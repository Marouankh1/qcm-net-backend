<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use App\Rules\TeacherExists;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;

class QuizController extends Controller
{
    // GET /api/quizzes
    public function index()
    {
        try{
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please log in.'
                ], 401);
            }

            $teacherId = Auth::user()->id;
            
            $quizzes = Quiz::with('teacher:id,first_name,last_name,email')
                ->where('teacher_id', $teacherId) // Add this filter
                ->orderByDesc('created_at')
                ->get();

            return response()->json($quizzes);
        }
        catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong. Please try again in a few moments.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    //  GET /api/quizzes/{id}
    public function show($id)
    {
        try{
            $quiz = Quiz::with([
                'teacher:id,first_name,last_name',
                'questions.choices'
            ])->find($id);

            if (!$quiz) {
                return response()->json(['message' => 'Quiz not found'], 404);
            }

            return response()->json($quiz);
        }
        catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token .',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong. Please try again in a few moments.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    //  POST /api/quizzes
    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'teacher_id'  => [
                    'required',
                    'integer',
                    'exists:users,id',
                    new TeacherExists
                ],
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $quiz = Quiz::create([
                'teacher_id'  => $validated['teacher_id'],
                'title'       => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_published'=> false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quiz created successfully',
                'data' => [
                    'quiz' => $quiz
                ]
            ], 201);
        }
        catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $e->errors()
            ], 422);
        }
        catch (JWTException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create token .',
                    'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
                ], 500);
        }
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create quiz',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    //  PUT /api/quizzes/{id}
    public function update(Request $request, $id)
    {
        try{
            $quiz = Quiz::find($id);

            if (!$quiz) {
                return response()->json(['success' => false,'message' => 'Quiz not found'], 404);
            }

            $validated = $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $quiz->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Quiz updated successfully',
                'data' => [
                    'quiz' => $quiz
                ]
            ]);
        }
        catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token .',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong. Please try again in a few moments.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    //  DELETE /api/quizzes/{id}
    public function destroy($id)
    {
        try{
            $quiz = Quiz::find($id);

            if (!$quiz) {
                return response()->json(['success' => false,'message' => 'Quiz not found'], 404);
            }

            $quiz->delete();

            return response()->json(['success' => true,'message' => 'Quiz deleted successfully']);
        }
        catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token .',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong. Please try again in a few moments.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    //  PATCH /api/quizzes/{id}  -> publish/unpublish
    public function publish(Request $request, $id)
    {
        try{
            $quiz = Quiz::find($id);

            if (!$quiz) {
                return response()->json(['success' => false,'message' => 'Quiz not found'], 404);
            }

            $validated = $request->validate([
                'is_published' => 'required|boolean',
            ]);

            $quiz->update([
                'is_published' => $validated['is_published'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quiz publication status updated successfully',
                'data' => [
                    'quiz' => $quiz
                ]
            ]);
        }
        catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token .',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong. Please try again in a few moments.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
