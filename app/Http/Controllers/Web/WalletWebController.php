<?php
namespace App\Http\Controllers\Web;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class WalletWebController extends WebController
{
    public function index() {
        $wallet = Wallet::where('account_id', auth()->user()->account_id)->firstOrNew();
        return view('pages.wallet.index', [
            'wallet' => $wallet,
            'transactions' => WalletLedgerEntry::where('wallet_id', $wallet->id ?? 0)->latest()->paginate(20),
            'paymentMethods' => PaymentMethod::where('account_id', auth()->user()->account_id)->get(),
        ]);
    }
    public function topup(Request $r) {
        $r->validate(['amount'=>'required|numeric|min:1']);
        $wallet = Wallet::firstOrCreate(
            ['account_id' => auth()->user()->account_id],
            ['currency' => 'SAR', 'available_balance' => 0]
        );
        $wallet->increment('available_balance', $r->amount);
        $wallet->refresh();
        WalletLedgerEntry::create([
            'wallet_id' => $wallet->id,
            'type' => 'topup',
            'amount' => $r->amount,
            'running_balance' => $wallet->available_balance,
            'description' => 'شحن محفظة',
            'created_at' => now(),
        ]);
        return back()->with('success', 'تم شحن ' . $r->amount . ' ر.س');
    }

    public function hold(Request $r) {
        $r->validate(['amount'=>'required|numeric|min:1']);
        $wallet = Wallet::where('account_id', auth()->user()->account_id)->first();
        if ($wallet) {
            $amount = (float) $r->amount;
            if ((float) $wallet->available_balance >= $amount) {
                $wallet->decrement('available_balance', $amount);
                $wallet->increment('locked_balance', $amount);
            }
        }
        return back()->with('warning', 'تم حجز ' . $r->amount . ' ر.س');
    }
}
