<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tenant.login');
        }

        $tenant = Auth::guard('tenant')->user();
        
        if (!$tenant->is_active) {
            Auth::guard('tenant')->logout();
            return redirect()->route('tenant.login')
                ->withErrors(['account' => 'تم إلغاء تفعيل حسابك. يرجى التواصل مع الدعم.']);
        }

        return $next($request);
    }
}