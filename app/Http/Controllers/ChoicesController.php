<?php
// app/Http/Controllers/ChoiceController.php
namespace App\Http\Controllers;

use App\Models\Choice;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ChoicesController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Vérification détaillée de l'authentification
        // if (!auth()->check()) {
        //     \Log::warning('Unauthenticated access attempt to store choice');
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthenticated - Please log in first',
        //         'auth_check' => auth()->check(),
        //         'user_id' => auth()->id()
        //     ], Response::HTTP_UNAUTHORIZED);
        // }

        try {
            $validator = Validator::make($request->all(), [
                'question_id' => 'required|exists:questions,id',
                'choice_text' => 'required|string|max:500',
                'is_correct' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $question = Question::with('quiz')->find($request->question_id);

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$question->quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found for this question'
                ], Response::HTTP_NOT_FOUND);
            }

            \Log::info('Choice creation attempt', [
                'teacher_id' => auth()->id(),
                'quiz_teacher_id' => $question->quiz->teacher_id,
                'question_id' => $question->id
            ]);

            // if ($question->quiz->teacher_id != auth()->id()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Unauthorized - You are not the owner of this quiz',
            //         'debug' => [
            //             'authenticated_teacher_id' => auth()->id(),
            //             'quiz_teacher_id' => $question->quiz->teacher_id,
            //             'question_id' => $question->id,
            //             'quiz_id' => $question->quiz->id
            //         ]
            //     ], Response::HTTP_FORBIDDEN);
            // }

            $choice = Choice::create([
                'question_id' => $request->question_id,
                'choice_text' => $request->choice_text,
                'is_correct' => $request->is_correct
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Choice created successfully',
                'data' => $choice
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            \Log::error('Choice creation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function index(): JsonResponse
    {
        try {
            $choices = Choice::with('question.quiz')->get();

            return response()->json([
                'success' => true,
                'message' => 'Choices retrieved successfully',
                'data' => $choices
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $choice = Choice::with('question.quiz')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Choice retrieved successfully',
                'data' => $choice
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Choice not found'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id): JsonResponse
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
                ], Response::HTTP_BAD_REQUEST);
            }

            $jsonRequest = new \Illuminate\Http\Request();
            $jsonRequest->replace($requestData);

            $validator = Validator::make($jsonRequest->all(), [
                'choice_text' => 'sometimes|required|string|max:500',
                'is_correct' => 'sometimes|required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Vérifier l'authentification
            // if (!auth()->check()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Unauthenticated - Please log in first'
            //     ], Response::HTTP_UNAUTHORIZED);
            // }

            $choice = Choice::with('question.quiz')->find($id);

            if (!$choice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Choice not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier la propriété
            // if ($choice->question->quiz->teacher_id !== auth()->id()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Unauthorized - You are not the owner of this choice'
            //     ], Response::HTTP_FORBIDDEN);
            // }

            $changes = false;

            if (isset($requestData['choice_text']) && $requestData['choice_text'] !== $choice->choice_text) {
                $choice->choice_text = $requestData['choice_text'];
                $changes = true;
            }

            if (isset($requestData['is_correct'])) {
                $newIsCorrect = (bool) $requestData['is_correct'];
                if ($newIsCorrect !== $choice->is_correct) {
                    $choice->is_correct = $newIsCorrect;
                    $changes = true;
                }
            }

            if ($changes) {
                $choice->save();
                $message = 'Choice updated successfully';
            } else {
                $message = 'No changes detected';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $choice->fresh(),
                'debug' => [
                    'content_type_received' => $contentType,
                    'data_parsed' => $requestData,
                    'changes_made' => $changes,
                    'fields_updated' => [
                        'choice_text' => isset($requestData['choice_text']) && $requestData['choice_text'] !== $choice->getOriginal('choice_text'),
                        'is_correct' => isset($requestData['is_correct']) && (bool) $requestData['is_correct'] !== $choice->getOriginal('is_correct')
                    ]
                ]
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Choice not found'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            \Log::error('Choice update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $choice = Choice::with('question.quiz')->findOrFail($id);

            // if ($choice->question->quiz->teacher_id !== auth()->id()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Unauthorized - You are not the owner of this choice'
            //     ], Response::HTTP_FORBIDDEN);
            // }

            $choice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Choice deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Choice not found'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getByQuestion($questionId): JsonResponse
    {
        try {
            $choices = Choice::where('question_id', $questionId)->get();

            return response()->json([
                'success' => true,
                'message' => 'Question choices retrieved successfully',
                'data' => $choices
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
