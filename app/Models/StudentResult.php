<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'quiz_id',
        'attempt_id',
        'final_score',
        'total_questions',
        'correct_answers',
        'completed_at',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    // Helpers
    public function getPercentageAttribute()
    {
        return $this->total_questions > 0 
            ? ($this->correct_answers / $this->total_questions) * 100 
            : 0;
    }

    public function getGradeAttribute()
    {
        $percentage = $this->percentage;

        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }

    public function studentResults()
    {
        return $this->hasMany(StudentResult::class, 'quiz_id');
    }
}