<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\TemplateProcessor;

class LetterService
{
    /**
     * Mengisi template Word dengan data dinamis.
     *
     * @param mixed $record data letter.
     * @return string Path file Word yang sudah diisi.
     */
    public function fillWordTemplate($record): string
    {
        $letter_type = $record->letter_request->letterType;
        $templatePath = $letter_type->getFirstMediaPath('templates');

        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found at path: {$templatePath}");
        }

        $kades = User::role('kades')->first();
        $letter_request = $record->letter_request;

        $data = [
            "kades_nama" => $kades->firstname . " " . $kades->lastname,
            "user_nama" => $letter_request->name,
            "user_tempat_lahir" => $letter_request->birthplace_id,
            "user_tanggal_lahir" => Carbon::parse($letter_request->birth_date)->format("D M Y"),
            "user_pekerjaan" => $letter_request->work,
            "user_nik" => $letter_request->id_number,
            "user_alamat" => $letter_request->address,
            "waktu_sekarang" => Carbon::now()->format("D M Y"),
            "nomor_surat" => $record->number
        ];

        // Buat instance TemplateProcessor
        $templateProcessor = new TemplateProcessor($templatePath);

        // Isi template dengan data
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // Simpan hasilnya ke file baru
        $outputFileName = "filled_{$record->id}" . ".docx";
        $outputFilePath = Storage::path("filled_templates/{$outputFileName}");
        $templateProcessor->saveAs($outputFilePath);

        return $outputFilePath;
    }

    /**
     * Mengonversi file Word ke PDF.
     *
     * @param string $filePath Path file Word yang akan dikonversi.
     * @param object $record Record terkait.
     * @return string Path file PDF.
     */
    public function convertWordToPDF(string $filePath, $record)
    {
        // Konfigurasi dasar TCPDF
        Settings::setPdfRendererName(Settings::PDF_RENDERER_TCPDF);
        Settings::setPdfRendererPath(base_path('vendor/tecnickcom/tcpdf'));

        // Load Word document
        $phpWord = IOFactory::load($filePath, 'Word2007');

        // Ubah setting sebelum membuat writer
        $domWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
        $htmlContent = $domWriter->getContent();

        // Modifikasi HTML untuk menghilangkan border tabel
        $htmlContent = preg_replace('/<table([^>]*)>/', '<table$1 style="border-collapse: collapse; border: none;">', $htmlContent);
        $htmlContent = preg_replace('/<td([^>]*)>/', '<td$1 style="border: none;">', $htmlContent);
        $htmlContent = preg_replace('/<th([^>]*)>/', '<th$1 style="border: none;">', $htmlContent);

        // Simpan HTML yang sudah dimodifikasi ke file temporary
        $tempHtmlPath = storage_path('app/temp_' . $record->id . '.html');
        file_put_contents($tempHtmlPath, $htmlContent);

        // Buat PDF dari HTML yang sudah dimodifikasi
        $pdf = new \TCPDF();
        $pdf->SetCreator('EOffice Pammas');
        $pdf->SetAuthor('EOffice Pammas');
        $pdf->SetTitle('Converted Document');

        // Konfigurasi penting untuk menghilangkan border
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->setCellMargins(0, 0, 0, 0);
        $pdf->SetLineWidth(0);
        $pdf->SetAutoPageBreak(TRUE, 0);

        // Tambahkan halaman dan konten HTML
        $pdf->AddPage();
        $pdf->writeHTML($htmlContent, true, false, true, false, '');

        $fileName = 'output_' . $record->id . '.pdf';
        $outputPdfPath = storage_path('app/public/filled_templates/' . $fileName);

        // Simpan PDF
        $pdf->Output($outputPdfPath, 'F');

        // Hapus file HTML temporary
        if (file_exists($tempHtmlPath)) {
            unlink($tempHtmlPath);
        }

        return asset('storage/filled_templates/' . $fileName);
    }

    /**
     * Menggabungkan pengisian template dan konversi ke PDF.
     *
     * @param mixed $record data letter.
     * @return path file word.
     */
    public function generatePDFFromTemplate($record)
    {
        // 1. Isi template Word dengan data
        $wordFilePath = $this->fillWordTemplate($record);

        // 2. Konversi file Word ke PDF
        return $this->convertWordToPDF($wordFilePath, $record);
    }
}
