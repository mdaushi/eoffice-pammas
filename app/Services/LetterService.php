<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\TemplateProcessor;
// Import kelas-kelas yang dibutuhkan dari BaconQrCode v3
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;

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
            "waktu_sekarang" => Carbon::now()->format("d M Y"),
            "nomor_surat" => $record->number
        ];

        // Buat instance TemplateProcessor
        $templateProcessor = new TemplateProcessor($templatePath);

        if ($record->letter_request->status === "Selesai") {
            // Generate QR Code dan simpan sebagai gambar
            $qrCodePath = $this->generateQRCodeWithBaconV3($record);

            // Isi template dengan QR Code sebagai gambar
            if ($qrCodePath && file_exists($qrCodePath)) {
                $templateProcessor->setImageValue('qrcode', [
                    'path' => $qrCodePath,
                    'width' => 100,
                    'height' => 100,
                    'ratio' => false
                ]);
            }
        } else {
            $data['qrcode'] = "Belum tertanda tangan";
        }


        // Isi template dengan data lainnya
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // Simpan hasilnya ke file baru
        $outputFileName = "filled_{$record->id}" . ".docx";
        $outputFilePath = Storage::path("filled_templates/{$outputFileName}");

        // Pastikan direktori ada
        if (!Storage::exists('filled_templates')) {
            Storage::makeDirectory('filled_templates');
        }

        $templateProcessor->saveAs($outputFilePath);

        return $outputFilePath;
    }

    /**
     * Menghasilkan QR Code menggunakan BaconQrCode versi 3.0.
     *
     * @param mixed $record Record surat.
     * @return string Path file QR Code atau false jika gagal.
     */
    private function generateQRCodeWithBaconV3($record)
    {
        try {
            $qrcodeDate = Carbon::parse($record->sign_at)->format("d M Y");
            // Data yang akan dimasukkan ke QR Code
            $qrCodeData = "Ditanda tangani pada tanggal {$qrcodeDate} dengan Nomor Surat: {$record->number}";

            // Path untuk menyimpan QR Code
            $qrCodePath = storage_path("app/temp_qrcode_{$record->id}.png");

            // Pastikan direktori ada
            if (!file_exists(dirname($qrCodePath))) {
                mkdir(dirname($qrCodePath), 0755, true);
            }

            // Buat renderer dengan ukuran 300x300 piksel
            $renderer = new ImageRenderer(
                new RendererStyle(100, 0), // Size 300, margin 4
                new ImagickImageBackEnd()  // Gunakan Imagick backend
            );

            // Buat writer dengan renderer yang telah dikonfigurasi
            $writer = new Writer($renderer);

            // Tulis QR code ke file
            $writer->writeFile($qrCodeData, $qrCodePath);

            return $qrCodePath;
        } catch (\Exception $e) {
            // Log error
            Log::error("Error generating QR Code with BaconQrCode v3: " . $e->getMessage());

            // Fallback ke Google Chart API jika BaconQrCode gagal
            return $this->generateQRCodeWithGoogleAPI($record);
        }
    }

    /**
     * Menghasilkan QR Code menggunakan Google Chart API sebagai fallback.
     *
     * @param mixed $record Record surat.
     * @return string Path file QR Code atau false jika gagal.
     */
    private function generateQRCodeWithGoogleAPI($record)
    {
        try {
            // Data yang akan dimasukkan ke QR Code
            $qrCodeData = "Nomor Surat: {$record->number}, ID: {$record->id}";

            // Path untuk menyimpan QR Code
            $qrCodePath = storage_path("app/temp_qrcode_{$record->id}.png");

            // URL Google Chart API untuk QR Code
            $googleChartUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrCodeData);

            // Ambil gambar QR Code dari Google Chart API
            $qrCodeImage = file_get_contents($googleChartUrl);

            if ($qrCodeImage !== false) {
                // Simpan gambar ke file
                file_put_contents($qrCodePath, $qrCodeImage);
                return $qrCodePath;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error generating QR Code with Google Chart API: " . $e->getMessage());
            return false;
        }
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

        // Buat direktori jika belum ada
        if (!file_exists(storage_path('app/public/filled_templates'))) {
            mkdir(storage_path('app/public/filled_templates'), 0755, true);
        }

        $fileName = 'output_' . $record->id . '.pdf';
        $outputPdfPath = storage_path('app/public/filled_templates/' . $fileName);

        // Simpan PDF
        $pdf->Output($outputPdfPath, 'F');

        // Hapus file HTML temporary
        if (file_exists($tempHtmlPath)) {
            unlink($tempHtmlPath);
        }

        // Hapus file QR Code temporary
        $qrCodePath = storage_path("app/temp_qrcode_{$record->id}.png");
        if (file_exists($qrCodePath)) {
            unlink($qrCodePath);
        }

        return asset('storage/filled_templates/' . $fileName);
    }

    /**
     * Menggabungkan pengisian template dan konversi ke PDF.
     *
     * @param mixed $record data letter.
     * @return string Path file PDF.
     */
    public function generatePDFFromTemplate($record)
    {
        // 1. Isi template Word dengan data
        $wordFilePath = $this->fillWordTemplate($record);

        // 2. Konversi file Word ke PDF
        return $this->convertWordToPDF($wordFilePath, $record);
    }
}
