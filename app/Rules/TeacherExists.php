<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TeacherExists implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $teacher = User::where('id', $value)
                      ->where('role', 'teacher')
                      ->exists();

        if (!$teacher) {
            $fail('The selected teacher must be a user with teacher role.');
        }
    }
}