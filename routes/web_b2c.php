<?php

use App\Http\Controllers\Web\B2CAuthWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FIX P2-3: B2C Web Routes
|--------------------------------------------------------------------------
|
| بوابة B2C — للحسابات الفردية (individual).
| prefix: /b2c
| name prefix: b2c.
| middleware: portal:b2c, ensureAccountType:individual
|
| ملاحظة: B2C لا يتضمن Orders/Stores/Invitations/Users RBAC
|
*/

// ── B2C Auth (بدون حماية) ────────────────────────────────────
Route::prefix('b2c')->name('b2c.')->middleware('portal:b2c')->group(function () {

    // صفحات تسجيل الدخول (Guest فقط)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [B2CAuthWebController::class, 'showLogin'])->name('login');
        Route::post('/login', [B2CAuthWebController::class, 'login'])->name('login.submit');
    });

    // تسجيل الخروج
    Route::post('/logout', [B2CAuthWebController::class, 'logout'])
        ->middleware('auth:web')
        ->name('logout');

    // ── B2C Protected Routes ─────────────────────────────────
    Route::middleware(['auth:web', 'tenant', 'ensureAccountType:individual'])->group(function () {

        // Dashboard
        Route::get('/dashboard', function () {
            return view('b2c.dashboard');
        })->name('dashboard');

        // ── الشحنات المباشرة (Direct Shipments) ─────────
        Route::prefix('shipments')->name('shipments.')->group(function () {
            Route::get('/', function () {
                return view('b2c.shipments.index');
            })->name('index');

            Route::get('/create', function () {
                return view('b2c.shipments.create');
            })->name('create');

            Route::get('/{id}', function ($id) {
                return view('b2c.shipments.show', ['shipmentId' => $id]);
            })->name('show');
        });

        // ── التتبع (Tracking) ────────────────────────────
        Route::prefix('tracking')->name('tracking.')->group(function () {
            Route::get('/', function () {
                return view('b2c.tracking.index');
            })->name('index');

            Route::get('/{trackingNumber}', function ($trackingNumber) {
                return view('b2c.tracking.show', ['trackingNumber' => $trackingNumber]);
            })->name('show');
        });

        // ── المحفظة والمدفوعات (Wallet/Payments) ────────
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', function () {
                return view('b2c.wallet.index');
            })->name('index');
        });

        // ── دفتر العناوين (Addresses) ───────────────────
        Route::prefix('addresses')->name('addresses.')->group(function () {
            Route::get('/', function () {
                return view('b2c.addresses.index');
            })->name('index');
        });

        // ── الدعم (Support) ──────────────────────────────
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', function () {
                return view('b2c.support.index');
            })->name('index');
        });

        // ── الإعدادات (Settings) ─────────────────────────
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', function () {
                return view('b2c.settings.index');
            })->name('index');
        });
    });
});
