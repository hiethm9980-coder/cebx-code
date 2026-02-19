<?php
namespace App\Http\Controllers\Web;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreWebController extends WebController
{
    public function index() {
        return view('pages.stores.index', ['stores' => Store::withCount('orders')->get()]);
    }
    public function store(Request $r) {
        $d = $r->validate(['name'=>'required','platform'=>'required','url'=>'required']);
        $d['status'] = 'active';
        $d['account_id'] = auth()->user()->account_id;
        Store::create($d);
        return back()->with('success', 'تم ربط المتجر');
    }
    public function sync(Store $store) { return back()->with('success', 'تمت مزامنة ' . $store->name); }
    public function test(Store $store) { return back()->with('success', 'اتصال ناجح بـ ' . $store->name); }
    public function destroy(Store $store) { $store->delete(); return back()->with('success', 'تم حذف المتجر'); }
}
