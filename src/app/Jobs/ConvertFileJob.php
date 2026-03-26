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

    /**
     * Create a new job instance.
     */
    public function __construct(int $conversionId)
    {
        $this->conversionId = $conversionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $conversion = Conversion::find($this->conversionId);

        if (!$conversion) {
            Log::error("Conversion not found: " . $this->conversionId);
            return;
        }

        try {
            // Update status
            $conversion->update(['status' => 'processing']);

            $input = storage_path('app/input/' . $conversion->input_file);
            $outputDir = storage_path('app/output');

            // Check file tồn tại
            if (!file_exists($input)) {
                throw new \Exception("Input file not found: " . $input);
            }

            // Tạo folder output nếu chưa có
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            // Command convert (an toàn)
            $cmd = "libreoffice --headless --nologo --nofirststartwizard --convert-to docx "
                . escapeshellarg($input)
                . " --outdir "
                . escapeshellarg($outputDir);

            Log::info("Running command: " . $cmd);

            exec($cmd, $out, $code);

            // Log output
            Log::info("LibreOffice output: " . implode("\n", $out));

            if ($code !== 0) {
                throw new \Exception("Convert failed: " . implode("\n", $out));
            }

            // Tạo tên file output
            $outputFile = pathinfo($conversion->input_file, PATHINFO_FILENAME) . '.docx';

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