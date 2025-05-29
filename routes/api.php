<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Alexrsk\S3DirectUpload\Http\UploadController;
use Alexrsk\S3DirectUpload\Http\ResourceController;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. You're free to add
| as many additional routes to this file as your tool may require.
|
*/

// Route::get('/endpoint', function (Request $request) {
//     //
// });

Route::get('/resource/{resourceName}/{resourceId}/{fieldName}', [ResourceController::class, 'getResource']);
Route::post('/create-chunked-upload', [UploadController::class, 'getChunkedUpload']);
Route::post('/presign-chunked', [UploadController::class, 'presignChunked']);
Route::post('/presign', [UploadController::class, 'presign']);
Route::post('/resource', [ResourceController::class, 'setResource']);
Route::post('/complete', [UploadController::class, 'completeMultipartUpload']);