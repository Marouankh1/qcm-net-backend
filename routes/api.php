<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChoicesController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Hello world!']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});



//------------------------Question---------------

Route::post('/questions', [QuestionController::class, 'store']);
Route::get('/questions', [QuestionController::class, 'index']);
Route::get('/questions/{id}', [QuestionController::class, 'show']);
Route::put('/questions/{id}', [QuestionController::class, 'update']);
Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);



//------------------------Choices---------------

Route::get('/choices', [ChoicesController::class, 'index']);
Route::get('/choices/{id}', [ChoicesController::class, 'show']);
Route::get('/questions/{questionId}/choices', [ChoicesController::class, 'getByQuestion']);

Route::post('/choices', [ChoicesController::class, 'store']);
Route::put('/choices/{id}', [ChoicesController::class, 'update']);
Route::delete('/choices/{id}', [ChoicesController::class, 'destroy']);
