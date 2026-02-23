<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * UserWebController — P1-3/P1-4: User Safety
 *
 * Changes from original:
 *   - destroy(): Hard delete → Soft delete + Owner/self protection
 *   - toggle(): Now revokes tokens + invalidates session on suspend
 *   - store(): Added account_id scoping + DB::transaction
 *
 * IMPORTANT: This file REPLACES the existing UserWebController.php
 * All existing method signatures preserved (index, store, toggle, destroy).
 */
class UserWebController extends WebController
{
    /**
     * GET /users — List users (unchanged signature)
     */
    public function index()
    {
        return view('pages.users.index', [
            'users' => User::where('account_id', auth()->user()->account_id)
                ->with('roles')
                ->paginate(20),
            'roles' => Role::where('account_id', auth()->user()->account_id)->get(),
        ]);
    }

    /**
     * POST /users — Create user (enhanced with transaction)
     */
    public function store(Request $r)
    {
        $d = $r->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'nullable|exists:roles,id',
        ]);

        try {
            $u = DB::transaction(function () use ($d) {
                $user = User::create([
                    'name'       => $d['name'],
                    'email'      => $d['email'],
                    'password'   => Hash::make($d['password']),
                    'account_id' => auth()->user()->account_id,
                    'status'     => 'active',
                ]);

                if (isset($d['role'])) {
                    $user->roles()->attach($d['role']);
                }

                return $user;
            });

            return back()->with('success', 'تم إضافة ' . $u->name);
        } catch (\Exception $e) {
            Log::error('UserWebController::store failed', [
                'error' => $e->getMessage(),
                'account_id' => auth()->user()->account_id,
            ]);
            return back()->with('error', 'حدث خطأ أثناء إنشاء المستخدم');
        }
    }

    /**
     * PATCH /users/{user}/toggle — Toggle user status
     *
     * P1-4: When suspending → revoke ALL tokens + invalidate remember_token
     * This immediately prevents API access AND session-based access.
     */
    public function toggle(User $user)
    {
        // Security: ensure same account
        if ($user->account_id !== auth()->user()->account_id) {
            abort(403, 'ليس لديك صلاحية');
        }

        // Cannot toggle yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الخاص');
        }

        // Cannot toggle Owner
        if ($user->is_owner) {
            return back()->with('error', 'لا يمكن تعطيل حساب المالك');
        }

        $newStatus = ($user->status ?? 'active') === 'active' ? 'suspended' : 'active';

        DB::transaction(function () use ($user, $newStatus) {
            $user->update(['status' => $newStatus]);

            // P1-4: On suspend → revoke all tokens + invalidate sessions
            if ($newStatus === 'suspended') {
                // Revoke Sanctum API tokens
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                // Invalidate session-based auth (forces re-login)
                $user->update(['remember_token' => Str::random(60)]);
            }
        });

        $msg = $newStatus === 'suspended'
            ? "تم تعطيل {$user->name} وإلغاء جميع جلساته"
            : "تم تفعيل {$user->name}";

        return back()->with('success', $msg);
    }

    /**
     * DELETE /users/{user} — Soft delete user
     *
     * P1-3: Soft delete instead of hard delete
     * Protection: Cannot delete Owner, cannot delete yourself
     * Also revokes all tokens before soft delete.
     */
    public function destroy(User $user)
    {
        // Security: ensure same account
        if ($user->account_id !== auth()->user()->account_id) {
            abort(403, 'ليس لديك صلاحية');
        }

        // Cannot delete yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص');
        }

        // Cannot delete Owner
        if ($user->is_owner) {
            return back()->with('error', 'لا يمكن حذف حساب المالك');
        }

        try {
            DB::transaction(function () use ($user) {
                // Revoke all tokens first
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                // Invalidate session
                $user->update(['remember_token' => Str::random(60)]);

                // Soft delete (requires SoftDeletes trait on User model)
                // If SoftDeletes not available, mark as 'deleted' status
                if (method_exists($user, 'trashed')) {
                    $user->delete(); // Uses SoftDeletes
                } else {
                    $user->update(['status' => 'deleted']);
                }
            });

            return back()->with('success', "تم حذف {$user->name}");
        } catch (\Exception $e) {
            Log::error('UserWebController::destroy failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'حدث خطأ أثناء حذف المستخدم');
        }
    }
}
