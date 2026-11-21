<?php

namespace App\Services;

use App\Models\DocumentSignatory;
use App\Models\LocatorRequest;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class TravelAuthorityWordService
{
    public function download(LocatorRequest $request): BinaryFileResponse
    {
        $request->loadMissing(['staffUser', 'personnel', 'reviewer']);

        $tempPath = $this->fill($request);
        $filename = 'Locator_Slip_'.$request->slipNumber().'_'.str_replace(' ', '_', $request->staffUser->officialLetterName() ?: $request->staffUser->name).'.docx';

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function fill(LocatorRequest $request): string
    {
        $source = base_path('LOCATOR - SHS.doc');
        if (! is_file($source)) {
            $source = storage_path('app/templates/LOCATOR - SHS.doc');
        }
        if (! is_file($source)) {
            throw new RuntimeException('Official LOCATOR - SHS.doc template was not found.');
        }

        $script = base_path('scripts/fill-locator-slip.ps1');
        if (! is_file($script)) {
            throw new RuntimeException('Locator fill script missing: '.$script);
        }

        $staff = $request->staffUser;
        $position = $request->position_designation
            ?: ($staff->department ?: ($staff->personnel?->role ?? $staff->roleLabel()));

        $nature = (string) ($request->nature ?: 'Official Business');
        if (! in_array($nature, ['Official Business', 'Official Time'], true)) {
            $nature = 'Official Business';
        }

        $dateTime = $request->date->format('F d, Y')
            .' / '.$request->formattedTimeOut()
            .' – '.$request->formattedTimeInExpected();

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tempPath = $dir.DIRECTORY_SEPARATOR.'Locator_Slip_'.$request->id.'_'.uniqid('', true).'.docx';

        $process = new Process([
            'powershell',
            '-NoProfile',
            '-ExecutionPolicy', 'Bypass',
            '-File', $script,
            '-Source', $source,
            '-Output', $tempPath,
            '-Name', $staff->officialLetterName() ?: $staff->name,
            '-Position', $position,
            '-Purpose', (string) $request->purpose,
            '-DateTime', $dateTime,
            '-Destination', (string) $request->destination,
            '-Nature', $nature,
            '-SchoolHead', DocumentSignatory::resolve('school_head') ?? 'PRISCA ROMELA C. BUENAVENTURA',
            '-SchoolHeadTitle', DocumentSignatory::resolve('school_head', 'title') ?? 'School Head',
        ]);
        $process->setTimeout(120);
        $process->run();

        $output = trim($process->getOutput().$process->getErrorOutput());
        if (! $process->isSuccessful() || ! is_file($tempPath) || ! str_starts_with($output, 'OK')) {
            throw new RuntimeException(
                'Could not generate Locator Slip in MS Word. Make sure Microsoft Word is installed. '.$output
            );
        }

        return $tempPath;
    }
}
