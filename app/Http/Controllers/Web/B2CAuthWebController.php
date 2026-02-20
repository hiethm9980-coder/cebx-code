<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * FIX P2-2: B2CAuthWebController
 *
 * بوابة دخول B2C — للحسابات الفردية.
 *
 * المدخلات: email + password
 * المنطق:
 *   1. ابحث عن المستخدم بالبريد حيث حسابه individual
 *   2. إذا وُجد أكثر من واحد: أوقف العملية
 *   3. تحقق كلمة المرور ثم سجّل الدخول
 */
class B2CAuthWebController extends Controller
{
    /**
     * عرض نموذج تسجيل دخول B2C.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->account && $user->account->type === 'individual') {
                return redirect()->route('b2c.dashboard');
            }
            Auth::logout();
        }

        return view('b2c.b2c-login');
    }

    /**
     * معالجة تسجيل الدخول لـ B2C.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // 1. البحث عن مستخدمين بهذا البريد في حسابات فردية
        $users = User::where('email', $request->email)
            ->whereHas('account', function ($query) {
                $query->where('type', 'individual');
            })
            ->with('account')
            ->get();

        // 2. لا يوجد مستخدم
        if ($users->isEmpty()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'البريد الإلكتروني غير مسجل.']);
        }

        // 3. أكثر من مستخدم بنفس البريد (نادر لكن ممكن بسبب تصميم DB)
        if ($users->count() > 1) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'هذا البريد مرتبط بأكثر من حساب. تواصل مع الدعم الفني أو استخدم بوابة B2B مع معرّف المنظمة.',
                ]);
        }

        $user = $users->first();

        // 4. التحقق من كلمة المرور
        if (!Hash::check($request->password, $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        // 5. التحقق من حالة المستخدم
        if (isset($user->status) && $user->status === 'suspended') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'تم تعليق حسابك. تواصل مع الدعم.']);
        }

        // 6. تسجيل الدخول
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('b2c.dashboard'));
    }

    /**
     * تسجيل الخروج.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('b2c.login');
    }
}
