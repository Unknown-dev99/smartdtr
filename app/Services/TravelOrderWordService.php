<?php

namespace App\Services;

use App\Models\DocumentSignatory;
use App\Models\TravelOrder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class TravelOrderWordService
{
    public function download(TravelOrder $order): BinaryFileResponse
    {
        $order->loadMissing(['staffUser', 'personnel', 'reviewer']);

        $tempPath = $this->fill($order);
        $filename = 'Travel_Order_'.$order->orderNumber().'_'.str_replace(' ', '_', $order->staffUser->officialLetterName() ?: $order->staffUser->name).'.docx';

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function fill(TravelOrder $order): string
    {
        $source = $this->resolveTemplatePath();

        $staff = $order->staffUser;
        $position = $order->position_designation
            ?: ($staff->department ?: ($staff->personnel?->role ?? $staff->roleLabel()));

        $destination = (string) $order->destination;
        if ($order->travel_type === 'outside') {
            $destination = 'Outside — '.$destination;
        } elseif ($order->travel_type === 'within') {
            $destination = 'Within — '.$destination;
        }

        $employeeDate = $order->created_at?->format('F d, Y') ?? now()->format('F d, Y');
        $schoolHeadDate = $order->reviewed_at?->format('F d, Y') ?? '';

        $values = [
            0 => $staff->officialLetterName() ?: (string) $staff->name,
            1 => (string) $position,
            2 => (string) ($order->permanent_station ?: 'Isabela National High School'),
            3 => (string) $order->purpose,
            4 => (string) $order->host_of_activity,
            5 => (string) $order->inclusiveDatesLabel(),
            6 => $destination,
            7 => (string) $order->fund_source,
        ];

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tempPath = $dir.DIRECTORY_SEPARATOR.'Travel_Order_'.$order->id.'_'.uniqid('', true).'.docx';
        if (! copy($source, $tempPath)) {
            throw new RuntimeException('Could not copy Travel Order template.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tempPath) !== true) {
            throw new RuntimeException('Could not open Travel Order template for editing.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            throw new RuntimeException('Travel Order template is missing word/document.xml.');
        }

        $documentXml = $this->fillDocumentXml(
            $documentXml,
            $values,
            $staff->officialLetterName() ?: (string) $staff->name,
            $employeeDate,
            $schoolHeadDate,
            DocumentSignatory::resolve('school_head') ?? '',
            $this->resolveSdsName()
        );

        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        return $tempPath;
    }

    protected function resolveSdsName(): string
    {
        $name = DocumentSignatory::resolve('schools_division_superintendent');
        $defaultShort = 'EDUARDO C. ESCORPISO, JR.';
        $official = 'EDUARDO C. ESCORPISO JR., EdD, CESO V';

        if (! filled($name) || strcasecmp(trim($name), $defaultShort) === 0) {
            return $official;
        }

        return $name;
    }

    public function resolveTemplatePath(): string
    {
        foreach ([
            base_path('TRAVEL ORDER -WITH IN & OUTSIDE 2026 INHS-SHS.docx'),
            storage_path('app/templates/TRAVEL ORDER -WITH IN & OUTSIDE 2026 INHS-SHS.docx'),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException('Travel Order Word template was not found: TRAVEL ORDER -WITH IN & OUTSIDE 2026 INHS-SHS.docx');
    }

    /**
     * @param  array<int, string>  $values  row index => value for column 2
     */
    protected function fillDocumentXml(
        string $xml,
        array $values,
        string $employeeName,
        string $employeeDate,
        string $schoolHeadDate,
        string $schoolHeadName,
        string $sdsName = ''
    ): string {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = true;
        $previous = libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            libxml_use_internal_errors($previous);
            throw new RuntimeException('Could not parse Travel Order document.xml.');
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $mainTable = $xpath->query('//w:body/w:tbl')->item(0)
            ?? $xpath->query('//w:tbl')->item(0);
        if (! $mainTable) {
            throw new RuntimeException('Travel Order template table structure is invalid.');
        }

        $rows = $xpath->query('./w:tr', $mainTable);
        if (! $rows || $rows->length < 8) {
            throw new RuntimeException('Travel Order template table structure is invalid.');
        }

        foreach ($values as $rowIndex => $text) {
            $row = $rows->item($rowIndex);
            if (! $row) {
                continue;
            }
            $cells = $xpath->query('./w:tc', $row);
            if (! $cells || $cells->length < 2) {
                continue;
            }
            $this->setCellPlainText($xpath, $cells->item(1), $text);
        }

        // Employee attestation (row 8): name over the line, label below, date on the right.
        if ($rows->item(8)) {
            $cell = $xpath->query('./w:tc', $rows->item(8))->item(0);
            if ($cell) {
                $this->writeSignatureOverPrintedName(
                    $xpath,
                    $cell,
                    name: strtoupper($employeeName),
                    date: $employeeDate,
                    caption: 'Signature over Printed Name',
                    lineParagraphIndex: 2,
                    captionParagraphIndex: 3
                );
            }
        }

        // School Head certification (row 9).
        if ($rows->item(9)) {
            $cell = $xpath->query('./w:tc', $rows->item(9))->item(0);
            if ($cell) {
                $this->writeSignatureOverPrintedName(
                    $xpath,
                    $cell,
                    name: $schoolHeadName !== '' ? strtoupper($schoolHeadName) : '',
                    date: $schoolHeadDate,
                    caption: 'School Head',
                    lineParagraphIndex: 3,
                    captionParagraphIndex: 4
                );
            }
        }

        // SDS approval (row 10): same name / title / date layout as School Head.
        if ($rows->item(10)) {
            $cell = $xpath->query('./w:tc', $rows->item(10))->item(0);
            if ($cell) {
                $this->writeSignatureOverPrintedName(
                    $xpath,
                    $cell,
                    name: $sdsName !== '' ? $sdsName : 'EDUARDO C. ESCORPISO JR., EdD, CESO V',
                    date: '',
                    caption: 'Schools Division Superintendent',
                    lineParagraphIndex: 3,
                    captionParagraphIndex: 4
                );
            }
        }

        return $dom->saveXML() ?: $xml;
    }

    protected function setCellPlainText(DOMXPath $xpath, DOMElement $cell, string $text): void
    {
        $textNodes = $xpath->query('.//w:t', $cell);
        if ($textNodes && $textNodes->length > 0) {
            // Put full value in the first w:t; clear the rest so leftovers don't show.
            $first = true;
            foreach ($textNodes as $node) {
                if ($first) {
                    $node->nodeValue = $this->escapeXmlText($text);
                    if ($node instanceof DOMElement) {
                        $node->setAttributeNS(
                            'http://www.w3.org/XML/1998/namespace',
                            'xml:space',
                            'preserve'
                        );
                    }
                    $first = false;
                } else {
                    $node->nodeValue = '';
                }
            }

            return;
        }

        // No text node: create a simple paragraph run inside the cell.
        $pList = $xpath->query('./w:p', $cell);
        $p = ($pList && $pList->length > 0) ? $pList->item(0) : null;
        if (! $p) {
            $p = $cell->ownerDocument->createElementNS(
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                'w:p'
            );
            $cell->appendChild($p);
        }

        $r = $cell->ownerDocument->createElementNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'w:r'
        );
        $t = $cell->ownerDocument->createElementNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'w:t',
            $this->escapeXmlText($text)
        );
        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $r->appendChild($t);
        $p->appendChild($r);
    }

    /**
     * Two real columns so Times New Roman cannot collapse the date into the name:
     *   PRINTED NAME                         September 19, 2026
     *   School Head / Signature over Printed Name     Date
     */
    protected function writeSignatureOverPrintedName(
        DOMXPath $xpath,
        DOMElement $cell,
        string $name,
        string $date,
        string $caption,
        int $lineParagraphIndex,
        int $captionParagraphIndex
    ): void {
        $paragraphs = $xpath->query('./w:p', $cell);
        if (! $paragraphs || $paragraphs->length <= max($lineParagraphIndex, $captionParagraphIndex)) {
            return;
        }

        $doc = $cell->ownerDocument;
        if (! $doc) {
            return;
        }

        $lineP = $paragraphs->item($lineParagraphIndex);
        $captionP = $paragraphs->item($captionParagraphIndex);
        if (! $lineP || ! $captionP) {
            return;
        }

        $table = $this->makeSignatureTable($doc, trim($name), trim($date), trim($caption));
        $cell->insertBefore($table, $lineP);
        $cell->removeChild($lineP);
        $cell->removeChild($captionP);

        $last = $cell->lastChild;
        if (! $last || $last->localName !== 'p') {
            $cell->appendChild($this->makeEmptyParagraph($doc));
        }
    }

    protected function makeSignatureTable(DOMDocument $doc, string $name, string $date, string $caption): DOMElement
    {
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $tbl = $doc->createElementNS($ns, 'w:tbl');

        $tblPr = $doc->createElementNS($ns, 'w:tblPr');
        $tblW = $doc->createElementNS($ns, 'w:tblW');
        $tblW->setAttribute('w:w', '5000');
        $tblW->setAttribute('w:type', 'pct');
        $tblPr->appendChild($tblW);

        $borders = $doc->createElementNS($ns, 'w:tblBorders');
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $edge) {
            $border = $doc->createElementNS($ns, 'w:'.$edge);
            $border->setAttribute('w:val', 'nil');
            $borders->appendChild($border);
        }
        $tblPr->appendChild($borders);
        $tbl->appendChild($tblPr);

        $grid = $doc->createElementNS($ns, 'w:tblGrid');
        foreach ([6200, 2800] as $width) {
            $col = $doc->createElementNS($ns, 'w:gridCol');
            $col->setAttribute('w:w', (string) $width);
            $grid->appendChild($col);
        }
        $tbl->appendChild($grid);

        $tbl->appendChild($this->makeSignatureTableRow($doc, $name, $date, true));
        $tbl->appendChild($this->makeSignatureTableRow($doc, $caption, 'Date', false));

        return $tbl;
    }

    protected function makeSignatureTableRow(DOMDocument $doc, string $left, string $right, bool $leftBold): DOMElement
    {
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $tr = $doc->createElementNS($ns, 'w:tr');
        $tr->appendChild($this->makeSignatureTableCell($doc, $left, $leftBold, 6200));
        $tr->appendChild($this->makeSignatureTableCell($doc, $right, false, 2800));

        return $tr;
    }

    protected function makeSignatureTableCell(DOMDocument $doc, string $text, bool $bold, int $width): DOMElement
    {
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $tc = $doc->createElementNS($ns, 'w:tc');

        $tcPr = $doc->createElementNS($ns, 'w:tcPr');
        $tcW = $doc->createElementNS($ns, 'w:tcW');
        $tcW->setAttribute('w:w', (string) $width);
        $tcW->setAttribute('w:type', 'dxa');
        $tcPr->appendChild($tcW);
        $tc->appendChild($tcPr);

        $p = $doc->createElementNS($ns, 'w:p');
        $pPr = $doc->createElementNS($ns, 'w:pPr');
        $jc = $doc->createElementNS($ns, 'w:jc');
        $jc->setAttribute('w:val', 'center');
        $pPr->appendChild($jc);
        $p->appendChild($pPr);

        $run = $doc->createElementNS($ns, 'w:r');
        $rPr = $doc->createElementNS($ns, 'w:rPr');
        $fonts = $doc->createElementNS($ns, 'w:rFonts');
        $fonts->setAttribute('w:ascii', 'Times New Roman');
        $fonts->setAttribute('w:hAnsi', 'Times New Roman');
        $rPr->appendChild($fonts);
        $size = $doc->createElementNS($ns, 'w:sz');
        $size->setAttribute('w:val', '22');
        $rPr->appendChild($size);
        if ($bold) {
            $rPr->appendChild($doc->createElementNS($ns, 'w:b'));
        }
        $run->appendChild($rPr);

        $node = $doc->createElementNS($ns, 'w:t');
        $node->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $node->nodeValue = $this->escapeXmlText($text);
        $run->appendChild($node);
        $p->appendChild($run);
        $tc->appendChild($p);

        return $tc;
    }

    protected function makeEmptyParagraph(DOMDocument $doc): DOMElement
    {
        return $doc->createElementNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'w:p'
        );
    }

    protected function escapeXmlText(string $text): string
    {
        // DOMDocument will re-escape when setting nodeValue in most PHP builds,
        // but we normalize control characters here.
        return str_replace("\0", '', $text);
    }
}
