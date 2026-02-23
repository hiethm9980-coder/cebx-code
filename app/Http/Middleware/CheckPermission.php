<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPermission Middleware — Fixed for Web + API
 *
 * Original: always returned JSON (broke web routes)
 * Fixed: detects web vs API and responds appropriately
 *
 * REPLACES: app/Http/Middleware/CheckPermission.php
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'ليس لديك صلاحية كافية للوصول'], 403);
            }
            abort(403, 'ليس لديك صلاحية كافية للوصول');
        }

        return $next($request);
    }
}
