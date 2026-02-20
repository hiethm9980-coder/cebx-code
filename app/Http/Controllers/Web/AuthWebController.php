<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthWebController extends Controller
{
    // ── Portal Selector ──
    public function portalSelector()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('pages.auth.portal-selector');
    }

    // ════════════════════════════════════════
    //  B2B LOGIN
    // ════════════════════════════════════════
    public function showB2bLogin()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('pages.auth.login-b2b');
    }

    public function loginB2b(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Must be a business account
            if (!$user->account || $user->account->type !== 'business') {
                $type = $user->account->type ?? '';
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $hint = match($type) {
                    'individual' => 'يرجى استخدام <a href="' . route('b2c.login') . '">بوابة الأفراد</a>',
                    'admin' => 'يرجى استخدام <a href="' . route('admin.login') . '">بوابة الإدارة</a>',
                    default => 'يرجى استخدام البوابة المناسبة',
                };
                return back()->withErrors(['email' => "هذا الحساب غير مسجّل كحساب أعمال. {$hint}"])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);

            // Always go to dashboard — NOT intended() which might go elsewhere
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->onlyInput('email');
    }

    // ════════════════════════════════════════
    //  B2C LOGIN
    // ════════════════════════════════════════
    public function showB2cLogin()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('pages.auth.login-b2c');
    }

    public function loginB2c(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->account || $user->account->type !== 'individual') {
                $type = $user->account->type ?? '';
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $hint = match($type) {
                    'business' => 'يرجى استخدام <a href="' . route('b2b.login') . '">بوابة الأعمال</a>',
                    'admin' => 'يرجى استخدام <a href="' . route('admin.login') . '">بوابة الإدارة</a>',
                    default => 'يرجى استخدام البوابة المناسبة',
                };
                return back()->withErrors(['email' => "هذا الحساب غير مسجّل كحساب فردي. {$hint}"])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->onlyInput('email');
    }

    // ════════════════════════════════════════
    //  ADMIN LOGIN
    // ════════════════════════════════════════
    public function showAdminLogin()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('pages.auth.login-admin');
    }

    public function loginAdmin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $isAdmin = $user->is_super_admin
                || $user->role === 'admin'
                || ($user->account && $user->account->type === 'admin');

            if (!$isAdmin) {
                $type = $user->account->type ?? '';
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $hint = match($type) {
                    'business' => 'يرجى استخدام <a href="' . route('b2b.login') . '">بوابة الأعمال</a>',
                    'individual' => 'يرجى استخدام <a href="' . route('b2c.login') . '">بوابة الأفراد</a>',
                    default => 'يرجى استخدام البوابة المناسبة',
                };
                return back()->withErrors(['email' => "ليس لديك صلاحيات إدارية. {$hint}"])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->onlyInput('email');
    }

    // ════════════════════════════════════════
    //  LOGOUT — returns to correct portal login
    // ════════════════════════════════════════
    public function logout(Request $request)
    {
        $loginRoute = 'login'; // default: portal selector
        $user = Auth::user();

        if ($user) {
            $isAdmin = $user->is_super_admin || $user->role === 'admin';
            $type = $user->account->type ?? '';

            $loginRoute = match(true) {
                $isAdmin          => 'admin.login',
                $type === 'admin' => 'admin.login',
                $type === 'individual' => 'b2c.login',
                default => 'b2b.login',
            };
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute);
    }
}
