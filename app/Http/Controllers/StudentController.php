<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use App\Models\StudentResult;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Get available quizzes for students
     */
    public function getAvailableQuizzes(Request $request): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $quizzes = Quiz::with(['teacher:id,first_name,last_name'])
                ->withCount(['questions', 'quizAttempts as participants_count' => function($query) {
                    $query->select(DB::raw('COUNT(DISTINCT student_id)'));
                }])
                ->where('is_published', true)
                ->whereDoesntHave('studentResults', function($query) use ($studentId) {
                    $query->where('student_id', $studentId)
                          ->whereNotNull('completed_at');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $quizzes,
                'message' => 'Available quizzes retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Get available quizzes error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load available quizzes',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get quiz details for student
     */
    public function getQuizDetails($id): JsonResponse
    {
        try {
            $quiz = Quiz::with([
                'teacher:id,first_name,last_name',
                'questions.choices'
            ])
            ->where('is_published', true)
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $quiz,
                'message' => 'Quiz details retrieved successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found or not available'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Get quiz details error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quiz details',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Start a new quiz attempt
     */
    public function startQuizAttempt(Request $request): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $validator = \Validator::make($request->all(), [
                'quiz_id' => 'required|exists:quizzes,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $quiz = Quiz::where('is_published', true)->find($request->quiz_id);
            
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found or not available'
                ], 404);
            }

            // Check if student already has a completed attempt via StudentResult
            $completedAttempt = StudentResult::where('student_id', $studentId)
                ->where('quiz_id', $request->quiz_id)
                ->whereNotNull('completed_at')
                ->first();

            if ($completedAttempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already completed this quiz'
                ], 400);
            }

            // Check if student has an active attempt (QuizAttempt sans StudentResult complété)
            $activeAttempt = QuizAttempt::where('student_id', $studentId)
                ->where('quiz_id', $request->quiz_id)
                ->whereDoesntHave('studentResult', function($query) {
                    $query->whereNotNull('completed_at');
                })
                ->first();

            if ($activeAttempt) {
                return response()->json([
                    'success' => true,
                    'data' => $activeAttempt,
                    'message' => 'Existing attempt found'
                ]);
            }

            // Create new attempt
            $attempt = QuizAttempt::create([
                'student_id' => $studentId,
                'quiz_id' => $request->quiz_id,
                'attempt_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $attempt,
                'message' => 'Quiz attempt started successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Start quiz attempt error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start quiz attempt',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Submit an answer
     */
    public function submitAnswer(Request $request): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $validator = \Validator::make($request->all(), [
                'question_id' => 'required|exists:questions,id',
                'choice_id' => 'required|exists:choices,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $question = Question::find($request->question_id);
            $choice = \App\Models\Choice::find($request->choice_id);

            // Verify the choice belongs to the question
            if ($choice->question_id != $question->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid choice for this question'
                ], 422);
            }

            // Check if answer already exists
            $existingAnswer = StudentAnswer::where('student_id', $studentId)
                ->where('question_id', $request->question_id)
                ->first();

            if ($existingAnswer) {
                // Update existing answer
                $existingAnswer->update([
                    'choice_id' => $request->choice_id,
                    'is_correct' => $choice->is_correct,
                    'answered_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $existingAnswer,
                    'message' => 'Answer updated successfully'
                ]);
            }

            // Create new answer
            $studentAnswer = StudentAnswer::create([
                'student_id' => $studentId,
                'question_id' => $request->question_id,
                'choice_id' => $request->choice_id,
                'is_correct' => $choice->is_correct,
                'answered_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $studentAnswer,
                'message' => 'Answer submitted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Submit answer error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit answer',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Submit quiz attempt and calculate results
     */
    public function submitQuizAttempt($attemptId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $studentId = Auth::id();
            
            $attempt = QuizAttempt::where('student_id', $studentId)
                ->where('id', $attemptId)
                ->firstOrFail();

            $quiz = $attempt->quiz;

            // Get all answers for this attempt
            $answers = StudentAnswer::where('student_id', $studentId)
                ->whereHas('question', function($query) use ($quiz) {
                    $query->where('quiz_id', $quiz->id);
                })
                ->with('question')
                ->get();

            // Calculate results
            $totalQuestions = $quiz->questions()->count();
            $correctAnswers = $answers->where('is_correct', true)->count();
            $finalScore = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

            // Create or update student result
            $studentResult = StudentResult::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'quiz_id' => $quiz->id,
                    'attempt_id' => $attempt->id,
                ],
                [
                    'final_score' => $finalScore,
                    'total_questions' => $totalQuestions,
                    'correct_answers' => $correctAnswers,
                    'completed_at' => now(),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $studentResult,
                'message' => 'Quiz submitted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Attempt not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Submit quiz attempt error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit quiz',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getAttemptAnswers($attemptId): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $attempt = QuizAttempt::where('student_id', $studentId)
                ->where('id', $attemptId)
                ->firstOrFail();

            $answers = StudentAnswer::where('student_id', $studentId)
                ->whereHas('question', function($query) use ($attempt) {
                    $query->where('quiz_id', $attempt->quiz_id);
                })
                ->with('choice')
                ->get(['question_id', 'choice_id', 'is_correct', 'answered_at']);

            return response()->json([
                'success' => true,
                'data' => $answers,
                'message' => 'Attempt answers retrieved successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attempt not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Get attempt answers error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attempt answers',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get student's quiz attempts
     */
    public function getMyAttempts(): JsonResponse
    {
        try {
            $studentId = Auth::id();
            
            $attempts = QuizAttempt::with([
                'quiz.teacher:id,first_name,last_name',
                'studentResult' // Maintenant au singulier
            ])
            ->where('student_id', $studentId)
            ->orderBy('attempt_date', 'desc')
            ->get()
            ->map(function($attempt) {
                return [
                    'id' => $attempt->id,
                    'quiz' => $attempt->quiz,
                    'attempt_date' => $attempt->attempt_date,
                    'completed_at' => $attempt->studentResult->completed_at ?? null, // studentResult au singulier
                    'final_score' => $attempt->studentResult->final_score ?? null,
                    'total_questions' => $attempt->studentResult->total_questions ?? null,
                    'correct_answers' => $attempt->studentResult->correct_answers ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $attempts,
                'message' => 'Quiz attempts retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Get my attempts error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quiz attempts',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get detailed results for a specific attempt
     */
    public function getAttemptResults($attemptId): JsonResponse
    {
        try {
            $studentId = Auth::id();
        
            $attempt = QuizAttempt::with([
                'quiz.questions.choices',
                'studentResult' // Maintenant au singulier
            ])
            ->where('student_id', $studentId)
            ->where('id', $attemptId)
            ->firstOrFail();

            $answers = StudentAnswer::where('student_id', $studentId)
                ->whereHas('question', function($query) use ($attempt) {
                    $query->where('quiz_id', $attempt->quiz_id);
                })
                ->with(['question', 'choice'])
                ->get();

            $questionDetails = $attempt->quiz->questions->map(function($question) use ($answers) {
                $studentAnswer = $answers->where('question_id', $question->id)->first();
                $correctChoice = $question->choices->where('is_correct', true)->first();

                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'points' => $question->points,
                    'student_answer' => $studentAnswer ? [
                        'choice_id' => $studentAnswer->choice_id,
                        'choice_text' => $studentAnswer->choice->choice_text,
                        'is_correct' => $studentAnswer->is_correct
                    ] : null,
                    'correct_answer' => $correctChoice ? [
                        'choice_id' => $correctChoice->id,
                        'choice_text' => $correctChoice->choice_text
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'attempt' => $attempt,
                    'result' => $attempt->studentResult,
                    'question_details' => $questionDetails,
                    'summary' => [
                        'total_questions' => $attempt->studentResult->total_questions ?? 0,
                        'correct_answers' => $attempt->studentResult->correct_answers ?? 0,
                        'final_score' => $attempt->studentResult->final_score ?? 0,
                        'percentage' => $attempt->studentResult->final_score ?? 0
                    ]
                ],
                'message' => 'Attempt results retrieved successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attempt not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Get attempt results error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attempt results',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}