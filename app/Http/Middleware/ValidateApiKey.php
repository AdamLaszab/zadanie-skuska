<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->bearerToken();
        
        if (!$apiKey) {
            return response()->json(['error' => 'API key is missing'], 401);
        }
        
        $hashedKey = hash('sha256', $apiKey);
        
        $user = User::where('api_key', $hashedKey)->first();
        
        if (!$user) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }
        
        $request->setUserResolver(function () use ($user) {
             return $user;
         });
        
        return $next($request);
    }
}