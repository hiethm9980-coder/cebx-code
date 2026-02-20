<?php

use App\Http\Controllers\Web\B2BAuthWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FIX P2-3: B2B Web Routes
|--------------------------------------------------------------------------
|
| بوابة B2B — للحسابات من نوع organization.
| prefix: /b2b
| name prefix: b2b.
| middleware: portal:b2b, ensureAccountType:organization
|
| طريقة التشغيل:
| في RouteServiceProvider أو bootstrap/app.php:
|   require base_path('routes/web_b2b.php');
|
*/

// ── B2B Auth (بدون حماية) ────────────────────────────────────
Route::prefix('b2b')->name('b2b.')->middleware('portal:b2b')->group(function () {

    // صفحات تسجيل الدخول (Guest فقط)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [B2BAuthWebController::class, 'showLogin'])->name('login');
        Route::post('/login', [B2BAuthWebController::class, 'login'])->name('login.submit');
    });

    // تسجيل الخروج
    Route::post('/logout', [B2BAuthWebController::class, 'logout'])
        ->middleware('auth:web')
        ->name('logout');

    // ── B2B Protected Routes ─────────────────────────────────
    Route::middleware(['auth:web', 'tenant', 'ensureAccountType:organization'])->group(function () {

        // Dashboard
        Route::get('/dashboard', function () {
            return view('b2b.dashboard');
        })->name('dashboard');

        // ── الشحنات (Shipments) ──────────────────────────
        Route::prefix('shipments')->name('shipments.')->group(function () {
            Route::get('/', function () {
                return view('b2b.shipments.index');
            })->name('index');

            Route::get('/create', function () {
                return view('b2b.shipments.create');
            })->name('create');

            Route::get('/{id}', function ($id) {
                return view('b2b.shipments.show', ['shipmentId' => $id]);
            })->name('show');
        });

        // ── الطلبات (Orders) ─────────────────────────────
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', function () {
                return view('b2b.orders.index');
            })->name('index');

            Route::get('/{id}', function ($id) {
                return view('b2b.orders.show', ['orderId' => $id]);
            })->name('show');
        });

        // ── المتاجر (Stores) ─────────────────────────────
        Route::prefix('stores')->name('stores.')->group(function () {
            Route::get('/', function () {
                return view('b2b.stores.index');
            })->name('index');
        });

        // ── المستخدمون والأدوار (Users & RBAC) ──────────
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', function () {
                return view('b2b.users.index');
            })->name('index');
        });

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', function () {
                return view('b2b.roles.index');
            })->name('index');
        });

        // ── الدعوات (Invitations) ────────────────────────
        Route::prefix('invitations')->name('invitations.')->group(function () {
            Route::get('/', function () {
                return view('b2b.invitations.index');
            })->name('index');
        });

        // ── المحفظة (Wallet) ─────────────────────────────
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', function () {
                return view('b2b.wallet.index');
            })->name('index');
        });

        // ── التقارير (Reports) ───────────────────────────
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', function () {
                return view('b2b.reports.index');
            })->name('index');
        });

        // ── الإعدادات (Settings) ─────────────────────────
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', function () {
                return view('b2b.settings.index');
            })->name('index');
        });
    });
});
