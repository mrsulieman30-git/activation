<?php

use App\Http\Controllers\Api\ActivationApiController;
use App\Http\Controllers\Api\RegistrationApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('registration')->group(function () {
    Route::post('/request', [RegistrationApiController::class, 'request']);
});

Route::prefix('activation')->group(function () {
    Route::post('/request', [ActivationApiController::class, 'requestActivation']);
    Route::get('/status/{requestId}', [ActivationApiController::class, 'checkStatus']);
    Route::post('/validate', [ActivationApiController::class, 'validateLicense']);
});
