<?php

use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\JobDescriptionController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\TipsController;
use App\Http\Controllers\KeywordsController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/resumes', [ResumeController::class, 'store'])
        ->middleware('throttle:30,1');

    Route::post('/job-descriptions', [JobDescriptionController::class, 'store']);

    Route::post('/evaluate', [EvaluationController::class, 'evaluate'])
        ->middleware('throttle:15,1');

    // Get available AI providers
    Route::get('/providers', [EvaluationController::class, 'providers']);

    // ATS Keywords and Tips
    Route::post('/keywords', [KeywordsController::class, 'check']);
    Route::post('/tips', [TipsController::class, 'generate'])
        ->middleware('throttle:15,1');
});
