<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WEB\Public\FrontController;
use App\Http\Controllers\WEB\Public\WebAuthController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('test', function () {
    return view('welcome');
}); 


Route::get('/',  [FrontController::class, 'Junction']);
Route::get('login',  [FrontController::class, 'Junction']);
Route::get('register',  [FrontController::class, 'Junction']);
Route::get('register/ref={refid}',  function($referal = 'refid'){
    [WebAuthjController::class, 'register'];
});
Route::get('forgot-password',  [FrontController::class, 'Junction']);
Route::get('verify-account',  [FrontController::class, 'Junction']);


