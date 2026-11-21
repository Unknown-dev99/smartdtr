<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClearanceRequest;
use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Services\AuditLogService;
use App\Services\ClearanceRequestService;
use App\Services\CompensatoryTimeOffService;
use App\Services\File201PdfCompiler;
use App\Services\File201Service;
use App\Services\MedicalCertificateService;
use App\Services\PersonalDataSheetService;
use App\Services\PositionDescriptionService;
use App\Services\ServiceRecordService;
use App\Services\WorkExperienceSheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class File201Controller extends Controller
{
    public function __construct(
        protected File201Service $files,
        protected AuditLogService $auditLog
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $personnel = Personnel::query()
            ->withCount('files201')
            ->orderBy('name')
            ->get();

        return view('admin.files201.index', [
            'personnel' => $personnel,
            'q' => $q,
            'categories' => $this->files->categories(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:120',
            'personnel_id' => 'nullable|integer|exists:personnel,id',
        ]);

        $category = $this->files->addCategory($validated['label']);

        $this->auditLog->log('201_file.category_added', "Added 201 file category {$category->label}");

        $redirect = isset($validated['personnel_id'])
            ? redirect()->route('admin.files201.show', ['personnel' => $validated['personnel_id'], 'open' => $category->slug])
            : redirect()->route('admin.files201.index');

        return $redirect->with('success', 'Category added to every 201 file.');
    }

    public function show(Personnel $personnel): View
    {
        $files = $personnel->files201()
            ->latest()
            ->get()
            ->groupBy('category');

        return view('admin.files201.show', [
            'personnel' => $personnel,
            'filesByCategory' => $files,
            'categories' => $this->files->categoriesFor($personnel),
            'clearanceRequests' => $personnel->clearanceRequests()->orderByDesc('year')->get(),
            'workExperienceSheet' => $personnel->workExperienceSheet,
            'positionDescriptionForm' => $personnel->positionDescriptionForm,
            'medicalCertificate' => $personnel->medicalCertificate,
            'personalDataSheet' => $personnel->personalDataSheet,
            'serviceRecordReady' => $personnel->serviceRecord !== null,
            'ctoReady' => $personnel->compensatoryTimeOff !== null,
        ]);
    }

    public function printServiceRecord(Personnel $personnel, ServiceRecordService $records): Response|RedirectResponse
    {
        $record = $personnel->serviceRecord;
        if (! $record) {
            return back()->with('error', 'This employee has not filled in a Service Record yet.');
        }

        return response($records->render($record), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Service-Record.pdf"',
        ]);
    }

    public function printCompensatoryTimeOff(Personnel $personnel, CompensatoryTimeOffService $records): Response|RedirectResponse
    {
        $record = $personnel->compensatoryTimeOff;
        if (! $record) {
            return back()->with('error', 'This employee has not filled in a Compensatory Time Off form yet.');
        }

        return response($records->render($record), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Compensatory-Time-Off.pdf"',
        ]);
    }

    public function printWorkExperience(Personnel $personnel, WorkExperienceSheetService $sheets): Response|RedirectResponse
    {
        $sheet = $personnel->workExperienceSheet;
        if (! $sheet) {
            return back()->with('error', 'This employee has not filled in a Work Experience Sheet yet.');
        }

        return response($sheets->render($sheet), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Work-Experience-Sheet.pdf"',
        ]);
    }

    public function printPositionDescription(Personnel $personnel, PositionDescriptionService $forms): Response|RedirectResponse
    {
        $form = $personnel->positionDescriptionForm;
        if (! $form) {
            return back()->with('error', 'This employee has not filled in a Position Description Form yet.');
        }

        return response($forms->render($form), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Position-Description-Form.pdf"',
        ]);
    }

    public function printMedicalCertificate(Personnel $personnel, MedicalCertificateService $certificates): Response|RedirectResponse
    {
        $certificate = $personnel->medicalCertificate;
        if (! $certificate) {
            return back()->with('error', 'This employee has not filled in a Medical Certificate yet.');
        }

        return response($certificates->render($certificate), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Medical-Certificate-CS-Form-211.pdf"',
        ]);
    }

    public function printPersonalDataSheet(Personnel $personnel, PersonalDataSheetService $sheets): Response|RedirectResponse
    {
        $sheet = $personnel->personalDataSheet;
        if (! $sheet) {
            return back()->with('error', 'This employee has not filled in a Personal Data Sheet yet.');
        }

        return response($sheets->render($sheet), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Personal-Data-Sheet-CS-Form-212.pdf"',
        ]);
    }

    public function printClearance(Personnel $personnel, ClearanceRequest $clearance, ClearanceRequestService $clearances): Response
    {
        abort_unless($clearance->personnel_id === $personnel->id, 404);

        return response($clearances->render($clearance), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Request-for-Clearance-'.$clearance->year.'.pdf"',
        ]);
    }

    public function store(Request $request, Personnel $personnel): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        $validated = $request->validate($this->files->uploadRules());
        $file = $this->files->store(
            $personnel,
            $validated['category'],
            $request->file('file'),
            $validated['notes'] ?? null
        );

        $this->auditLog->log(
            '201_file.uploaded',
            "Uploaded {$file->original_name} ({$file->categoryLabel()}) for {$personnel->name}",
            $file
        );

        return back()->with('success', 'Document uploaded. Print 201 File (PDF) opens the compiled file.');
    }

    public function print(Personnel $personnel, File201PdfCompiler $compiler): Response|RedirectResponse
    {
        try {
            $binary = $compiler->compile($personnel);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('201_file.printed', "Printed the compiled 201 File for {$personnel->name}");

        $filename = '201-File-'.(Str::slug($personnel->name) ?: 'employee').'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download(Personnel $personnel, Personnel201File $file): BinaryFileResponse
    {
        abort_unless($file->personnel_id === $personnel->id, 404);
        abort_unless($file->existsOnDisk(), 404);

        return response()->download($file->absolutePath(), $file->original_name);
    }

    public function destroy(Personnel $personnel, Personnel201File $file): RedirectResponse
    {
        if (! $this->adminUser()->canWrite()) {
            abort(403);
        }

        abort_unless($file->personnel_id === $personnel->id, 404);

        $label = $file->original_name;
        $category = $file->categoryLabel();
        $file->delete();

        $this->auditLog->log(
            '201_file.deleted',
            "Deleted {$label} ({$category}) from {$personnel->name}'s 201 File"
        );

        return back()->with('success', 'Document removed from 201 File.');
    }
}
