<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class ApiKeyService
{
    public function generate(User $user): string
    {
        $plainTextKey = Str::random(64);
        $user->api_key = hash('sha256', $plainTextKey);
        $user->save();

        return $plainTextKey;
    }
}
