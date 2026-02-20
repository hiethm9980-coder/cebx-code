<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthWebController extends Controller
{
    /**
     * Portal selector — landing page with 3 doors
     */
    public function portalSelector()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('pages.auth.portal-selector');
    }

    // ── B2B ── (يقبل نوع الحساب business أو organization)
    public function showB2bLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->account && in_array($user->account->type, ['business', 'organization'], true)) {
                return redirect()->route('b2b.dashboard');
            }
        }
        return view('pages.auth.login-b2b');
    }

    public function loginB2b(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email', 'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $isB2b = $user->account && in_array($user->account->type, ['business', 'organization'], true);
            if (!$isB2b) {
                Auth::logout();
                $request->session()->invalidate();
                $portalName = match($user->account->type ?? '') {
                    'individual' => 'بوابة الأفراد',
                    'admin' => 'بوابة الإدارة',
                    default => 'البوابة المناسبة',
                };
                return back()->withErrors(['email' => "هذا الحساب غير مسجّل كحساب أعمال. يرجى استخدام {$portalName}."])->onlyInput('email');
            }
            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            return redirect()->intended(route('b2b.dashboard'));
        }
        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->onlyInput('email');
    }

    // ── B2C ──
    public function showB2cLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->account && $user->account->type === 'individual') {
                return redirect()->route('b2c.dashboard');
            }
        }
        return view('pages.auth.login-b2c');
    }

    public function loginB2c(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email', 'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->account || $user->account->type !== 'individual') {
                Auth::logout();
                $request->session()->invalidate();
                $portalName = match($user->account->type ?? '') {
                    'business' => 'بوابة الأعمال',
                    'admin' => 'بوابة الإدارة',
                    default => 'البوابة المناسبة',
                };
                return back()->withErrors(['email' => "هذا الحساب غير مسجّل كحساب فردي. يرجى استخدام {$portalName}."])->onlyInput('email');
            }
            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            return redirect()->intended(route('b2c.dashboard'));
        }
        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->onlyInput('email');
    }

    // ── Admin ──
    public function showAdminLogin()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('pages.auth.login-admin');
    }

    public function loginAdmin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email', 'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $isAdmin = $user->is_super_admin || $user->role === 'admin' || ($user->account && $user->account->type === 'admin');
            if (!$isAdmin) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors(['email' => 'ليس لديك صلاحيات إدارية. يرجى استخدام البوابة المناسبة.'])->onlyInput('email');
            }
            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            return redirect()->intended(route('dashboard'));
        }
        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->onlyInput('email');
    }

    // ── Logout ──
    public function logout(Request $request)
    {
        $portalType = 'login';
        $user = Auth::user();
        if ($user && $user->account) {
            $portalType = match($user->account->type) {
                'individual' => 'b2c.login',
                'admin' => 'admin.login',
                default => 'b2b.login',
            };
            if ($user->is_super_admin || $user->role === 'admin') {
                $portalType = 'admin.login';
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route($portalType);
    }
}
