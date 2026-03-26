<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversion;
use App\Jobs\ConvertFileJob;

class ConvertController extends Controller
{
    // POST /convert/init
    public function init(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $file = $request->file('file');

        $inputPath = storage_path('app/input');
        if (!file_exists($inputPath)) mkdir($inputPath, 0777, true);

        $filename = uniqid().'_'.$file->getClientOriginalName();
        $file->move($inputPath, $filename);

        $conversion = Conversion::create([
            'input_file' => $filename,
            'status' => 'pending'
        ]);

        ConvertFileJob::dispatch($conversion->id);

        return response()->json([
            'id' => $conversion->id,
            'status' => 'pending'
        ]);
    }

    // GET /convert/status/{id}
    public function status($id)
    {
        $conversion = Conversion::findOrFail($id);

        return response()->json([
            'id' => $conversion->id,
            'status' => $conversion->status,
            'error' => $conversion->error
        ]);
    }

    // GET /convert/download/{id}
    public function download($id)
    {
        $conversion = Conversion::findOrFail($id);

        if ($conversion->status !== 'done') {
            return response()->json([
                'error' => 'File not ready'
            ], 400);
        }

        $filePath = storage_path('app/output/' . $conversion->output_file);

        if (!file_exists($filePath)) {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }

        return response()->download($filePath);
    }
}
