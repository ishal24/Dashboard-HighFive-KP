<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnsureUserHasRequiredData
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Validasi untuk account_manager
        if ($user->role === 'account_manager' && !$user->account_manager_id) {
            Log::error('Account Manager user missing account_manager_id', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda belum terkonfigurasi dengan benar. Hubungi administrator.']);
        }

        // Validasi untuk witel_support
        if ($user->role === 'witel' && !$user->witel_id) {
            Log::error('Witel user missing witel_id', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda belum terkonfigurasi dengan benar. Hubungi administrator.']);
        }

        return $next($request);
    }
}

