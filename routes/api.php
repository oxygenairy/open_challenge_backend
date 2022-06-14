<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test', function(){
    return response()->json(
        [
            'Programmer' => 'Oxygen Airy',
            'email' => 'asukuismail2019@gmail.com'
        ]
    );
});


Route::prefix('management')->group(__DIR__.'/api_location/management.php');
Route::prefix('public')->group(__DIR__.'/api_location/public.php');