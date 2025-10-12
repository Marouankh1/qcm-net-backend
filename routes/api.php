<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChoicesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\StudentResultsController;
use App\Http\Controllers\UserController;    

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('jwt')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Hello world!']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard/stats', [DashboardController::class, 'teacherStats']);

    Route::apiResource('quizzes', QuizController::class);
    Route::patch('/quizzes/{id}/publish', [QuizController::class, 'publish']);

    Route::apiResource('questions', QuestionController::class);
    Route::get('/questions/quiz/{quizId}', [QuestionController::class, 'getByQuiz']); 

    Route::apiResource('choices', ChoicesController::class);
    Route::get('/questions/{questionId}/choices', [ChoicesController::class, 'getByQuestion']);

    // Dans le groupe middleware 'jwt'
    Route::prefix('student-results')->group(function () {
        Route::get('/', [StudentResultsController::class, 'getStudentsStats']);
        Route::get('/quizzes-stats', [StudentResultsController::class, 'getQuizStats']);
        Route::get('/student/{studentId}', [StudentResultsController::class, 'getStudentDetail']);
        Route::get('/student/{studentId}/quiz/{quizId}', [StudentResultsController::class, 'getQuizResults']);
    });
    
    Route::get('/profile', [UserController::class, 'getProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/password', [UserController::class, 'updatePassword']);
    Route::put('/account', [UserController::class, 'updateAccount']);
});



// //------------------------Question---------------

// Route::post('/questions', [QuestionController::class, 'store']);
// Route::get('/questions', [QuestionController::class, 'index']);
// Route::get('/questions/{id}', [QuestionController::class, 'show']);
// Route::put('/questions/{id}', [QuestionController::class, 'update']);
// Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);



// //------------------------Choices---------------

// Route::get('/choices', [ChoicesController::class, 'index']);
// Route::get('/choices/{id}', [ChoicesController::class, 'show']);
// Route::get('/questions/{questionId}/choices', [ChoicesController::class, 'getByQuestion']);

// Route::post('/choices', [ChoicesController::class, 'store']);
// Route::put('/choices/{id}', [ChoicesController::class, 'update']);
// Route::delete('/choices/{id}', [ChoicesController::class, 'destroy']);
// // Route::apiResource('quizzes', QuizController::class);


// Route::prefix('quizzes')->group(function () {
//         Route::get('/',        [QuizController::class, 'index']);    // Read all
//         Route::get('/{id}',    [QuizController::class, 'show']);     // Read one 
//         Route::post('/',       [QuizController::class, 'store']);    // Create
//         Route::put('/{id}',    [QuizController::class, 'update']);   // Update title & description
//         Route::delete('/{id}', [QuizController::class, 'destroy']);  // Delete
//         Route::patch('/{id}/publish',  [QuizController::class, 'publish']);  // Update is_published
//     });