<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ShipmentWebController;
use App\Http\Controllers\Web\OrderWebController;
use App\Http\Controllers\Web\StoreWebController;
use App\Http\Controllers\Web\WalletWebController;
use App\Http\Controllers\Web\UserWebController;
use App\Http\Controllers\Web\SupportWebController;
use App\Http\Controllers\Web\PageController;

/*
|--------------------------------------------------------------------------
| Web Routes — Shipping Gateway (Blade)
|--------------------------------------------------------------------------
| ═══ FIX P0-B1: Added permission middleware to ALL routes ═══
| BEFORE: Only auth:web + tenant — any logged-in user could access everything
| AFTER:  Each route group enforced by module-level permission checks
|--------------------------------------------------------------------------
*/

// ── Auth ──
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

// ── Protected Routes ──
Route::middleware(['auth:web', 'tenant'])->group(function () {

    // Dashboard — accessible to all authenticated users
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Shipments ── (permission: shipments.read, shipments.create, etc.)
    Route::middleware('permission:shipments.read')->group(function () {
        Route::get('/shipments', [ShipmentWebController::class, 'index'])->name('shipments.index');
        Route::get('/shipments/export', [ShipmentWebController::class, 'export'])->name('shipments.export');
        Route::get('/shipments/{shipment}', [ShipmentWebController::class, 'show'])->name('shipments.show');
        Route::get('/shipments/{shipment}/label', [ShipmentWebController::class, 'label'])->name('shipments.label');
    });
    Route::post('/shipments', [ShipmentWebController::class, 'store'])->name('shipments.store')->middleware('permission:shipments.create');
    Route::patch('/shipments/{shipment}/cancel', [ShipmentWebController::class, 'cancel'])->name('shipments.cancel')->middleware('permission:shipments.cancel');
    Route::post('/shipments/{shipment}/return', [ShipmentWebController::class, 'createReturn'])->name('shipments.return')->middleware('permission:shipments.create_return');

    // ── Orders ── (permission: orders.read, orders.create, etc.)
    Route::middleware('permission:orders.read')->group(function () {
        Route::get('/orders', [OrderWebController::class, 'index'])->name('orders.index');
    });
    Route::post('/orders', [OrderWebController::class, 'store'])->name('orders.store')->middleware('permission:orders.create');
    Route::post('/orders/{order}/ship', [OrderWebController::class, 'ship'])->name('orders.ship')->middleware('permission:orders.ship');
    Route::patch('/orders/{order}/cancel', [OrderWebController::class, 'cancel'])->name('orders.cancel')->middleware('permission:orders.cancel');

    // ── Stores ── (permission: stores.read, stores.create, etc.)
    Route::middleware('permission:stores.read')->group(function () {
        Route::get('/stores', [StoreWebController::class, 'index'])->name('stores.index');
    });
    Route::post('/stores', [StoreWebController::class, 'store'])->name('stores.store')->middleware('permission:stores.create');
    Route::post('/stores/{store}/sync', [StoreWebController::class, 'sync'])->name('stores.sync')->middleware('permission:stores.sync');
    Route::post('/stores/{store}/test', [StoreWebController::class, 'test'])->name('stores.test')->middleware('permission:stores.test_connection');
    Route::delete('/stores/{store}', [StoreWebController::class, 'destroy'])->name('stores.destroy')->middleware('permission:stores.delete');

    // ── Wallet ── (permission: wallet.read, wallet.topup, wallet.hold)
    Route::get('/wallet', [WalletWebController::class, 'index'])->name('wallet.index')->middleware('permission:wallet.read');
    Route::post('/wallet/topup', [WalletWebController::class, 'topup'])->name('wallet.topup')->middleware('permission:wallet.topup');
    Route::post('/wallet/hold', [WalletWebController::class, 'hold'])->name('wallet.hold')->middleware('permission:wallet.hold');

    // ── Users ── (permission: users.read, users.create, etc.)
    Route::middleware('permission:users.read')->group(function () {
        Route::get('/users', [UserWebController::class, 'index'])->name('users.index');
    });
    Route::post('/users', [UserWebController::class, 'store'])->name('users.store')->middleware('permission:users.create');
    Route::patch('/users/{user}/toggle', [UserWebController::class, 'toggle'])->name('users.toggle')->middleware('permission:users.toggle_status');
    Route::delete('/users/{user}', [UserWebController::class, 'destroy'])->name('users.destroy')->middleware('permission:users.delete');

    // ── Support ── (permission: support.read, support.create, etc.)
    Route::middleware('permission:support.read')->group(function () {
        Route::get('/support', [SupportWebController::class, 'index'])->name('support.index');
        Route::get('/support/{ticket}', [SupportWebController::class, 'show'])->name('support.show');
    });
    Route::post('/support', [SupportWebController::class, 'store'])->name('support.store')->middleware('permission:support.create');
    Route::post('/support/{ticket}/reply', [SupportWebController::class, 'reply'])->name('support.reply')->middleware('permission:support.reply');
    Route::patch('/support/{ticket}/resolve', [SupportWebController::class, 'resolve'])->name('support.resolve')->middleware('permission:support.resolve');

    // ── Roles ── (permission: roles.read, roles.create)
    Route::get('/roles', [PageController::class, 'roles'])->name('roles.index')->middleware('permission:roles.read');
    Route::post('/roles', [PageController::class, 'rolesStore'])->name('roles.store')->middleware('permission:roles.create');

    // ── Invitations ── (permission: invitations.read, invitations.create)
    Route::get('/invitations', [PageController::class, 'invitations'])->name('invitations.index')->middleware('permission:invitations.read');
    Route::post('/invitations', [PageController::class, 'invitationsStore'])->name('invitations.store')->middleware('permission:invitations.create');

    // ── Notifications ── (permission: notifications.read)
    Route::middleware('permission:notifications.read')->group(function () {
        Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [PageController::class, 'notificationsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [PageController::class, 'notificationsReadAll'])->name('notifications.readAll');
    });

    // ── Addresses ── (permission: addresses.read, addresses.create, etc.)
    Route::get('/addresses', [PageController::class, 'addresses'])->name('addresses.index')->middleware('permission:addresses.read');
    Route::post('/addresses', [PageController::class, 'addressesStore'])->name('addresses.store')->middleware('permission:addresses.create');
    Route::patch('/addresses/{address}/default', [PageController::class, 'addressesDefault'])->name('addresses.default')->middleware('permission:addresses.set_default');
    Route::delete('/addresses/{address}', [PageController::class, 'addressesDestroy'])->name('addresses.destroy')->middleware('permission:addresses.delete');

    // ── Settings ── (permission: settings.read, settings.update)
    Route::get('/settings', [PageController::class, 'settings'])->name('settings.index')->middleware('permission:settings.read');
    Route::put('/settings', [PageController::class, 'settingsUpdate'])->name('settings.update')->middleware('permission:settings.update');

    // ── Audit Log ── (permission: audit_log.read, audit_log.export)
    Route::get('/audit', [PageController::class, 'audit'])->name('audit.index')->middleware('permission:audit_log.read');
    Route::get('/audit/export', [PageController::class, 'auditExport'])->name('audit.export')->middleware('permission:audit_log.export');

    // ── Admin ── (permission: admin.system_health)
    Route::get('/admin', [PageController::class, 'admin'])->name('admin.index')->middleware('permission:admin.system_health');

    // ── Reports ── (permission: reports.read, reports.export)
    Route::get('/reports', [PageController::class, 'reports'])->name('reports.index')->middleware('permission:reports.read');
    Route::get('/reports/export/{type}', [PageController::class, 'reportsExport'])->name('reports.export')->where('type', 'shipments|revenue|carriers|stores|operations|financial')->middleware('permission:reports.export');

    // ── KYC ── (permission: kyc.read)
    Route::get('/kyc', [PageController::class, 'kyc'])->name('kyc.index')->middleware('permission:kyc.read');

    // ── Pricing ── (permission: pricing.read, pricing.create)
    Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing.index')->middleware('permission:pricing.read');
    Route::post('/pricing', [PageController::class, 'pricingStore'])->name('pricing.store')->middleware('permission:pricing.create');

    // ── Tracking ── (permission: tracking.read)
    Route::get('/tracking', [PageController::class, 'tracking'])->name('tracking.index')->middleware('permission:tracking.read');

    // ── Financial ── (permission: financial.read)
    Route::get('/financial', [PageController::class, 'financial'])->name('financial.index')->middleware('permission:financial.read');

    // ── Organizations ── (permission: organizations.read, organizations.create)
    Route::get('/organizations', [PageController::class, 'organizations'])->name('organizations.index')->middleware('permission:organizations.read');
    Route::post('/organizations', [PageController::class, 'organizationsStore'])->name('organizations.store')->middleware('permission:organizations.create');

    // ── Risk ── (permission: admin.system_health)
    Route::get('/risk', [PageController::class, 'risk'])->name('risk.index')->middleware('permission:admin.system_health');

    // ── DG ── (permission: dg.read)
    Route::get('/dg', [PageController::class, 'dg'])->name('dg.index')->middleware('permission:dg.read');

    // ── Phase 2 Modules ──
    Route::get('/containers', [PageController::class, 'containers'])->name('containers.index')->middleware('permission:containers.read');
    Route::get('/customs', [PageController::class, 'customs'])->name('customs.index')->middleware('permission:customs.read');
    Route::get('/drivers', [PageController::class, 'drivers'])->name('drivers.index')->middleware('permission:drivers.read');
    Route::get('/claims', [PageController::class, 'claims'])->name('claims.index')->middleware('permission:claims.read');
    Route::get('/vessels', [PageController::class, 'vessels'])->name('vessels.index')->middleware('permission:vessels.read');
    Route::get('/schedules', [PageController::class, 'schedules'])->name('schedules.index')->middleware('permission:vessels.read');
    Route::get('/branches', [PageController::class, 'branches'])->name('branches.index')->middleware('permission:branches.read');
    Route::get('/companies', [PageController::class, 'companies'])->name('companies.index')->middleware('permission:companies.read');
    Route::get('/hscodes', [PageController::class, 'hscodes'])->name('hscodes.index')->middleware('permission:hs_codes.search');
});
