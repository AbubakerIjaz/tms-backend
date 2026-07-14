<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/storage/{path}', function ($path) {
    $absolutePath = storage_path('app/public/' . $path);
    if (!File::exists($absolutePath)) { abort(404); }
    $file = File::get($absolutePath);
    $type = File::mimeType($absolutePath);
    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);
    $response->header("Cache-Control", "public, max-age=86400");
    return $response;
})->where('path', '.*');