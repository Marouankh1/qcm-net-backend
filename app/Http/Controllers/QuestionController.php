<?php
// app/Http/Controllers/QuestionController.php
namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Response;
use Validator;

class QuestionController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ], 400);
            }

            if (!isset($data['quiz_id'], $data['question_text'], $data['question_type'], $data['points'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 422);
            }

            $question = new Question();
            $question->quiz_id = $data['quiz_id'];
            $question->question_text = $data['question_text'];
            $question->question_type = $data['question_type'];
            $question->points = $data['points'];
            $question->save();

            return response()->json([
                'success' => true,
                'message' => 'Question created',
                'data' => $question
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    private function parseRequestData($request)
    {
        $contentType = $request->header('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            return $request->all();
        }

        $rawContent = $request->getContent();
        if (!empty($rawContent)) {
            $data = json_decode($rawContent, true);
            return json_last_error() === JSON_ERROR_NONE ? $data : [];
        }

        return [];
    }
    public function index()
    {
        $questions = Question::with(['quiz', 'studentAnswers'])->get();
        // $questions = Question::all();
        return response()->json($questions);
        // dd(vars: "wwwww");

    }

    public function show($id)
    {
        $question = Question::with(['quiz', 'studentAnswers'])->findOrFail($id);
        return response()->json($question);
    }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'question_text' => 'sometimes|required|string',
    //         'question_type' => 'sometimes|required|string',
    //         'points' => 'sometimes|required|integer|min:1'
    //     ]);

    //     $question = Question::findOrFail($id);

    //     if ($question->quiz->teacher_id !== auth()->id()) {
    //         return response()->json(['error' => 'Unauthorized'], 403);
    //     }

    //     $question->update($request->all());

    //     return response()->json($question);
    // }
    public function update(Request $request, $id)
    {
        try {
            $contentType = $request->header('Content-Type');
            $requestData = [];

            if (str_contains($contentType, 'application/json')) {
                $requestData = $request->all();
            } else {
                $rawContent = $request->getContent();
                if (!empty($rawContent)) {
                    $requestData = json_decode($rawContent, true) ?? [];
                }
            }

            if (empty($requestData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON valide reçue',
                    'help' => 'Dans Postman: 1) Headers → Content-Type: application/json 2) Body → raw → JSON',
                    'debug' => [
                        'content_type' => $contentType,
                        'raw_content' => $request->getContent(),
                        'parsed_data' => $requestData
                    ]
                ], 400);
            }

            $jsonRequest = new \Illuminate\Http\Request();
            $jsonRequest->replace($requestData);

            $validator = Validator::make($jsonRequest->all(), [
                'question_text' => 'sometimes|required|string',
                'question_type' => 'sometimes|required|string',
                'points' => 'sometimes|required|integer|min:1|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $question = Question::find($id);

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found'
                ], 404);
            }

            $changes = false;

            if (isset($requestData['question_text']) && $requestData['question_text'] !== $question->question_text) {
                $question->question_text = $requestData['question_text'];
                $changes = true;
            }

            if (isset($requestData['points']) && $requestData['points'] != $question->points) {
                $question->points = (int) $requestData['points'];
                $changes = true;
            }

            if (isset($requestData['question_type']) && $requestData['question_type'] !== $question->question_type) {
                $question->question_type = $requestData['question_type'];
                $changes = true;
            }

            if ($changes) {
                $question->save();
                $message = 'Question updated successfully';
            } else {
                $message = 'No changes detected';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $question->fresh(),
                'debug' => [
                    'content_type_received' => $contentType,
                    'data_parsed' => $requestData,
                    'changes_made' => $changes
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function destroy($id)
    {
        \DB::beginTransaction();

        try {
            $question = Question::find($id);

            if (!$question) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found'
                ], 404);
            }

            $quiz = Quiz::find($question->quiz_id);

            if (!$quiz) {
                $question->delete();
                \DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Question deleted (orphaned record)'
                ], 200);

                // \DB::rollBack();
                // return response()->json([
                //     'success' => false,
                //     'message' => 'Quiz not found for this question',
                //     'question_id' => $question->id,
                //     'quiz_id' => $question->quiz_id
                // ], 404);
            }

            if ($quiz->teacher_id !== auth()->id()) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - You are not the owner of this question'
                ], 403);
            }

            $question->delete();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Question deletion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong while deleting the question',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function getByQuiz($quizId): JsonResponse
    {
        try {
            $questions = Question::with('choices')
                ->where('quiz_id', $quizId)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Questions retrieved successfully',
                'data' => $questions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
