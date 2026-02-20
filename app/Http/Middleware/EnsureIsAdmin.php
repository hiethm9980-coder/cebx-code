<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Allow if super admin or admin role or admin account type
        if ($user->is_super_admin || $user->role === 'admin' || ($user->account && $user->account->type === 'admin')) {
            return $next($request);
        }

        abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
    }
}
