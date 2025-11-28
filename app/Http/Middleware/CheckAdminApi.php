<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminApi
{
    /**
     * Handle an incoming request for API routes.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Please login first.'
            ], 401);
        }

        // Check if user has admin role
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Admin access required.'
            ], 403);
        }

        return $next($request);
    }
}