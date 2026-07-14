<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/storage/{path}', function ($path) {
    $absolutePath = storage_path('app/public/' . $path);
    
    if (!file_exists($absolutePath)) {
        abort(404);
    }
    
    return response()->file($absolutePath);
})->where('path', '.*');