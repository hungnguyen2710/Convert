<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Conversion;

class ConvertFileJob implements ShouldQueue
{
    use Queueable;

    public $conversion;

    /**
     * Create a new job instance.
     */
    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->conversion->update(['status' => 'processing']);

            $input = storage_path('app/input/' . $this->conversion->input_file);
            $outputDir = storage_path('app/output');

            $cmd = "libreoffice --headless --convert-to docx {$input} --outdir {$outputDir}";
            exec($cmd, $out, $code);

            if ($code !== 0) {
                throw new \Exception("Convert failed");
            }

            $outputFile = pathinfo($this->conversion->input_file, PATHINFO_FILENAME) . '.docx';

            $this->conversion->update([
                'status' => 'done',
                'output_file' => $outputFile
            ]);

        } catch (\Exception $e) {
            $this->conversion->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}
