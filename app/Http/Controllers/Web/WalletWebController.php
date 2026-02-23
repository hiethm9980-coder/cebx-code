<?php
namespace App\Http\Controllers\Web;

use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletWebController extends WebController
{
    public function index()
    {
        $wallet = Wallet::where('account_id', auth()->user()->account_id)->firstOrNew();
        return view('pages.wallet.index', [
            'wallet' => $wallet,
            'transactions' => WalletLedgerEntry::where('wallet_id', $wallet->id ?? 0)->latest()->paginate(20),
            'paymentMethods' => PaymentMethod::where('account_id', auth()->user()->account_id)->get(),
        ]);
    }

    public function topup(Request $r)
    {
        $r->validate(['amount' => 'required|numeric|min:1|max:100000']);

        $wallet = Wallet::firstOrCreate(
            ['account_id' => auth()->user()->account_id],
            ['currency' => 'SAR', 'available_balance' => 0]
        );

        DB::transaction(function () use ($wallet, $r) {
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
        });

        return back()->with('success', 'تم شحن ' . number_format($r->amount, 2) . ' ر.س');
    }

    /**
     * ═══ FIX P1: hold() now creates a WalletLedgerEntry for audit trail ═══
     * BEFORE: Moved balance between available/locked WITHOUT any ledger record
     * AFTER:  Creates proper ledger entry for financial traceability
     */
    public function hold(Request $r)
    {
        $r->validate(['amount' => 'required|numeric|min:1']);

        $wallet = Wallet::where('account_id', auth()->user()->account_id)->first();

        if (!$wallet) {
            return back()->with('error', 'المحفظة غير موجودة');
        }

        $amount = (float) $r->amount;

        if ((float) $wallet->available_balance < $amount) {
            return back()->with('error', 'الرصيد غير كافٍ. المتاح: ' . number_format($wallet->available_balance, 2) . ' ر.س');
        }

        DB::transaction(function () use ($wallet, $amount) {
            $wallet->decrement('available_balance', $amount);
            $wallet->increment('locked_balance', $amount);
            $wallet->refresh();

            // ═══ FIX: Create LedgerEntry for the hold operation ═══
            WalletLedgerEntry::create([
                'wallet_id' => $wallet->id,
                'type' => 'hold',
                'amount' => $amount,
                'running_balance' => $wallet->available_balance,
                'description' => 'حجز رصيد',
                'created_at' => now(),
            ]);
        });

        return back()->with('warning', 'تم حجز ' . number_format($amount, 2) . ' ر.س');
    }
}
