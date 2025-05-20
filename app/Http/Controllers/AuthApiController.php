<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthApiController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Session-based login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "password"},
     *             @OA\Property(property="login", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']])) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful',
            'user' => Auth::user(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Register new user (session-based)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "username", "email", "password", "password_confirmation"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Registration successful")
     * )
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'username' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('user');

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout user (session-based)",
     *     @OA\Response(response=200, description="User logged out successfully")
     * )
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}
