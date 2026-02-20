<?php
namespace App\Http\Controllers\Web;
use App\Models\{Wallet, WalletTransaction};
use Illuminate\Http\Request;

class WalletWebController extends WebController
{
    public function index()
    {
        $accountId = auth()->user()->account_id;
        $wallet = Wallet::firstOrCreate(['account_id' => $accountId], ['available_balance' => 0, 'locked_balance' => 0]);
        $transactions = WalletTransaction::where('account_id', $accountId)->latest()->paginate(15);
        return view('pages.wallet.index', compact('wallet', 'transactions'));
    }

    public function topup(Request $request)
    {
        $v = $request->validate(['amount' => 'required|numeric|min:10|max:100000', 'payment_method' => 'nullable|string']);
        $accountId = auth()->user()->account_id;
        $wallet = Wallet::firstOrCreate(['account_id' => $accountId], ['available_balance' => 0, 'locked_balance' => 0]);
        $wallet->increment('available_balance', $v['amount']);

        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'account_id' => $accountId,
            'reference_number' => 'TXN-' . str_pad(WalletTransaction::count() + 1, 5, '0', STR_PAD_LEFT),
            'type' => 'credit', 'description' => 'شحن رصيد — ' . ($v['payment_method'] ?? 'تحويل بنكي'),
            'amount' => $v['amount'], 'balance_after' => $wallet->available_balance,
            'status' => 'completed', 'payment_method' => $v['payment_method'] ?? 'bank_transfer',
        ]);

        return back()->with('success', "تم شحن SAR " . number_format($v['amount'], 2));
    }
}
