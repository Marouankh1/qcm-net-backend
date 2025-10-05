<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'points',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    // Relationships
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function choices()
    {
        return $this->hasMany(Choice::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    // Helpers
    public function getCorrectChoicesAttribute()
    {
        return $this->choices()->where('is_correct', true)->get();
    }

    public function hasMultipleCorrectAnswers()
    {
        return $this->choices()->where('is_correct', true)->count() > 1;
    }
}