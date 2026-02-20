<?php

namespace App\Http\Controllers\Web;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreWebController extends WebController
{
    public function index()
    {
        $query = Store::query();
        if (!$this->isAdmin()) {
            $query->where('account_id', auth()->user()->account_id);
        } else {
            $query->with('account');
        }
        $stores = $query->latest()->get();
        return view('pages.stores.index', compact('stores'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:100', 'platform' => 'required|string',
            'url' => 'nullable|url', 'api_key' => 'nullable|string',
        ]);
        Store::create(array_merge($v, [
            'account_id' => auth()->user()->account_id,
            'status' => 'connected', 'last_sync_at' => now(),
        ]));
        return back()->with('success', 'تم ربط المتجر بنجاح');
    }

    public function edit(Store $store)
    {
        return view('pages.stores.edit', compact('store'));
    }

    public function sync(Store $store)
    {
        $store->update(['last_sync_at' => now()]);
        return back()->with('success', "تمت مزامنة {$store->name}");
    }

    public function destroy(Store $store)
    {
        $store->update(['status' => 'disconnected']);
        return back()->with('warning', "تم فصل {$store->name}");
    }
}
