<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class ActivityLogger
{
    public static function log($action, $details = null)
    {
        $isInertia = request()->header('X-Inertia') === 'true';
        $method = $isInertia ? 'frontend' : 'API';
        
        $ip = request()->ip();
        $position = Location::get($ip);
        $userId = request()->user()->id;
        
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'access_method' => $method,
            'details' => $details,
            'ip_address' => $ip,
            'city' => $position ? $position->cityName : 'Nezname',
            'country' => $position ? $position->countryName : 'Neznama',
        ]);
    }

}