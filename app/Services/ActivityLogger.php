<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class ActivityLogger
{
    // App\Services\ActivityLogger.php
public static function log($action, $details = null)
{
    $isInertia = request()->header('X-Inertia') === 'true';
    $method = $isInertia ? 'Frontend' : 'API';
    
    $ip = request()->ip();
    \Illuminate\Support\Facades\Log::info("Attempting to geolocate IP: " . $ip); // Keep this for now
    
    $userId = \Illuminate\Support\Facades\Auth::id();

    $city = 'Unknown';
    $country = 'Unknown';
    $position = null;

    // Check if IP is not local or private before attempting geolocation
    if ($ip && $ip !== '127.0.0.1' && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $position = \Stevebauman\Location\Facades\Location::get($ip); // Ensure you have the Facade imported or use full namespace
        if ($position) {
            $city = $position->cityName ?: 'N/A'; 
            $country = $position->countryName ?: 'N/A';
        } else {
            \Illuminate\Support\Facades\Log::warning("Geolocation failed for public IP: " . $ip . ". Location::get() returned false/null.");
        }
    } elseif ($ip === '127.0.0.1' || ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
        // It's a local or private IP
        if ($ip === '127.0.0.1') {
            $city = 'Localhost';
            $country = 'N/A';
        } else {
            $city = 'Private Network'; // Private Network
            $country = 'N/A';
        }
        \Illuminate\Support\Facades\Log::info("IP " . $ip . " is local/private. Skipping external geolocation.");
    } else {
        // Invalid IP or no IP
        \Illuminate\Support\Facades\Log::warning("Invalid or missing IP address: " . ($ip ?: 'null'));
        $city = 'Unknown IP';
        $country = 'N/A';
    }
    
    return \App\Models\ActivityLog::create([ // Ensure correct model namespace
        'user_id' => $userId,
        'action' => $action,
        'access_method' => $method,
        'details' => $details,
        'ip_address' => $ip,
        'city' => $city,
        'country' => $country,
    ]);
}

}