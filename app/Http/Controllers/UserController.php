<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Update the user's profile information.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Define validation rules
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
        ], [
            'firstName.required' => 'First name is required',
            'firstName.max' => 'First name must not exceed 50 characters',
            'lastName.required' => 'Last name is required',
            'lastName.max' => 'Last name must not exceed 50 characters',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update user information
            $user->update([
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
            ]);
            // $user->update([
            //     'first_name' => $request->firstName,
            //     'last_name' => $request->lastName,
            // ]);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'full_name' => $user->full_name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:8|confirmed',
        ], [
            'currentPassword.required' => 'Current password is required',
            'newPassword.required' => 'New password is required',
            'newPassword.min' => 'New password must be at least 8 characters',
            'newPassword.confirmed' => 'New password confirmation does not match',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if current password matches
        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'currentPassword' => ['The current password is incorrect']
                ]
            ], 422);
        }

        // Check if new password is different from current password
        if (Hash::check($request->newPassword, $user->password)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'newPassword' => ['New password must be different from current password']
                ]
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->newPassword)
            ]);

            return response()->json([
                'message' => 'Password updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update both profile and password in one request.
     */
    public function updateAccount(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Define base validation rules
        $validationRules = [
            'firstName' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
        ];

        $validationMessages = [
            'firstName.required' => 'First name is required',
            'firstName.max' => 'First name must not exceed 50 characters',
            'lastName.required' => 'Last name is required',
            'lastName.max' => 'Last name must not exceed 50 characters',
        ];

        // Check if password change is requested
        $isChangingPassword = $request->filled('currentPassword') || 
                             $request->filled('newPassword') || 
                             $request->filled('confirmPassword');

        if ($isChangingPassword) {
            $validationRules['currentPassword'] = 'required|string';
            $validationRules['newPassword'] = 'required|string|min:8|confirmed';
            
            $validationMessages['currentPassword.required'] = 'Current password is required';
            $validationMessages['newPassword.required'] = 'New password is required';
            $validationMessages['newPassword.min'] = 'New password must be at least 8 characters';
            $validationMessages['newPassword.confirmed'] = 'New password confirmation does not match';
        }

        $validator = Validator::make($request->all(), $validationRules, $validationMessages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Password validation logic if changing password
        if ($isChangingPassword) {
            if (!Hash::check($request->currentPassword, $user->password)) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        'currentPassword' => ['The current password is incorrect']
                    ]
                ], 422);
            }

            if (Hash::check($request->newPassword, $user->password)) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        'newPassword' => ['New password must be different from current password']
                    ]
                ], 422);
            }
        }

        try {
            // Start update data
            $updateData = [
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
            ];

            // Add password to update if changing
            if ($isChangingPassword) {
                $updateData['password'] = Hash::make($request->newPassword);
            }

            // Update user
            $user->update($updateData);

            return response()->json([
                'message' => 'Account updated successfully',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'full_name' => $user->full_name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the current authenticated user's profile.
     */
    public function getProfile(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'full_name' => $user->full_name,
            ]
        ]);
    }
}