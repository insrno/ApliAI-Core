<?php

use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\JobDescriptionController;
use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/resumes', [ResumeController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::post('/job-descriptions', [JobDescriptionController::class, 'store']);

    Route::post('/evaluate', [EvaluationController::class, 'evaluate'])
        ->middleware('throttle:5,1');

    // Get available AI providers
    Route::get('/providers', [EvaluationController::class, 'providers']);
});
