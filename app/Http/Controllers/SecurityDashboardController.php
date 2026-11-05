<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    public function index()
    {
        $since = now()->subHours(24);
        $stats = [];

        foreach (['success', 'failed', 'blocked'] as $status) {
            $stats[$status] = LoginAttempt::where('status', $status)
                ->where('created_at', '>=', $since)
                ->count();
        }

        $suspiciousIps = LoginAttempt::select('ip_address', DB::raw('COUNT(*) as total'))
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total')
            ->get();

        return view('security-dashboard', compact('stats', 'suspiciousIps'));
    }

    // Mejora 2: exportación de evidencias en CSV
    public function export()
    {
        $rows = LoginAttempt::where('created_at', '>=', now()->subDay())
            ->orderBy('created_at')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'identifier', 'ip_address', 'status', 'user_agent', 'created_at']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->id, $r->identifier, $r->ip_address, $r->status, $r->user_agent, $r->created_at]);
            }
            fclose($out);
        }, 'evidencias-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }
}