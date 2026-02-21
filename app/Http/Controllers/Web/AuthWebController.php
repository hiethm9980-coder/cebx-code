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
    //  B2B LOGIN (يقبل business أو organization)
    // ════════════════════════════════════════
    public function showB2bLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->account && in_array($user->account->type, ['business', 'organization'], true)) {
                return redirect()->to(url('/'));
            }
        }
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
            $isB2b = $user->account && in_array($user->account->type, ['business', 'organization'], true);

            if (!$isB2b) {
                $type = $user->account->type ?? '';
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $hint = match($type) {
                    'individual' => 'يرجى استخدام <a href="' . url('/b2c/login') . '">بوابة الأفراد</a>',
                    'admin' => 'يرجى استخدام <a href="' . url('/admin/login') . '">بوابة الإدارة</a>',
                    default => 'يرجى استخدام البوابة المناسبة',
                };
                return back()->withErrors(['email' => "هذا الحساب غير مسجّل كحساب أعمال. {$hint}"])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            return redirect()->to(url('/'));
        }

        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->onlyInput('email');
    }

    // ════════════════════════════════════════
    //  B2C LOGIN
    // ════════════════════════════════════════
    public function showB2cLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->account && $user->account->type === 'individual') {
                return redirect()->to(url('/'));
            }
        }
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
                    'business', 'organization' => 'يرجى استخدام <a href="' . url('/b2b/login') . '">بوابة الأعمال</a>',
                    'admin' => 'يرجى استخدام <a href="' . url('/admin/login') . '">بوابة الإدارة</a>',
                    default => 'يرجى استخدام البوابة المناسبة',
                };
                return back()->withErrors(['email' => "هذا الحساب غير مسجّل كحساب فردي. {$hint}"])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            return redirect()->to(url('/'));
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
                    'business', 'organization' => 'يرجى استخدام <a href="' . url('/b2b/login') . '">بوابة الأعمال</a>',
                    'individual' => 'يرجى استخدام <a href="' . url('/b2c/login') . '">بوابة الأفراد</a>',
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
    //  LOGOUT — returns to correct portal login (استخدام url لتجنب Route not defined)
    // ════════════════════════════════════════
    public function logout(Request $request)
    {
        $user = Auth::user();
        $loginUrl = url('/login');

        if ($user) {
            $isAdmin = $user->is_super_admin || $user->role === 'admin';
            $type = $user->account->type ?? '';

            $loginUrl = match(true) {
                $isAdmin, $type === 'admin' => url('/admin/login'),
                $type === 'individual' => url('/b2c/login'),
                in_array($type, ['business', 'organization'], true) => url('/b2b/login'),
                default => url('/login'),
            };
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($loginUrl);
    }
}
