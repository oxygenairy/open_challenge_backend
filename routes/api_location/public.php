<?php

use App\Http\Controllers\API\Public\AuthController;
use App\Http\Controllers\API\Public\HomeController;
use App\Http\Controllers\API\Public\ChallengeController;
use App\Http\Controllers\API\Public\QuestionController;

/**
* version 1.0
*/

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-account', [AuthController::class, 'verifyAccount']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('new-code', [AuthController::class, 'newCode']);

    Route::middleware(['auth:sanctum'])->group(function () {
        //main menus
        Route::post('user-home', [HomeController::class, 'getHome']);

        //challenges and events related for player 1
        Route::post('challenge-1v1', [ChallengeController::class, 'createOneVOne']);
        Route::post('player1-request', [ChallengeController::class, 'plONE_request']);
        Route::post('player1-response', [ChallengeController::class, 'plONE_response']);
        Route::post('challenge-1v1-next', [ChallengeController::class, 'pl_GetNext']);

        //challenges and events related for player 2
        Route::post('challenge-1v1-2', [ChallengeController::class, 'acceptOneVOne']);
        Route::post('player2-request', [ChallengeController::class, 'plTWO_request']);
        Route::post('player2-response', [ChallengeController::class, 'plTWO_response']);
        Route::post('challenge-1v1-next', [ChallengeController::class, 'pl_GetNext']);

        //question processing

       
    
    });