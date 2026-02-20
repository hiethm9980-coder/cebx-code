<?php

namespace App\Http\Controllers\Web;

use App\Models\{Wallet, WalletTransaction};
use Illuminate\Http\Request;

class WalletWebController extends WebController
{
    public function index()
    {
        if ($this->isAdmin()) {
            // Admin: show ALL wallets summary + all transactions
            $wallets      = Wallet::with('account')->get();
            $totalBalance = $wallets->sum('available_balance');
            $wallet       = (object) ['available_balance' => $totalBalance, 'pending_balance' => $wallets->sum('pending_balance')];
            $transactions = WalletTransaction::with('wallet.account')->latest()->paginate(20);
        } else {
            $accountId    = auth()->user()->account_id;
            $wallet       = Wallet::firstOrCreate(['account_id' => $accountId], ['available_balance' => 0, 'pending_balance' => 0]);
            $transactions = WalletTransaction::where('account_id', $accountId)->latest()->paginate(15);
            $wallets      = collect();
            $totalBalance = $wallet->available_balance;
        }

        return view('pages.wallet.index', compact('wallet', 'transactions', 'wallets', 'totalBalance'));
    }

    public function topup(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10|max:50000']);
        $accountId = auth()->user()->account_id;
        $wallet    = Wallet::firstOrCreate(['account_id' => $accountId], ['available_balance' => 0]);
        $wallet->increment('available_balance', $request->amount);

        WalletTransaction::create([
            'wallet_id'        => $wallet->id,
            'account_id'       => $accountId,
            'reference_number' => 'TXN-' . str_pad(WalletTransaction::count() + 1, 5, '0', STR_PAD_LEFT),
            'type'             => 'credit',
            'description'      => 'شحن رصيد المحفظة',
            'amount'           => $request->amount,
            'balance_after'    => $wallet->available_balance,
            'status'           => 'completed',
        ]);

        return back()->with('success', 'تم شحن الرصيد بنجاح — SAR ' . number_format($request->amount));
    }
}
