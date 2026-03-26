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

            if (!file_exists($input)) {
                throw new \Exception("Input file not found: " . $input);
            }

            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $outputFile = pathinfo($conversion->input_file, PATHINFO_FILENAME) . '.docx';
            $outputPath = $outputDir . '/' . $outputFile;

            // 🔥 COMMAND MỚI (pdf2docx)
            $cmd = "python3 -m pdf2docx convert "
                . escapeshellarg($input) . " "
                . escapeshellarg($outputPath);

            Log::info("Running command: " . $cmd);

            exec($cmd . " 2>&1", $out, $code);

            Log::info("pdf2docx output: " . implode("\n", $out));

            if ($code !== 0 || !file_exists($outputPath)) {
                throw new \Exception("Convert failed: " . implode("\n", $out));
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