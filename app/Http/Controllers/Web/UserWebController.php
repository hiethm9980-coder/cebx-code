<?php
namespace App\Http\Controllers\Web;
use App\Models\{User, Branch};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserWebController extends WebController
{
    public function index()
    {
        $accountId = auth()->user()->account_id;
        $users = User::where('account_id', $accountId)->latest()->paginate(20);
        $activeCount   = User::where('account_id', $accountId)->where('is_active', true)->count();
        $pendingCount  = 0;
        $disabledCount = User::where('account_id', $accountId)->where('is_active', false)->count();
        return view('pages.users.index', compact('users', 'activeCount', 'pendingCount', 'disabledCount'));
    }

    public function edit(User $user)
    {
        $organizations = \App\Models\Account::where('type', '!=', 'admin')->get();
        $branches = Branch::where('is_active', true)->get();
        return view('pages.users.edit', compact('user', 'organizations', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:200',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'job_title' => 'nullable|string',
            'role_name' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'password' => 'nullable|min:6|confirmed',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return back()->with('success', 'تم تحديث بيانات المستخدم');
    }
}
