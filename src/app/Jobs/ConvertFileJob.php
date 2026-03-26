<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Conversion;
use Illuminate\Support\Facades\Log;

class ConvertFileJob implements ShouldQueue
{
    use Queueable;

    public int $conversionId;

    public function __construct(int $conversionId)
    {
        $this->conversionId = $conversionId;
    }

    public function handle(): void
    {
        $conversion = Conversion::find($this->conversionId);

        if (!$conversion) {
            Log::error("Conversion not found: " . $this->conversionId);
            return;
        }

        try {
            $conversion->update(['status' => 'processing']);

            $input = storage_path('app/input/' . $conversion->input_file);
            $outputDir = storage_path('app/output');

            // ✅ Check file tồn tại
            if (!file_exists($input)) {
                throw new \Exception("Input file not found: " . $input);
            }

            // ✅ Tạo thư mục output
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            // ✅ FIX QUAN TRỌNG: LibreOffice trong Docker
            $profileDir = '/tmp/libreoffice-profile';

            if (!file_exists($profileDir)) {
                mkdir($profileDir, 0777, true);
            }

            // ✅ Command chuẩn production
            $cmd = "HOME=/tmp /usr/bin/libreoffice --headless --nologo --nofirststartwizard "
                . "-env:UserInstallation=file://{$profileDir} "
                . "--convert-to docx "
                . escapeshellarg($input)
                . " --outdir "
                . escapeshellarg($outputDir);

            Log::info("Running command: " . $cmd);

            exec($cmd . " 2>&1", $out, $code);

            Log::info("LibreOffice output: " . implode("\n", $out));

            // ❗ Check kỹ hơn (tránh fake DONE)
            if ($code !== 0 || empty($out)) {
                throw new \Exception("Convert failed: " . implode("\n", $out));
            }

            // ✅ Xác định file output
            $outputFile = pathinfo($conversion->input_file, PATHINFO_FILENAME) . '.docx';
            $outputPath = $outputDir . '/' . $outputFile;

            // ❗ Check file thực sự được tạo
            if (!file_exists($outputPath)) {
                throw new \Exception("Output file not found after convert");
            }

            $conversion->update([
                'status' => 'done',
                'output_file' => $outputFile
            ]);

        } catch (\Exception $e) {
            Log::error("Convert error: " . $e->getMessage());

            $conversion->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}