<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class LetterService
{
    /**
     * Mengisi template Word dengan data dinamis.
     *
     * @param string $templateName Nama template yang disimpan dalam storage.
     * @param array $data Data yang akan diisi ke dalam template.
     * @return string Path file Word yang sudah diisi.
     */
    public function fillWordTemplate(string $templateName, array $data): string
    {
        // Lokasi template Word
        $templatePath = Storage::path("templates/{$templateName}.docx");

        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found at path: {$templatePath}");
        }

        // Buat instance TemplateProcessor
        $templateProcessor = new TemplateProcessor($templatePath);

        // Isi template dengan data
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // Simpan hasilnya ke file baru
        $outputFileName = "filled_{$templateName}_" . time() . ".docx";
        $outputFilePath = Storage::path("filled_templates/{$outputFileName}");
        $templateProcessor->saveAs($outputFilePath);

        return $outputFilePath;
    }

    /**
     * Mengonversi file Word ke PDF.
     *
     * @param string $filePath Path file Word yang akan dikonversi.
     * @return path file word.
     */
    public function convertWordToPDF(string $filePath)
    {

        // Konfigurasi renderer untuk TCPDF
        Settings::setPdfRendererName(Settings::PDF_RENDERER_TCPDF);
        Settings::setPdfRendererPath(base_path('vendor/tecnickcom/tcpdf'));

        $phpWord = IOFactory::load($filePath, 'Word2007');
        $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');

        $fileName = 'output_' . time() . '.pdf';

        // Output file path
        $outputPdfPath = storage_path('app/public/filled_templates/' . $fileName);

        // Save as PDF
        $pdfWriter->save($outputPdfPath);

        // Dapatkan URL file dari storage
        $pdfUrl = asset('storage/filled_templates/' . $fileName);

        return $pdfUrl;
    }

    /**
     * Menggabungkan pengisian template dan konversi ke PDF.
     *
     * @param string $templateName Nama template yang disimpan dalam storage.
     * @param array $data Data yang akan diisi ke dalam template.
     * @return path file word.
     */
    public function generatePDFFromTemplate(string $templateName, array $data)
    {
        // 1. Isi template Word dengan data
        $wordFilePath = $this->fillWordTemplate($templateName, $data);

        // 2. Konversi file Word ke PDF
        return $this->convertWordToPDF($wordFilePath);
    }
}
