<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController
{
    public function index()
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate(25);
            
        return Inertia::render('Admin/Logs', [
            'logs' => $logs
        ]);
    }

    public function export()
    {
        $logs = ActivityLog::with('user')->get();
        
        $csvFileName = 'activity_logs_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
        
        $columns = ['ID', 'Pouzivatel', 'ID Pouzivatela', 'Akcia', 'Metoda', 'Detail', 'IP', 'Mesto', 'Krajina', 'Datum'];
        
        $callback = function() use($logs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            foreach($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user->username ?? 'Neznamy',
                    $log->user->user_id ?? 'Neznamy',
                    $log->action,
                    $log->access_method,
                    $log->details,
                    $log->ip_address,
                    $log->city,
                    $log->country,
                    $log->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    public function clear()
    {
        ActivityLog::truncate();
        
        return redirect()->route('admin.logs.index')
        ->with('success', 'All activity logs have been successfully cleared.');
    }
}