<?php

use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\B2BAuthWebController;
use App\Http\Controllers\Web\B2CAuthWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\OrderWebController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\ShipmentWebController;
use App\Http\Controllers\Web\StoreWebController;
use App\Http\Controllers\Web\SupportWebController;
use App\Http\Controllers\Web\UserWebController;
use App\Http\Controllers\Web\WalletWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Shipping Gateway (Blade)
|--------------------------------------------------------------------------
*/

// توجيه الجذر إلى بوابة B2C
Route::get('/', fn () => redirect('/b2c/login'))->name('home');

// ── Auth (عامة بدون بادئة) ──
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

// ── Protected Routes (web guard + tenant so current_account_id is set for scoped models) ──
Route::middleware(['auth:web', 'tenant'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Shipments ──
    Route::get('/shipments', [ShipmentWebController::class, 'index'])->name('shipments.index');
    Route::post('/shipments', [ShipmentWebController::class, 'store'])->name('shipments.store');
    Route::get('/shipments/export', [ShipmentWebController::class, 'export'])->name('shipments.export');
    Route::get('/shipments/{shipment}', [ShipmentWebController::class, 'show'])->name('shipments.show');
    Route::patch('/shipments/{shipment}/cancel', [ShipmentWebController::class, 'cancel'])->name('shipments.cancel');
    Route::post('/shipments/{shipment}/return', [ShipmentWebController::class, 'createReturn'])->name('shipments.return');
    Route::get('/shipments/{shipment}/label', [ShipmentWebController::class, 'label'])->name('shipments.label');

    // ── Orders ──
    Route::get('/orders', [OrderWebController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderWebController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/ship', [OrderWebController::class, 'ship'])->name('orders.ship');
    Route::patch('/orders/{order}/cancel', [OrderWebController::class, 'cancel'])->name('orders.cancel');

    // ── Stores ──
    Route::get('/stores', [StoreWebController::class, 'index'])->name('stores.index');
    Route::post('/stores', [StoreWebController::class, 'store'])->name('stores.store');
    Route::post('/stores/{store}/sync', [StoreWebController::class, 'sync'])->name('stores.sync');
    Route::post('/stores/{store}/test', [StoreWebController::class, 'test'])->name('stores.test');
    Route::delete('/stores/{store}', [StoreWebController::class, 'destroy'])->name('stores.destroy');

    // ── Wallet ──
    Route::get('/wallet', [WalletWebController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletWebController::class, 'topup'])->name('wallet.topup');
    Route::post('/wallet/hold', [WalletWebController::class, 'hold'])->name('wallet.hold');

    // ── Users ──
    Route::get('/users', [UserWebController::class, 'index'])->name('users.index');
    Route::post('/users', [UserWebController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle', [UserWebController::class, 'toggle'])->name('users.toggle');
    Route::delete('/users/{user}', [UserWebController::class, 'destroy'])->name('users.destroy');

    // ── Support ──
    Route::get('/support', [SupportWebController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportWebController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [SupportWebController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportWebController::class, 'reply'])->name('support.reply');
    Route::patch('/support/{ticket}/resolve', [SupportWebController::class, 'resolve'])->name('support.resolve');

    // ── Roles ──
    Route::get('/roles', [PageController::class, 'roles'])->name('roles.index');
    Route::post('/roles', [PageController::class, 'rolesStore'])->name('roles.store');

    // ── Invitations ──
    Route::get('/invitations', [PageController::class, 'invitations'])->name('invitations.index');
    Route::post('/invitations', [PageController::class, 'invitationsStore'])->name('invitations.store');

    // ── Notifications ──
    Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [PageController::class, 'notificationsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [PageController::class, 'notificationsReadAll'])->name('notifications.readAll');

    // ── Addresses ──
    Route::get('/addresses', [PageController::class, 'addresses'])->name('addresses.index');
    Route::post('/addresses', [PageController::class, 'addressesStore'])->name('addresses.store');
    Route::patch('/addresses/{address}/default', [PageController::class, 'addressesDefault'])->name('addresses.default');
    Route::delete('/addresses/{address}', [PageController::class, 'addressesDestroy'])->name('addresses.destroy');

    // ── Settings ──
    Route::get('/settings', [PageController::class, 'settings'])->name('settings.index');
    Route::put('/settings', [PageController::class, 'settingsUpdate'])->name('settings.update');

    // ── Audit Log ──
    Route::get('/audit', [PageController::class, 'audit'])->name('audit.index');
    Route::get('/audit/export', [PageController::class, 'auditExport'])->name('audit.export');

    // ── Admin ──
    Route::get('/admin', [PageController::class, 'admin'])->name('admin.index');

    // ── Reports ──
    Route::get('/reports', [PageController::class, 'reports'])->name('reports.index');
    Route::get('/reports/export/{type}', [PageController::class, 'reportsExport'])->name('reports.export')->where('type', 'shipments|revenue|carriers|stores|operations|financial');

    // ── KYC ──
    Route::get('/kyc', [PageController::class, 'kyc'])->name('kyc.index');

    // ── Pricing ──
    Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing.index');
    Route::post('/pricing', [PageController::class, 'pricingStore'])->name('pricing.store');

    // ── Tracking ──
    Route::get('/tracking', [PageController::class, 'tracking'])->name('tracking.index');

    // ── Financial ──
    Route::get('/financial', [PageController::class, 'financial'])->name('financial.index');

    // ── Organizations ──
    Route::get('/organizations', [PageController::class, 'organizations'])->name('organizations.index');
    Route::post('/organizations', [PageController::class, 'organizationsStore'])->name('organizations.store');

    // ── Risk ──
    Route::get('/risk', [PageController::class, 'risk'])->name('risk.index');

    // ── DG ──
    Route::get('/dg', [PageController::class, 'dg'])->name('dg.index');

    // ── Phase 2 Modules ──
    Route::get('/containers', [PageController::class, 'containers'])->name('containers.index');
    Route::get('/customs', [PageController::class, 'customs'])->name('customs.index');
    Route::get('/drivers', [PageController::class, 'drivers'])->name('drivers.index');
    Route::get('/claims', [PageController::class, 'claims'])->name('claims.index');
    Route::get('/vessels', [PageController::class, 'vessels'])->name('vessels.index');
    Route::get('/schedules', [PageController::class, 'schedules'])->name('schedules.index');
    Route::get('/branches', [PageController::class, 'branches'])->name('branches.index');
    Route::get('/companies', [PageController::class, 'companies'])->name('companies.index');
    Route::get('/hscodes', [PageController::class, 'hscodes'])->name('hscodes.index');
});

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
