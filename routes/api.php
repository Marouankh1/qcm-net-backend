<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Hello world!']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});