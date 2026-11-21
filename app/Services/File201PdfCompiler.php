<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Support\PrintReadyPdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use Throwable;

class File201PdfCompiler
{
    public function __construct(protected File201Service $files) {}

    /**
     * Combine every uploaded 201 document into one print-ready PDF.
     */
    public function compile(Personnel $personnel): string
    {
        $documents = $this->documents($personnel);
        if ($documents->isEmpty()) {
            throw new RuntimeException('Upload a document before printing the 201 File.');
        }

        $pdf = new PrintReadyPdf('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->SetTitle($pdf->latin('201 File - '.$personnel->name));
        $pdf->SetAuthor('INHS SMART-DTR');

        $tempFiles = [];

        try {
            $this->addCover($pdf, $personnel, $documents);

            foreach ($documents as $index => $file) {
                $this->addDivider($pdf, $index + 1, $file);
                $this->addContents($pdf, $file, $tempFiles);
            }

            return $pdf->Output('S');
        } finally {
            foreach ($tempFiles as $temp) {
                if (is_file($temp)) {
                    @unlink($temp);
                }
            }
        }
    }

    /**
     * @return Collection<int, Personnel201File>
     */
    protected function documents(Personnel $personnel): Collection
    {
        $rank = array_flip($this->files->categoryKeys());

        return $personnel->files201()
            ->get()
            ->filter(fn (Personnel201File $file) => $file->existsOnDisk())
            ->sortBy(fn (Personnel201File $file) => sprintf(
                '%05d-%010d-%010d',
                $rank[$file->category] ?? 999,
                $file->created_at?->getTimestamp() ?? 0,
                $file->id
            ))
            ->values();
    }

    /**
     * @param  Collection<int, Personnel201File>  $documents
     */
    protected function addCover(PrintReadyPdf $pdf, Personnel $personnel, Collection $documents): void
    {
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->MultiCell(0, 8, $pdf->latin('ISABELA NATIONAL HIGH SCHOOL'), 0, 'C');
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 6, $pdf->latin('City of Ilagan'), 0, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->MultiCell(0, 10, '201 FILE', 0, 'C');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(0, 5, 'Print-ready compilation of uploaded documents', 0, 'C');
        $pdf->Ln(6);

        $pdf->SetFont('Helvetica', '', 12);
        $this->fact($pdf, 'Name', $personnel->name);
        $this->fact($pdf, 'Employee No.', $personnel->employee_number ?: '—');
        $this->fact($pdf, 'Position', $personnel->role ?: '—');
        $this->fact($pdf, 'Compiled', now()->format('F j, Y g:i A'));
        $pdf->Ln(4);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Documents included', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);

        foreach ($documents as $index => $file) {
            $line = ($index + 1).'. '.$file->categoryLabel().' — '.$file->original_name;
            $pdf->MultiCell(0, 5, $pdf->latin($line), 0, 'L');
        }
    }

    protected function fact(PrintReadyPdf $pdf, string $label, string $value): void
    {
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(36, 7, $label, 0, 0);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 7, $pdf->latin($value), 0, 'L');
    }

    protected function addDivider(PrintReadyPdf $pdf, int $number, Personnel201File $file): void
    {
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->MultiCell(0, 8, $pdf->latin($number.'. '.$file->categoryLabel()), 0, 'L');
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 6, $pdf->latin('File: '.$file->original_name), 0, 'L');
        $pdf->MultiCell(0, 6, $pdf->latin('Uploaded: '.$file->created_at?->format('F j, Y g:i A')), 0, 'L');
        if ($file->notes) {
            $pdf->MultiCell(0, 6, $pdf->latin('Notes: '.$file->notes), 0, 'L');
        }
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->MultiCell(0, 5, 'The uploaded document follows this page.', 0, 'L');
    }

    /**
     * @param  array<int, string>  $tempFiles
     */
    protected function addContents(PrintReadyPdf $pdf, Personnel201File $file, array &$tempFiles): void
    {
        try {
            match ($this->kind($file)) {
                'image' => $this->addImage($pdf, $file, $tempFiles),
                'pdf' => $this->addPdf($pdf, $file),
                'word' => $this->addWord($pdf, $file),
                default => $this->addNotice($pdf, 'This file type cannot be placed in the compiled PDF. Download the original and print it with this packet.'),
            };
        } catch (Throwable) {
            $this->addNotice($pdf, 'This file could not be placed in the compiled PDF. Download the original from My 201 File and print it with this packet.');
        }
    }

    protected function kind(Personnel201File $file): string
    {
        $extension = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
        $mime = strtolower((string) $file->mime_type);

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true) || str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if ($extension === 'pdf' || $mime === 'application/pdf') {
            return 'pdf';
        }

        if (in_array($extension, ['doc', 'docx'], true) || str_contains($mime, 'word')) {
            return 'word';
        }

        return 'other';
    }

    /**
     * @param  array<int, string>  $tempFiles
     */
    protected function addImage(PrintReadyPdf $pdf, Personnel201File $file, array &$tempFiles): void
    {
        $path = $this->jpegPath($file->absolutePath(), $tempFiles);
        $size = @getimagesize($path);
        if ($size === false) {
            throw new RuntimeException('Unreadable image.');
        }

        [$pixelWidth, $pixelHeight] = $size;
        $maxWidth = 190;
        $maxHeight = 250;
        $scale = min($maxWidth / max(1, $pixelWidth), $maxHeight / max(1, $pixelHeight));
        $width = $pixelWidth * $scale;
        $height = $pixelHeight * $scale;

        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $pdf->Image($path, (210 - $width) / 2, 16, $width, $height);
        $pdf->SetAutoPageBreak(true, 16);
    }

    /**
     * @param  array<int, string>  $tempFiles
     */
    protected function jpegPath(string $path, array &$tempFiles): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            return $path;
        }

        $image = @imagecreatefrompng($path);
        if ($image === false) {
            throw new RuntimeException('Unreadable image.');
        }

        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($flat, 255, 255, 255);
        imagefill($flat, 0, 0, $white);
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        $directory = storage_path('app/temp');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $temp = $directory.DIRECTORY_SEPARATOR.'201-'.uniqid('', true).'.jpg';
        imagejpeg($flat, $temp, 90);
        imagedestroy($image);
        imagedestroy($flat);
        $tempFiles[] = $temp;

        return $temp;
    }

    protected function addPdf(PrintReadyPdf $pdf, Personnel201File $file): void
    {
        $pageCount = $pdf->setSourceFile($file->absolutePath());
        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($template);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template);
        }
    }

    protected function addWord(PrintReadyPdf $pdf, Personnel201File $file): void
    {
        $text = trim($this->wordText($file->absolutePath()));
        if ($text === '') {
            $this->addNotice($pdf, 'This Word document has no readable text. Download the original and print it with this packet.');

            return;
        }

        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 6, $pdf->latin($text), 0, 'L');
    }

    protected function wordText(string $path): string
    {
        $document = IOFactory::load($path);
        $chunks = [];

        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $chunks[] = trim($this->elementText($element));
            }
        }

        return trim(implode("\n\n", array_filter($chunks, fn (string $chunk) => $chunk !== '')));
    }

    protected function elementText(object $element): string
    {
        if (method_exists($element, 'getRows')) {
            $rows = [];
            foreach ($element->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cells[] = trim($this->elementText($cell));
                }
                $rows[] = implode(' | ', array_filter($cells, fn (string $cell) => $cell !== ''));
            }

            return implode("\n", $rows);
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                if (is_object($child)) {
                    $parts[] = $this->elementText($child);
                }
            }

            return trim(implode('', $parts));
        }

        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text)) {
                return $text;
            }
            if (is_object($text)) {
                return $this->elementText($text);
            }
        }

        return '';
    }

    protected function addNotice(PrintReadyPdf $pdf, string $message): void
    {
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 6, $pdf->latin($message), 0, 'L');
    }
}
