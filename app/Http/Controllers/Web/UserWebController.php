<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserWebController extends WebController
{
    public function index()
    {
        $query = User::query();
        if (!$this->isAdmin()) {
            $query->where('account_id', auth()->user()->account_id);
        } else {
            $query->with('account');
        }

        $users = $query->latest()->paginate(20);

        $statsQ = fn() => $this->isAdmin() ? User::query() : User::where('account_id', auth()->user()->account_id);
        $activeCount   = $statsQ()->where('is_active', true)->count();
        $totalCount    = $statsQ()->count();
        $disabledCount = $statsQ()->where('is_active', false)->count();

        return view('pages.users.index', compact('users', 'activeCount', 'disabledCount', 'totalCount'));
    }

    public function edit(User $user)
    {
        return view('pages.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $v = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'role' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $user->update($v);
        return redirect()->route('users.index')->with('success', "تم تحديث {$user->name}");
    }
}
