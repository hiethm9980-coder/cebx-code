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
| Web Routes — Shipping Gateway (B2C + B2B + Admin Blade)
|--------------------------------------------------------------------------
*/

// ── Auth ──
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

// ── Protected Routes ──
Route::middleware(['auth:web', 'tenant'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Shipments ──
    Route::get('/shipments', [ShipmentWebController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [ShipmentWebController::class, 'create'])->name('shipments.create');
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
    Route::get('/stores/{store}/edit', [StoreWebController::class, 'edit'])->name('stores.edit');
    Route::post('/stores/{store}/sync', [StoreWebController::class, 'sync'])->name('stores.sync');
    Route::delete('/stores/{store}', [StoreWebController::class, 'destroy'])->name('stores.disconnect');

    // ── Wallet ──
    Route::get('/wallet', [WalletWebController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletWebController::class, 'topup'])->name('wallet.topup');

    // ── Users ──
    Route::get('/users', [UserWebController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserWebController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [UserWebController::class, 'update'])->name('users.update');

    // ── Support ──
    Route::get('/support', [SupportWebController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportWebController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [SupportWebController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportWebController::class, 'reply'])->name('support.reply');
    Route::patch('/support/{ticket}/resolve', [SupportWebController::class, 'resolve'])->name('support.resolve');

    // ── Tracking ──
    Route::get('/tracking', [PageController::class, 'tracking'])->name('tracking.index');

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
    Route::post('/settings/password', [PageController::class, 'settingsPassword'])->name('settings.password');

    // ── Reports ──
    Route::get('/reports', [PageController::class, 'reports'])->name('reports.index');
    Route::get('/reports/export/{type}', [PageController::class, 'reportsExport'])->name('reports.export');

    // ── Financial ──
    Route::get('/financial', [PageController::class, 'financial'])->name('financial.index');

    // ── Audit ──
    Route::get('/audit', [PageController::class, 'audit'])->name('audit.index');
    Route::get('/audit/export', [PageController::class, 'auditExport'])->name('audit.export');

    // ── Pricing ──
    Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing.index');

    // ── Admin ──
    Route::get('/admin', [PageController::class, 'admin'])->name('admin.index');

    // ── KYC ──
    Route::get('/kyc', [PageController::class, 'kyc'])->name('kyc.index');

    // ── DG (Dangerous Goods) ──
    Route::get('/dg', [PageController::class, 'dg'])->name('dg.index');

    // ── Organizations ──
    Route::get('/organizations', [PageController::class, 'organizations'])->name('organizations.index');
    Route::post('/organizations', [PageController::class, 'organizationsStore'])->name('organizations.store');

    // ── Containers ──
    Route::get('/containers', [PageController::class, 'containers'])->name('containers.index');

    // ── Customs ──
    Route::get('/customs', [PageController::class, 'customs'])->name('customs.index');

    // ── Drivers ──
    Route::get('/drivers', [PageController::class, 'drivers'])->name('drivers.index');

    // ── Claims ──
    Route::get('/claims', [PageController::class, 'claims'])->name('claims.index');

    // ── Vessels ──
    Route::get('/vessels', [PageController::class, 'vessels'])->name('vessels.index');

    // ── Schedules ──
    Route::get('/schedules', [PageController::class, 'schedules'])->name('schedules.index');

    // ── Branches ──
    Route::get('/branches', [PageController::class, 'branches'])->name('branches.index');

    // ── Companies ──
    Route::get('/companies', [PageController::class, 'companies'])->name('companies.index');

    // ── HS Codes ──
    Route::get('/hscodes', [PageController::class, 'hscodes'])->name('hscodes.index');

    // ── Risk ──
    Route::get('/risk', [PageController::class, 'risk'])->name('risk.index');
});
