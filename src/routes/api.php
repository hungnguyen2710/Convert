<?php
use App\Http\Controllers\ConvertController;

Route::post('/convert/init', [ConvertController::class, 'init']);
Route::get('/convert/status/{id}', [ConvertController::class, 'status']);
Route::get('/convert/download/{id}', [ConvertController::class, 'download']);
