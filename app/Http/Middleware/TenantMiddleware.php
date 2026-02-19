<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware
 *
 * Resolves the current tenant (account) from the authenticated user
 * and binds it to the container so all queries are automatically scoped.
 *
 * FIX H-01: يرفض الطلبات إذا لم يكن هناك حساب مرتبط
 */
class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // FIX H-01: التحقق من وجود المستخدم و account_id
        if (! $user) {
            return $this->denyAccess($request, 'يرجى تسجيل الدخول.', 401);
        }

        if (! $user->account_id) {
            return $this->denyAccess($request, 'الحساب غير مرتبط. تواصل مع الدعم.', 403);
        }

        // التحقق من أن الحساب نشط
        $account = $user->account()->withoutGlobalScopes()->first();
        if (! $account) {
            return $this->denyAccess($request, 'الحساب غير موجود.', 403);
        }

        if ($account->status === 'suspended') {
            return $this->denyAccess($request, 'الحساب موقوف. تواصل مع الإدارة.', 403);
        }

        // Bind current account_id to the container for global scopes
        app()->instance('current_account_id', $user->account_id);

        // Also store the full account for convenience
        app()->instance('current_account', $account);

        return $next($request);
    }

    /**
     * رفض الوصول مع رسالة مناسبة حسب نوع الطلب
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

        // لطلبات الويب — إعادة التوجيه لصفحة الدخول
        if ($status === 401) {
            return redirect()->route('login');
        }

        abort($status, $message);
    }
}
