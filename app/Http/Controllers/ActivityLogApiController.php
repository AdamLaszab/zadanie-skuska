<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Activity Logs",
 *     description="User activity log management"
 * )
 */
class ActivityLogApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/logs",
     *     tags={"Activity Logs"},
     *     summary="Get paginated list of activity logs",
     *     @OA\Response(
     *         response=200,
     *         description="List of activity logs",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ActivityLogEntry"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')->latest()->paginate(25);
        return response()->json($logs);
    }

    /**
     * @OA\Get(
     *     path="/api/logs/export",
     *     tags={"Activity Logs"},
     *     summary="Export activity logs to CSV",
     *     @OA\Response(
     *         response=200,
     *         description="CSV file download",
     *     )
     * )
     */
    public function export()
    {
        $logs = ActivityLog::with('user')->get();
        $csvFileName = 'activity_logs_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate",
            "Expires" => "0"
        ];

        $columns = ['ID', 'USER', 'USER ID', 'ACTION', 'ACCESS METHOD', 'DETAILS', 'IP ADDRESS', 'CITY', 'COUNTRY', 'DATE'];

        $callback = function () use ($logs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user->username ?? 'Unknown',
                    $log->user->user_id ?? 'Unknown',
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

    /**
     * @OA\Delete(
     *     path="/api/logs",
     *     tags={"Activity Logs"},
     *     summary="Clear all activity logs",
     *     @OA\Response(
     *         response=200,
     *         description="Logs cleared",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="All activity logs have been cleared.")
     *         )
     *     )
     * )
     */
    public function clear()
    {
        ActivityLog::truncate();

        return response()->json(['message' => 'All activity logs have been cleared.']);
    }
}
