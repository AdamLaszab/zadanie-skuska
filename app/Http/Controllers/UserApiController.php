<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Get list of all users with roles",
     *     @OA\Response(
     *         response=200,
     *         description="List of users returned successfully",
     *     )
     * )
     */
    public function index()
    {
        $users = User::with('roles')->select('id', 'username', 'email', 'first_name', 'last_name')->get();
        return response()->json($users);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{user}/role",
     *     tags={"Users"},
     *     summary="Update user role",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User role updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Role updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$request->role]);

        return response()->json(['message' => 'Role updated successfully.']);
    }

    /**
     * @OA\Get(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Get current authenticated user profile",
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user profile returned",
     *     )
     * )
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * @OA\Put(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Update profile of authenticated user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile updated successfully.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    /**
     * @OA\Put(
     *     path="/api/profile/password",
     *     tags={"Profile"},
     *     summary="Update password of authenticated user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "password", "password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="oldpass123"),
     *             @OA\Property(property="password", type="string", format="password", example="newpass456"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpass456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password updated successfully.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
