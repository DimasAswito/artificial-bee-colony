<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index()
    {
        return view('pages.user.log');
    }

    public function getLogsData()
    {
        $logs = Log::with('user')->orderBy('created_at', 'desc')->get();

        $formattedLogs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user ? $log->user->name : 'System',
                'type' => $log->type,
                'description' => $log->description,
                'time' => \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s'),
            ];
        });

        return response()->json($formattedLogs);
    }
}
