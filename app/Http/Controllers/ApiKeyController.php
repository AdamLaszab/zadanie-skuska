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
        $hashedKey = hash('sha256', $plainTextKey);

        $user->api_key = $hashedKey;
        $user->save();

        return $plainTextKey;
    }

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