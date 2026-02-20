<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware
 *
 * FIX P2-4: تعديل لدعم بوابات B2B/B2C المنفصلة.
 * عند عدم تسجيل الدخول يعيد التوجيه للبوابة المناسبة.
 */
class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->denyAccess($request, 'يرجى تسجيل الدخول.', 401);
        }

        if (! $user->account_id) {
            return $this->denyAccess($request, 'الحساب غير مرتبط. تواصل مع الدعم.', 403);
        }

        $account = $user->account()->withoutGlobalScopes()->first();
        if (! $account) {
            return $this->denyAccess($request, 'الحساب غير موجود.', 403);
        }

        if ($account->status === 'suspended') {
            return $this->denyAccess($request, 'الحساب موقوف. تواصل مع الإدارة.', 403);
        }

        // Bind current account_id to the container for global scopes
        app()->instance('current_account_id', $user->account_id);
        app()->instance('current_account', $account);

        return $next($request);
    }

    /**
     * FIX P2-4: إعادة التوجيه حسب البوابة عند عدم تسجيل الدخول.
     */
    private function denyAccess(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success'    => false,
                'error_code' => $status === 401 ? 'ERR_UNAUTHENTICATED' : 'ERR_ACCOUNT_INVALID',
                'message'    => $message,
            ], $status);
        }

        // FIX P2-4: إعادة التوجيه للبوابة المناسبة
        if ($status === 401) {
            return redirect($this->resolveLoginRoute($request));
        }

        abort($status, $message);
    }

    /**
     * FIX P2-4: تحديد صفحة تسجيل الدخول بناءً على المسار.
     */
    private function resolveLoginRoute(Request $request): string
    {
        $path = $request->path();

        if (str_starts_with($path, 'b2b')) {
            return route('b2b.login');
        }

        if (str_starts_with($path, 'b2c')) {
            return route('b2c.login');
        }

        // Fallback: البوابة العامة (التوافق مع النظام الحالي)
        return route('login');
    }
}
