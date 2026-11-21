<?php

namespace App\Support;

use setasign\Fpdi\Fpdi;

class PrintReadyPdf extends Fpdi
{
    public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->SetCompression(false);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 8, '201 File  |  Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
        $this->SetTextColor(0, 0, 0);
    }

    public function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        if ($converted === false) {
            return preg_replace('/[^\x20-\x7E]/', '', $value) ?? '';
        }

        return $converted;
    }
}
