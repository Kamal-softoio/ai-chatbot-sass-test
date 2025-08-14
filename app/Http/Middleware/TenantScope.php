<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;

class TenantScope
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tenant.login');
        }

        $tenant = Auth::guard('tenant')->user();
        
        // التأكد من أن المستأجر نشط
        if (!$tenant->is_active) {
            Auth::guard('tenant')->logout();
            return redirect()->route('tenant.login')
                ->with('error', 'حسابك غير نشط. يرجى التواصل مع الدعم.');
        }

        // تعيين السياق العام للمستأجر الحالي
        app()->instance('current_tenant', $tenant);
        
        return $next($request);
    }
}


