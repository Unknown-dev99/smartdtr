<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use App\Services\File201PdfCompiler;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class File201PrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_compiled_pdf_includes_uploads_in_201_category_order(): void
    {
        Storage::fake('local');
        $personnel = Personnel::create([
            'name' => 'Jennefer S Lonzañeda',
            'role' => 'TCH V',
            'employee_number' => '00-037',
        ]);

        $this->storeUpload($personnel, 'saln', 'saln.jpg', $this->jpegBytes(), 'image/jpeg', '2024 SALN');
        $this->storeUpload($personnel, 'appointments', 'oath.pdf', $this->pdfBytes('Oath of office'), 'application/pdf');
        $this->storeUpload($personnel, 'personal_data_sheet', 'pds.docx', $this->docxBytes('Personal data sheet 2017'), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $binary = app(File201PdfCompiler::class)->compile($personnel);

        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertStringContainsString('DCTDecode', $binary);
        $appointments = strpos($binary, 'Appointments');
        $pds = strpos($binary, 'Personal Data Sheet');
        $saln = strpos($binary, 'SALN Form');
        $this->assertNotFalse($appointments);
        $this->assertNotFalse($pds);
        $this->assertNotFalse($saln);
        $this->assertLessThan($pds, $appointments);
        $this->assertLessThan($saln, $pds);
        $this->assertStringContainsString('Personal data sheet 2017', $binary);
        $this->assertStringContainsString('Lonza', $binary);
    }

    public function test_staff_can_open_the_compiled_pdf_after_uploading(): void
    {
        Storage::fake('local');
        $personnel = Personnel::create(['name' => 'Precious Calle', 'role' => 'TCH I', 'employee_number' => '00-018']);
        $staff = StaffUser::create([
            'personnel_id' => $personnel->id,
            'name' => 'Precious Calle',
            'email' => 'precious.calle@example.test',
            'password' => 'Password123!',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertDontSee('Print 201 File (PDF)');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.files201.store'), [
                'category' => 'birth_certificate',
                'file' => UploadedFile::fake()->image('psa-birth.jpg', 80, 100),
                'notes' => 'PSA copy',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertSee('Print 201 File (PDF)');

        $response = $this->actingAs($staff, 'staff')->get(route('staff.files201.print'));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }

    public function test_print_without_uploads_asks_for_a_document(): void
    {
        $personnel = Personnel::create(['name' => 'Edmar Teel', 'role' => 'TCH III']);
        $staff = StaffUser::create([
            'personnel_id' => $personnel->id,
            'name' => 'Edmar Teel',
            'email' => 'edmar.teel@example.test',
            'password' => 'Password123!',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $this->actingAs($staff, 'staff')
            ->from(route('staff.files201.index'))
            ->get(route('staff.files201.print'))
            ->assertRedirect(route('staff.files201.index'))
            ->assertSessionHas('error', 'Upload a document before printing the 201 File.');
    }

    protected function storeUpload(Personnel $personnel, string $category, string $name, string $bytes, string $mime, ?string $notes = null): void
    {
        $path = '201_files/'.$personnel->id.'/'.$category.'/'.$name;
        Storage::disk('local')->put($path, $bytes);

        Personnel201File::create([
            'personnel_id' => $personnel->id,
            'category' => $category,
            'original_name' => $name,
            'stored_path' => $path,
            'mime_type' => $mime,
            'size_bytes' => strlen($bytes),
            'notes' => $notes,
            'uploaded_by_type' => 'staff',
            'uploaded_by_name' => $personnel->name,
        ]);
    }

    protected function jpegBytes(): string
    {
        $image = imagecreatetruecolor(40, 30);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 40, 80));
        ob_start();
        imagejpeg($image, null, 90);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    protected function pdfBytes(string $label): string
    {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, $label);

        return $pdf->Output('S');
    }

    protected function docxBytes(string $text): string
    {
        $document = new PhpWord();
        $document->addSection()->addText($text);
        $path = storage_path('app/temp/201-test-'.uniqid('', true).'.docx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        IOFactory::createWriter($document, 'Word2007')->save($path);
        $bytes = (string) file_get_contents($path);
        @unlink($path);

        return $bytes;
    }
}
