<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\TelegramSecurityPipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HoneypotController extends Controller
{
    /**
     * Trap Attacker / Bot accessing decoy Honeypot endpoints
     */
    public function capture(Request $request)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? 'Unknown User-Agent';
        $path = $request->path();
        $method = $request->method();
        $queryParams = $request->query();
        $body = $request->all();

        $payloadSummary = sprintf(
            "HONEYPOT TRAP TRIGGERED!\nEndpoint: [%s] %s\nQuery: %s\nBody: %s\nReferer: %s",
            $method,
            $path,
            json_encode($queryParams, JSON_UNESCAPED_UNICODE),
            json_encode($body, JSON_UNESCAPED_UNICODE),
            $request->header('referer', 'None')
        );

        $fakeUser = [
            'id'            => 'Honeypot_Trap_' . substr(md5($ip . $userAgent), 0, 8),
            'username'      => 'Attacker_' . str_replace('.', '_', $ip),
            'first_name'    => 'Unknown Intruder',
            'last_name'     => '(' . $request->header('cf-ipcountry', 'Unknown') . ')',
            'language_code' => 'EN',
        ];

        // Trigger forensic alert with Geo-IP and interactive buttons
        TelegramSecurityPipeline::triggerForensicAlert(
            $fakeUser,
            'Honeypot Trap Hit (Decoy Exploit Attempt)',
            'HIGH',
            $payloadSummary,
            $ip,
            $userAgent
        );

        Log::warning("HONEYPOT INTERCEPT: IP [{$ip}] accessed [/{$path}] using [{$userAgent}]");

        // Return deceptive realistic 403 response
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'status'    => 'error',
                'code'      => 403,
                'message'   => 'Access Denied: Internal network access only. Security token invalid.',
                'timestamp' => now()->toIso8601String(),
            ], 403);
        }

        return response(
            '<!DOCTYPE html><html><head><title>403 Forbidden - Internal Management</title><style>body{font-family:sans-serif;background:#0d1117;color:#c9d1d9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;} .box{background:#161b22;border:1px solid #30363d;padding:40px;border-radius:12px;text-align:center;max-width:500px;} h1{color:#f85149;margin-bottom:10px;font-size:24px;} p{color:#8b949e;font-size:14px;line-height:1.6;}</style></head><body><div class="box"><h1>🔒 Access Denied (403)</h1><p>This internal endpoint is strictly restricted to authorized administrative VPN subnet IP ranges.<br><br>Security Incident ID: <code>' . strtoupper(substr(md5($ip . microtime()), 0, 12)) . '</code></p></div></body></html>',
            403
        );
    }
}
