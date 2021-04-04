<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\IndexController;
use App\Http\Controllers\ImageController;

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

Route::post('job', [IndexController::class, 'newJob']);
Route::get('job', [IndexController::class, 'getJob']);

Route::get('image', [ImageController::class, 'getImage']);

Route::get('frame', [ImageController::class, 'getFrame']);