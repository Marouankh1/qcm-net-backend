<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuizController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('jwt')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Hello world!']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::apiResource('quizzes', QuizController::class);

Route::prefix('quizzes')->group(function () {
    Route::get('/',        [QuizController::class, 'index']);    // Read all
    Route::get('/{id}',    [QuizController::class, 'show']);     // Read one 
    Route::post('/',       [QuizController::class, 'store']);    // Create
    Route::put('/{id}',    [QuizController::class, 'update']);   // Update title & description
    Route::delete('/{id}', [QuizController::class, 'destroy']);  // Delete
    Route::patch('/{id}/publish',  [QuizController::class, 'publish']);  // Update is_published
});
