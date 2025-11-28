<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature = null): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Check if user has admin role
        if (Auth::user()->role !== 'admin') {
            // For API requests, return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.',
                    'message' => 'Akses ditolak. Fitur ini hanya untuk Administrator.'
                ], 403);
            }

            // For regular requests, redirect with error message
            $errorMessage = $feature
                ? "Akses ditolak. Fitur {$feature} hanya untuk Administrator."
                : 'Akses ditolak. Fitur ini hanya untuk Administrator.';

            return redirect()->route('dashboard')->with('error', $errorMessage);
        }

        return $next($request);
    }
}