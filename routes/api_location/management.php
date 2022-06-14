<?php

use App\Http\Controllers\API\Public\AuthController;
use App\Http\Controllers\API\Public\HomeController;
use App\Http\Controllers\API\Management\QuestionController;
use App\Http\Controllers\API\Public\ChallengeController;

/**
* version 1.0
*/

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('new-code', [AuthController::class, 'newCode']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('add-question', [QuestionController::class, 'addQuestion']);
    
    });