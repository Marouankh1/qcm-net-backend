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
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    // Relation au singulier (un attempt a un résultat)
    public function studentResult()
    {
        return $this->hasOne(StudentResult::class, 'attempt_id');
    }

    // Relation au pluriel (pour d'autres usages)
    public function studentResults()
    {
        return $this->hasMany(StudentResult::class, 'attempt_id');
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class, 'student_id', 'student_id')
            ->whereHas('question', function($query) {
                $query->where('quiz_id', $this->quiz_id);
            });
    }
}