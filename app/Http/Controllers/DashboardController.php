<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\User;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentResult;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function teacherStats(): JsonResponse
    {
        try {
            $teacherId = auth()->id();

            // Total Quizzes
            $totalQuizzes = Quiz::where('teacher_id', $teacherId)->count();

            // Active Students (students who have attempted quizzes created by this teacher)
            $activeStudents = StudentAnswer::whereHas('question.quiz', function($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })->distinct('student_id')->count('student_id');

            // Completion Rate - Basé sur les résultats finaux des étudiants
            $completionStats = StudentResult::whereHas('quiz', function($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })->select(
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as completed_attempts')
            )->first();

            $completionRate = $completionStats->total_attempts > 0 
                ? round(($completionStats->completed_attempts / $completionStats->total_attempts) * 100, 2)
                : 0;

            // Average Score - Basé sur les résultats finaux
            $averageScore = StudentResult::whereHas('quiz', function($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->whereNotNull('completed_at')
            ->avg('final_score') ?? 0;

            $averageScore = round($averageScore, 2);

            // Recent Quizzes
            $recentQuizzes = Quiz::where('teacher_id', $teacherId)
                ->withCount('questions')
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get()
                ->map(function($quiz) {
                    return [
                        'title' => $quiz->title,
                        'questions_count' => $quiz->questions_count,
                        'created_at' => $quiz->created_at->diffForHumans(),
                        'created_date' => $quiz->created_at
                    ];
                });

            // Top Performers - Basé sur les résultats finaux
            $topPerformers = StudentResult::select(
                    'users.id',
                    'users.first_name', 
                    'users.last_name',
                    'users.email',
                    DB::raw('ROUND(AVG(student_results.final_score), 2) as average_score')
                )
                ->join('users', 'student_results.student_id', '=', 'users.id')
                ->join('quizzes', 'student_results.quiz_id', '=', 'quizzes.id')
                ->where('quizzes.teacher_id', $teacherId)
                ->whereNotNull('student_results.completed_at')
                ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->orderBy('average_score', 'desc')
                ->limit(3)
                ->get()
                ->map(function($user) {
                    return [
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'average_score' => $user->average_score
                    ];
                });

            // Monthly growth calculations
            $lastMonthQuizzes = Quiz::where('teacher_id', $teacherId)
                ->where('created_at', '>=', now()->subMonth())
                ->count();

            $previousMonthQuizzes = Quiz::where('teacher_id', $teacherId)
                ->whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])
                ->count();

            $quizzesGrowth = $previousMonthQuizzes > 0 
                ? round((($lastMonthQuizzes - $previousMonthQuizzes) / $previousMonthQuizzes) * 100, 2)
                : ($lastMonthQuizzes > 0 ? 100 : 0);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_quizzes' => $totalQuizzes,
                    'active_students' => $activeStudents,
                    'completion_rate' => $completionRate,
                    'average_score' => $averageScore,
                    'recent_quizzes' => $recentQuizzes,
                    'top_performers' => $topPerformers,
                    'growth_metrics' => [
                        'quizzes_growth' => $quizzesGrowth,
                        'last_month_quizzes' => $lastMonthQuizzes,
                        'previous_month_quizzes' => $previousMonthQuizzes
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard stats error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}