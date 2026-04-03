<?php

use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

// Endpoint for uploading a new resume
Route::post('/resumes', [ResumeController::class, 'store']);
