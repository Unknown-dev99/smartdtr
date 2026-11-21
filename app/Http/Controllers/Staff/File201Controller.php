<?php

namespace App\Http\Controllers\Staff;

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

    public function index(): View
    {
        $personnel = $this->linkedPersonnel();

        $filesByCategory = collect();
        $clearanceRequests = collect();
        $clearanceDefaults = [];
        $workExperienceDefaults = [];
        $workExperienceReady = false;
        $positionDescriptionDefaults = [];
        $positionDescriptionReady = false;
        $medicalCertificateDefaults = [];
        $medicalCertificateReady = false;
        $personalDataSheetDefaults = [];
        $personalDataSheetReady = false;
        $serviceRecordDefaults = [];
        $serviceRecordReady = false;
        $ctoDefaults = [];
        $ctoReady = false;
        if ($personnel) {
            $filesByCategory = $personnel->files201()
                ->latest()
                ->get()
                ->groupBy('category');
            $clearanceRequests = $personnel->clearanceRequests()->orderByDesc('year')->get();
            $editingYear = (int) request('clearance_year', now()->year);
            $existing = $clearanceRequests->firstWhere('year', $editingYear);
            $clearanceDefaults = app(ClearanceRequestService::class)->defaults(
                $this->staffUser(),
                $personnel,
                $existing
            );
            $workExperienceDefaults = app(WorkExperienceSheetService::class)->defaults(
                $this->staffUser(),
                $personnel,
                $personnel->workExperienceSheet
            );
            $workExperienceReady = $personnel->workExperienceSheet !== null;
            $positionDescriptionDefaults = app(PositionDescriptionService::class)->defaults(
                $this->staffUser(),
                $personnel,
                $personnel->positionDescriptionForm
            );
            $positionDescriptionReady = $personnel->positionDescriptionForm !== null;
            $medicalCertificateDefaults = app(MedicalCertificateService::class)->defaults(
                $this->staffUser(),
                $personnel,
                $personnel->medicalCertificate
            );
            $medicalCertificateReady = $personnel->medicalCertificate !== null;
            $personalDataSheetDefaults = app(PersonalDataSheetService::class)->defaults(
                $this->staffUser(),
                $personnel,
                $personnel->personalDataSheet
            );
            $personalDataSheetReady = $personnel->personalDataSheet !== null;
            if ($personnel->isNonTeaching()) {
                $serviceRecordDefaults = app(ServiceRecordService::class)->defaults(
                    $this->staffUser(),
                    $personnel,
                    $personnel->serviceRecord
                );
                $serviceRecordReady = $personnel->serviceRecord !== null;
                $ctoDefaults = app(CompensatoryTimeOffService::class)->defaults(
                    $this->staffUser(),
                    $personnel,
                    $personnel->compensatoryTimeOff
                );
                $ctoReady = $personnel->compensatoryTimeOff !== null;
            }
        }

        return view('staff.files201.index', [
            'personnel' => $personnel,
            'filesByCategory' => $filesByCategory,
            'categories' => $this->files->categoriesFor($personnel),
            'clearanceRequests' => $clearanceRequests,
            'clearanceDefaults' => $clearanceDefaults,
            'workExperienceDefaults' => $workExperienceDefaults,
            'workExperienceReady' => $workExperienceReady,
            'positionDescriptionDefaults' => $positionDescriptionDefaults,
            'positionDescriptionReady' => $positionDescriptionReady,
            'medicalCertificateDefaults' => $medicalCertificateDefaults,
            'medicalCertificateReady' => $medicalCertificateReady,
            'personalDataSheetDefaults' => $personalDataSheetDefaults,
            'personalDataSheetReady' => $personalDataSheetReady,
            'serviceRecordDefaults' => $serviceRecordDefaults,
            'serviceRecordReady' => $serviceRecordReady,
            'ctoDefaults' => $ctoDefaults,
            'ctoReady' => $ctoReady,
        ]);
    }

    public function storeWorkExperience(Request $request, WorkExperienceSheetService $sheets): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $validated = $request->validate($sheets->rules());
        $sheet = $sheets->save($personnel, $this->staffUser(), $validated);

        $this->auditLog->log(
            'work_experience.saved',
            "Saved Work Experience Sheet for {$personnel->name}",
            $sheet
        );

        return redirect()
            ->route('staff.files201.index', ['work_experience' => 1])
            ->with('success', 'Work Experience Sheet is saved. Use Print form to open it.');
    }

    public function printWorkExperience(WorkExperienceSheetService $sheets): Response|RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $sheet = $personnel->workExperienceSheet;
        if (! $sheet) {
            return back()->with('error', 'Fill in the Work Experience Sheet before printing it.');
        }

        return response($sheets->render($sheet), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Work-Experience-Sheet.pdf"',
        ]);
    }

    public function storePositionDescription(Request $request, PositionDescriptionService $forms): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $validated = $request->validate($forms->rules());
        $form = $forms->save($personnel, $this->staffUser(), $validated);

        $this->auditLog->log(
            'position_description.saved',
            "Saved Position Description Form for {$personnel->name}",
            $form
        );

        return redirect()
            ->route('staff.files201.index', ['position_description' => 1])
            ->with('success', 'Position Description Form is saved. Use Print form to open it.');
    }

    public function printPositionDescription(PositionDescriptionService $forms): Response|RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $form = $personnel->positionDescriptionForm;
        if (! $form) {
            return back()->with('error', 'Fill in the Position Description Form before printing it.');
        }

        return response($forms->render($form), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Position-Description-Form.pdf"',
        ]);
    }

    public function storeMedicalCertificate(Request $request, MedicalCertificateService $certificates): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $validated = $request->validate($certificates->rules());
        $certificate = $certificates->save($personnel, $this->staffUser(), $validated);

        $this->auditLog->log(
            'medical_certificate.saved',
            "Saved Medical Certificate for {$personnel->name}",
            $certificate
        );

        return redirect()
            ->route('staff.files201.index', ['medical_certificate' => 1])
            ->with('success', 'Medical Certificate is saved. Use Print form to open it.');
    }

    public function printMedicalCertificate(MedicalCertificateService $certificates): Response|RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $certificate = $personnel->medicalCertificate;
        if (! $certificate) {
            return back()->with('error', 'Fill in the Medical Certificate before printing it.');
        }

        return response($certificates->render($certificate), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Medical-Certificate-CS-Form-211.pdf"',
        ]);
    }

    public function storePersonalDataSheet(Request $request, PersonalDataSheetService $sheets): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $validated = $request->validate($sheets->rules());
        $sheet = $sheets->save($personnel, $this->staffUser(), $validated);

        $this->auditLog->log(
            'personal_data_sheet.saved',
            "Saved Personal Data Sheet for {$personnel->name}",
            $sheet
        );

        return redirect()
            ->route('staff.files201.index', ['personal_data_sheet' => 1])
            ->with('success', 'Personal Data Sheet is saved. Use Print form to open it.');
    }

    public function printPersonalDataSheet(PersonalDataSheetService $sheets): Response|RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $sheet = $personnel->personalDataSheet;
        if (! $sheet) {
            return back()->with('error', 'Fill in the Personal Data Sheet before printing it.');
        }

        return response($sheets->render($sheet), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Personal-Data-Sheet-CS-Form-212.pdf"',
        ]);
    }

    public function storeServiceRecord(Request $request, ServiceRecordService $records): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $validated = $request->validate($records->rules());
        $record = $records->save($personnel, $this->staffUser(), $validated);

        $this->auditLog->log(
            'service_record.saved',
            "Saved Service Record for {$personnel->name}",
            $record
        );

        return redirect()
            ->route('staff.files201.index', ['service_record' => 1])
            ->with('success', 'Service Record is saved. Use Print form to open it.');
    }

    public function printServiceRecord(ServiceRecordService $records): Response|RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $record = $personnel->serviceRecord;
        if (! $record) {
            return back()->with('error', 'Fill in the Service Record before printing it.');
        }

        return response($records->render($record), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Service-Record.pdf"',
        ]);
    }

    public function storeCompensatoryTimeOff(Request $request, CompensatoryTimeOffService $records): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $validated = $request->validate($records->rules());
        $record = $records->save($personnel, $this->staffUser(), $validated);

        $this->auditLog->log(
            'cto.saved',
            "Saved Compensatory Time Off for {$personnel->name}",
            $record
        );

        return redirect()
            ->route('staff.files201.index', ['cto' => 1])
            ->with('success', 'Compensatory Time Off is saved. Use Print form to open it.');
    }

    public function printCompensatoryTimeOff(CompensatoryTimeOffService $records): Response|RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $record = $personnel->compensatoryTimeOff;
        if (! $record) {
            return back()->with('error', 'Fill in the Compensatory Time Off form before printing it.');
        }

        return response($records->render($record), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Compensatory-Time-Off.pdf"',
        ]);
    }

    public function storeClearance(Request $request, ClearanceRequestService $clearances): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        $validated = $request->validate($clearances->rules());
        $clearance = $clearances->save($personnel, $this->staffUser(), $validated);

        $this->auditLog->log(
            'clearance.saved',
            "Saved Request for Clearance {$clearance->year} for {$personnel->name}",
            $clearance
        );

        return redirect()
            ->route('staff.files201.index', ['clearance_year' => $clearance->year])
            ->with('success', 'Request for Clearance '.$clearance->year.' is saved. Use Print form to open it.');
    }

    public function printClearance(ClearanceRequest $clearance, ClearanceRequestService $clearances): Response
    {
        $personnel = $this->linkedPersonnelOrAbort();
        abort_unless($clearance->personnel_id === $personnel->id, 403);

        return response($clearances->render($clearance), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Request-for-Clearance-'.$clearance->year.'.pdf"',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();

        $validated = $request->validate($this->files->uploadRules());
        $file = $this->files->store(
            $personnel,
            $validated['category'],
            $request->file('file'),
            $validated['notes'] ?? null
        );

        $this->auditLog->log(
            '201_file.uploaded',
            "Staff uploaded {$file->original_name} ({$file->categoryLabel()})",
            $file
        );

        return back()->with('success', 'Document uploaded. Use Print 201 File (PDF) to open the compiled file.');
    }

    public function print(File201PdfCompiler $compiler): Response|RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();

        try {
            $binary = $compiler->compile($personnel);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('201_file.printed', "Staff printed the compiled 201 File for {$personnel->name}");

        return $this->pdfResponse($binary, $personnel->name);
    }

    public function download(Personnel201File $file): BinaryFileResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        abort_unless($file->personnel_id === $personnel->id, 403);
        abort_unless($file->existsOnDisk(), 404);

        return response()->download($file->absolutePath(), $file->original_name);
    }

    public function destroy(Personnel201File $file): RedirectResponse
    {
        $personnel = $this->linkedPersonnelOrAbort();
        abort_unless($file->personnel_id === $personnel->id, 403);

        $label = $file->original_name;
        $category = $file->categoryLabel();
        $file->delete();

        $this->auditLog->log(
            '201_file.deleted',
            "Staff deleted {$label} ({$category}) from own 201 File"
        );

        return back()->with('success', 'Document removed from your 201 File.');
    }

    protected function pdfResponse(string $binary, string $name): Response
    {
        $filename = '201-File-'.(Str::slug($name) ?: 'employee').'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    protected function linkedPersonnel(): ?Personnel
    {
        $staff = $this->staffUser();

        return $staff->personnel_id
            ? Personnel::find($staff->personnel_id)
            : null;
    }

    protected function linkedPersonnelOrAbort(): Personnel
    {
        $personnel = $this->linkedPersonnel();
        if (! $personnel) {
            abort(403, 'Your account is not linked to a personnel record. Contact HR.');
        }

        return $personnel;
    }
}
