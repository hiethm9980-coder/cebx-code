<?php
namespace App\Http\Controllers\Web;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserWebController extends WebController
{
    public function index() {
        return view('pages.users.index', [
            'users' => User::where('account_id', auth()->user()->account_id)->with('roles')->paginate(20),
            'roles' => Role::where('account_id', auth()->user()->account_id)->get(),
        ]);
    }
    public function store(Request $r) {
        $d = $r->validate(['name'=>'required','email'=>'required|email|unique:users','password'=>'required|min:6','role'=>'nullable']);
        $u = User::create(['name'=>$d['name'],'email'=>$d['email'],'password'=>Hash::make($d['password']),'account_id'=>auth()->user()->account_id]);
        if (isset($d['role'])) $u->roles()->attach($d['role']);
        return back()->with('success', 'تم إضافة ' . $u->name);
    }
    public function toggle(User $user) {
        $user->update(['status' => ($user->status ?? 'active') === 'active' ? 'suspended' : 'active']);
        return back()->with('success', 'تم تحديث الحالة');
    }
    public function destroy(User $user) { $user->delete(); return back()->with('success', 'تم الحذف'); }
}
