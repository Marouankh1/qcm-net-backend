<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    // GET /api/quizzes
    public function index()
    {
        $quizzes = Quiz::with('teacher:id,first_name,last_name,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($quizzes);
    }

    //  GET /api/quizzes/{id}
    public function show($id)
    {
        $quiz = Quiz::with([
            'teacher:id,first_name,last_name',
            'questions.choices'
        ])->find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        return response()->json($quiz);
    }

    //  POST /api/quizzes
    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id'  => 'required|integer|exists:users,id',
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
            'message' => 'Quiz created successfully',
            'quiz' => $quiz
        ], 201);
    }

    //  PUT /api/quizzes/{id}
    public function update(Request $request, $id)
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $quiz->update($validated);

        return response()->json([
            'message' => 'Quiz updated successfully',
            'quiz' => $quiz
        ]);
    }

    //  DELETE /api/quizzes/{id}
    public function destroy($id)
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted successfully']);
    }

    //  PATCH /api/quizzes/{id}  -> publish/unpublish
    public function publish(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);

        $validated = $request->validate([
            'is_published' => 'required|boolean',
        ]);

        $quiz->update([
            'is_published' => $validated['is_published'],
        ]);

        return response()->json([
            'message' => 'Quiz publication status updated successfully',
            'quiz' => $quiz
        ]);
    }

}
