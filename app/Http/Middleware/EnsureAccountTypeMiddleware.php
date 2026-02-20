<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FIX P2-1: EnsureAccountTypeMiddleware
 *
 * يمنع حساب فردي (individual) من دخول بوابة B2B والعكس.
 *
 * القواعد:
 *   بوابة b2b ⇒ account.type === 'organization'
 *   بوابة b2c ⇒ account.type === 'individual'
 *
 * الاستخدام في routes:
 *   middleware: 'ensureAccountType:organization'  (لبوابة B2B)
 *   middleware: 'ensureAccountType:individual'     (لبوابة B2C)
 */
class EnsureAccountTypeMiddleware
{
    public function handle(Request $request, Closure $next, string $requiredType): Response
    {
        $user = $request->user();

        if (!$user) {
            // غير مسجل دخول — سيُعالج بواسطة auth middleware
            return $next($request);
        }

        $account = $user->account;

        if (!$account) {
            abort(403, 'لا يوجد حساب مرتبط بالمستخدم.');
        }

        // التحقق من نوع الحساب
        if ($account->type !== $requiredType) {
            $portal = $request->attributes->get('portal', '');

            // إذا كان طلب API
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'ERR_WRONG_PORTAL',
                    'message'    => $this->getErrorMessage($account->type, $requiredType),
                ], 403);
            }

            // إعادة توجيه لبوابة الصحيحة
            if ($account->type === 'organization') {
                return redirect()->route('b2b.dashboard')
                    ->with('warning', 'حسابك من نوع منظمة، تم توجيهك لبوابة B2B.');
            }

            return redirect()->route('b2c.dashboard')
                ->with('warning', 'حسابك فردي، تم توجيهك لبوابة B2C.');
        }

        return $next($request);
    }

    private function getErrorMessage(string $actualType, string $requiredType): string
    {
        if ($requiredType === 'organization') {
            return 'هذه البوابة مخصصة لحسابات المنظمات فقط. حسابك من نوع فردي.';
        }

        return 'هذه البوابة مخصصة للحسابات الفردية فقط. حسابك من نوع منظمة.';
    }
}
