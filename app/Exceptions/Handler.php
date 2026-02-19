<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * FIX H-08: التمييز بين طلبات API وطلبات الويب
 */
class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (ValidationException $e) {
            $request = request();

            // FIX H-08: إرجاع JSON فقط لطلبات API
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null; // السماح لـ Laravel بالتعامل الافتراضي (redirect مع errors)
            }

            $errors = $e->errors();
            $errorCode = 'ERR_INVALID_INPUT';

            if (isset($errors['email'])) {
                foreach ($errors['email'] as $msg) {
                    if (str_contains($msg, 'ERR_DUPLICATE_EMAIL')) {
                        $errorCode = 'ERR_DUPLICATE_EMAIL';
                        break;
                    }
                }
            }

            return response()->json([
                'success'    => false,
                'error_code' => $errorCode,
                'message'    => 'فشل التحقق من صحة البيانات.',
                'errors'     => $errors,
            ], 422);
        });

        $this->renderable(function (AuthenticationException $e) {
            $request = request();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'ERR_UNAUTHENTICATED',
                    'message'    => 'يرجى تسجيل الدخول.',
                ], 401);
            }
            return null; // السماح بإعادة التوجيه لصفحة الدخول
        });

        $this->renderable(function (NotFoundHttpException $e) {
            $request = request();

            // FIX H-08: إرجاع JSON فقط لطلبات API
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'ERR_NOT_FOUND',
                    'message'    => 'المورد المطلوب غير موجود.',
                ], 404);
            }

            // لطلبات الويب — عرض صفحة 404
            return null;
        });

        // معالجة BusinessException
        $this->renderable(function (BusinessException $e) {
            $request = request();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'error_code' => $e->getErrorCode(),
                    'message'    => $e->getMessage(),
                ], $e->getStatusCode());
            }
            return null;
        });

        // عدم كشف تفاصيل الأخطاء في الإنتاج
        $this->reportable(function (Throwable $e) {
            // Log all exceptions for monitoring
        });
    }
}
