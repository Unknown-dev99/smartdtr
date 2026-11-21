<?php

namespace App\Services;

use App\Models\FaceEnrollment;
use App\Models\Personnel;
use App\Models\StaffUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FaceEnrollmentService
{
    public function __construct(
        protected NotificationService $notifications,
        protected AuditLogService $auditLog
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function submit(StaffUser $staff, array $files): FaceEnrollment
    {
        $pending = FaceEnrollment::where('staff_user_id', $staff->id)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            throw new \RuntimeException('You already have a face submission waiting for admin review.');
        }

        $enrollment = FaceEnrollment::create([
            'staff_user_id' => $staff->id,
            'personnel_id' => $staff->personnel_id,
            'status' => 'pending',
        ]);

        $dir = $enrollment->storageDir();
        Storage::disk('face_pending')->makeDirectory($dir);

        foreach ($files as $index => $file) {
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'_'.time()."_{$index}.".$file->getClientOriginalExtension();
            $file->storeAs($dir, $filename, 'face_pending');
        }

        $this->auditLog->log('face.submitted', "{$staff->name} submitted {$enrollment->photoCount()} face photo(s)", $enrollment);
        $this->notifications->notifyAllAdmins(
            'Face photos pending',
            "{$staff->name} submitted face photos for the attendance camera.",
            route('admin.hr.faces')
        );

        return $enrollment;
    }

    public function approve(FaceEnrollment $enrollment, int $adminId, ?int $personnelId = null): FaceEnrollment
    {
        if ($enrollment->status !== 'pending') {
            throw new \RuntimeException('This submission was already reviewed.');
        }

        $personnelId = $personnelId ?: $enrollment->personnel_id ?: $enrollment->staffUser?->personnel_id;
        if (! $personnelId) {
            throw new \RuntimeException('Select a personnel record before approving.');
        }

        $personnel = Personnel::findOrFail($personnelId);
        $dest = (string) $personnel->id;

        if (Storage::disk('faces')->exists($dest)) {
            Storage::disk('faces')->deleteDirectory($dest);
        }
        Storage::disk('faces')->makeDirectory($dest);

        foreach ($enrollment->photoNames() as $name) {
            $data = Storage::disk('face_pending')->get($enrollment->storageDir().'/'.$name);
            Storage::disk('faces')->put($dest.'/'.$name, $data);
        }

        $personnel->update(['face_dataset_dir' => storage_path('app/known_faces/'.$personnel->id)]);

        $staff = $enrollment->staffUser;
        if ($staff && ! $staff->personnel_id) {
            $staff->update(['personnel_id' => $personnel->id]);
        }

        $enrollment->update([
            'personnel_id' => $personnel->id,
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'reject_reason' => null,
        ]);

        if (Storage::disk('face_pending')->exists($enrollment->storageDir())) {
            Storage::disk('face_pending')->deleteDirectory($enrollment->storageDir());
        }

        $this->auditLog->log('face.approved', "Approved face photos for {$personnel->name} (#{$personnel->id})", $enrollment);

        if ($staff) {
            $this->notifications->notifyStaff(
                $staff,
                'Face photos approved',
                'Your face photos were approved. The attendance camera will recognize you shortly.',
                route('staff.faces')
            );
        }

        return $enrollment->fresh();
    }

    public function reject(FaceEnrollment $enrollment, int $adminId, ?string $reason = null): FaceEnrollment
    {
        if ($enrollment->status !== 'pending') {
            throw new \RuntimeException('This submission was already reviewed.');
        }

        $enrollment->update([
            'status' => 'rejected',
            'reject_reason' => $reason,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);

        $this->auditLog->log('face.rejected', "Rejected face photos for {$enrollment->staffUser?->name}", $enrollment);

        if ($enrollment->staffUser) {
            $message = 'Your face photos were not approved. Please upload clearer photos.';
            if ($reason) {
                $message .= ' Reason: '.$reason;
            }
            $this->notifications->notifyStaff(
                $enrollment->staffUser,
                'Face photos declined',
                $message,
                route('staff.faces')
            );
        }

        return $enrollment->fresh();
    }
}
