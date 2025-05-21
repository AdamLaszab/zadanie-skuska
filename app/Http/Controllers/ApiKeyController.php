<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Process;
use App\Models\User;


class ApiKeyController {

    private function generateApiKey(User $user)
    {
        $plainTextKey = Str::random(64);

        $user->api_key = $plainTextKey;
        $user->save();

        return $plainTextKey;
    }

    /**
     * @OA\Get(
     *     path="/api/api-key",
     *     tags={"API Key"},
     *     summary="Generate a new API key",
     *     description="Generates a new API key for the currently authenticated user and returns it in plain text. The key is stored in hashed form in the database.",
     *     security={{"apiAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="API key generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="api_key", type="string", example="9e2cf4f879d449a89bfb1b44b196cf6b0f0a24e86d7356c7df4832e2d49956a7")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized – user must be authenticated"
     *     )
     * )
     */

    public function newApiKeyApi(Request $request){
        $plainTextKey = $this->generateApiKey($request->user());
        return response()->json([
            'api_key' => $plainTextKey, 
        ], 200);
    }

    public function newApiKeyInertia(Request $request)
    {
        $key = $this->generateApiKey($request->user());
        return redirect()->route('profile')->with('api_key', $key);
    }

}