<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'quiz_id',
        'attempt_date',
    ];

    protected $casts = [
        'attempt_date' => 'datetime',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function studentResults()
    {
        return $this->hasOne(StudentResult::class, 'attempt_id');
    }

    public function studentAnswers()
    {
        return $this->hasManyThrough(
            StudentAnswer::class,
            StudentResult::class,
            'attempt_id',
            'student_id',
            'id',
            'student_id'
        );
    }
}