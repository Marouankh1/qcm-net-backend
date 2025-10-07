<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|max:255|confirmed',
                'role' => 'required|string|in:admin,teacher,student',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);
            try {
                $token = JWTAuth::fromUser($user);
            
                // Set HTTP-only cookie
                $cookie = cookie(
                    'token', 
                    $token,
                    config('jwt.ttl'), // 1 hour
                    '/',
                    null, 
                    true, // secure
                    true, // httpOnly
                    false, 
                    'lax'
                );

            } catch (JWTException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create token .',
                    'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
        
            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'created_at' => $user->created_at,
                    ]
                ]
            ], Response::HTTP_CREATED)->withCookie($cookie);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong. Please try again in a few moments.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function login(Request $request)
    {   
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $credentials = $request->only('email', 'password');

            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid credentials',
                        // 'error' => ''
                    ], Response::HTTP_UNAUTHORIZED);
                }
            } catch (JWTException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create token .',
                    'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
            
            $user = Auth::user();
            
            // Set HTTP-only cookie
            $cookie = cookie(
                'token', 
                $token,
                config('jwt.ttl'), // 1 hour
                '/',
                null, 
                true, // secure
                true, // httpOnly
                false, 
                'lax'
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ]
                ]
            ], Response::HTTP_OK)->withCookie($cookie);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, something went wrong. Please try again in a few moments.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function logout()
    {
        try {
           JWTAuth::invalidate(JWTAuth::getToken());
            
            // Clear the cookie
            $cookie = cookie()->forget('token');
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ])->withCookie($cookie);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout, please try again',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function user(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch user data'
            ], 500);
        }
    }
}
