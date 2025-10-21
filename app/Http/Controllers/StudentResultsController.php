<?php
// app/Http/Controllers/StudentResultsController.php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\User;
use App\Models\StudentResult;
use App\Models\StudentAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentResultsController extends Controller
{
    // Liste de tous les étudiants avec leurs statistiques globales
    public function getStudentsStats(Request $request): JsonResponse
    {
        try {
            $teacherId = auth()->id();
            $search = $request->get('search', '');
            $quizId = $request->get('quiz_id', '');

            // Modifier la requête pour ne prendre que les étudiants qui ont des résultats pour les quizzes du prof
            $query = User::where('role', 'student')
                ->whereHas('studentResults', function($query) use ($teacherId) {
                    $query->whereHas('quiz', function($q) use ($teacherId) {
                        $q->where('teacher_id', $teacherId);
                    });
                })
                ->withCount(['studentResults as quizzes_attempted' => function($query) use ($teacherId) {
                    $query->whereHas('quiz', function($q) use ($teacherId) {
                        $q->where('teacher_id', $teacherId);
                    });
                }])
                ->withAvg(['studentResults as average_score' => function($query) use ($teacherId) {
                    $query->whereHas('quiz', function($q) use ($teacherId) {
                        $q->where('teacher_id', $teacherId);
                    })->whereNotNull('completed_at');
                }], 'final_score')
                ->with(['studentResults' => function($query) use ($teacherId) {
                    $query->whereHas('quiz', function($q) use ($teacherId) {
                        $q->where('teacher_id', $teacherId);
                    })->with('quiz')->latest()->take(3);
                }]);

            // Filtre par recherche
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Filtre par quiz
            if (!empty($quizId) && $quizId !== 'all') {
                $query->whereHas('studentResults', function($q) use ($quizId) {
                    $q->where('quiz_id', $quizId);
                });
            }

            $students = $query->orderBy('first_name')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $students
            ]);

        } catch (\Exception $e) {
            \Log::error('Get students stats error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students statistics',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // Détails d'un étudiant spécifique
    public function getStudentDetail($studentId): JsonResponse
    {
        try {
            $teacherId = auth()->id();

            $student = User::where('id', $studentId)
                ->where('role', 'student')
                ->with(['studentResults' => function($query) use ($teacherId) {
                    $query->whereHas('quiz', function($q) use ($teacherId) {
                        $q->where('teacher_id', $teacherId);
                    })
                    ->with('quiz')
                    ->orderBy('completed_at', 'desc');
                }])
                ->firstOrFail();

            // Statistiques globales de l'étudiant
            $globalStats = StudentResult::where('student_id', $studentId)
                ->whereHas('quiz', function($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })
                ->select([
                    DB::raw('COUNT(*) as total_quizzes_attempted'),
                    DB::raw('AVG(final_score) as average_score'),
                    DB::raw('MAX(final_score) as best_score'),
                    DB::raw('COUNT(CASE WHEN final_score >= 80 THEN 1 END) as excellent_results'),
                    DB::raw('COUNT(CASE WHEN final_score BETWEEN 60 AND 79 THEN 1 END) as good_results'),
                    DB::raw('COUNT(CASE WHEN final_score < 60 THEN 1 END) as needs_improvement')
                ])
                ->first();

            // Progression dans le temps
            $progressData = StudentResult::where('student_id', $studentId)
                ->whereHas('quiz', function($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })
                ->whereNotNull('completed_at')
                ->select([
                    DB::raw('DATE(completed_at) as date'),
                    DB::raw('AVG(final_score) as average_score'),
                    DB::raw('COUNT(*) as quizzes_count')
                ])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'global_stats' => $globalStats,
                    'progress_data' => $progressData
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Get student detail error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load student details',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // Résultats détaillés d'un quiz spécifique pour un étudiant
    public function getQuizResults($studentId, $quizId): JsonResponse
    {
        try {
            $teacherId = auth()->id();

            $results = StudentResult::where('student_id', $studentId)
                ->where('quiz_id', $quizId)
                ->whereHas('quiz', function($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })
                ->with([
                    'student', // AJOUTER CETTE LIGNE pour charger les infos de l'étudiant
                    'quiz.questions.choices', 
                    'quiz.questions.studentAnswers' => function($query) use ($studentId) {
                        $query->where('student_id', $studentId);
                    }
                ])
                ->firstOrFail();

            // Détails des réponses
            $answerDetails = StudentAnswer::where('student_id', $studentId)
                ->whereHas('question', function($query) use ($quizId) {
                    $query->where('quiz_id', $quizId);
                })
                ->with(['question', 'choice'])
                ->get()
                ->groupBy('question.id');

            return response()->json([
                'success' => true,
                'data' => [
                    'result' => $results,
                    'answer_details' => $answerDetails
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Get quiz results error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quiz results',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // Statistiques par quiz
    public function getQuizStats(): JsonResponse
    {
        try {
            $teacherId = auth()->id();

            $quizStats = Quiz::where('teacher_id', $teacherId)
                ->withCount(['results as attempts_count'])
                ->withAvg(['results as average_score' => function($query) {
                    $query->whereNotNull('completed_at');
                }], 'final_score')
                ->with(['results' => function($query) {
                    $query->whereNotNull('completed_at')
                          ->orderBy('final_score', 'desc')
                          ->take(5)
                          ->with('student');
                }])
                ->get()
                ->map(function($quiz) {
                    return [
                        'id' => $quiz->id,
                        'title' => $quiz->title,
                        'attempts_count' => $quiz->attempts_count,
                        'average_score' => round($quiz->average_score ?? 0, 2),
                        'top_performers' => $quiz->results->map(function($result) {
                            return [
                                'student_name' => $result->student->first_name . ' ' . $result->student->last_name,
                                'score' => $result->final_score,
                                'completed_at' => $result->completed_at
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $quizStats
            ]);

        } catch (\Exception $e) {
            \Log::error('Get quiz stats error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quiz statistics',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}