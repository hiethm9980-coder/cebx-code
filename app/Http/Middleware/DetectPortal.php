<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Detects B2C/B2B/Admin portal type from authenticated user
 * and shares $portalType with ALL Blade views.
 *
 * This replaces the old $this->middleware() approach which
 * was removed in Laravel 11.
 */
class DetectPortal
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $portalType = 'b2b'; // default

        if ($user && $user->account) {
            $portalType = match ($user->account->type) {
                'individual' => 'b2c',
                'admin'      => 'admin',
                default      => 'b2b',
            };
        }

        // Role-based admin override
        if ($user && ($user->is_super_admin || $user->role === 'admin')) {
            $portalType = 'admin';
        }

        // Store on request for controllers to access
        $request->attributes->set('portalType', $portalType);

        // Share with ALL Blade views
        View::share('portalType', $portalType);

        return $next($request);
    }
}
