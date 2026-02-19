<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AccountTypeController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\FinancialDataController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\AccountSettingsController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\WalletBillingController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\RateController;
use App\Http\Controllers\Api\V1\CarrierController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PricingController;
use App\Http\Controllers\Api\V1\KycComplianceController;
use App\Http\Controllers\Api\V1\BillingWalletController;
use App\Http\Controllers\Api\V1\DgComplianceController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\VesselScheduleController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ShipmentWorkflowController;
use App\Http\Controllers\Api\V1\LastMileDeliveryController;
use App\Http\Controllers\Api\V1\InsuranceController;
use App\Http\Controllers\Api\V1\SLAController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\ContentDeclarationController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CustomsController;
use App\Http\Controllers\Api\V1\ContainerController;
use App\Http\Controllers\Api\V1\ClaimController;
use App\Http\Controllers\Api\V1\RiskController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\IncotermController;
use App\Http\Controllers\Api\V1\HsCodeController;
use App\Http\Controllers\Api\V1\TariffController;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
|
| FR-IAM-001: Multi-tenant account registration
| FR-IAM-002: User management within account
| FR-IAM-003: RBAC — Roles & Permissions management
| FR-IAM-010: Account types (individual/organization)
| FR-IAM-011: Invitation system for users
| FR-IAM-006: Audit Log (comprehensive, append-only)
| FR-IAM-013: Organization/team audit context
| FR-IAM-012: Financial data masking
| FR-IAM-014: KYC status display & capabilities
| FR-IAM-016: KYC document access restriction
| FR-IAM-008: Account settings management
| FR-IAM-009: Multi-store management
| FR-IAM-017+019+020: Wallet/billing permissions & payment masking
| FR-ST-001→010: Store integrations & order sync
|
*/

// ── Public Routes ─────────────────────────────────────────────────
Route::prefix('v1')->middleware('throttle:10,1')->group(function () {

    // Authentication (rate limited: 10 attempts per minute)
    Route::post('/login', [AuthController::class, 'login'])
         ->middleware('throttle:5,1')
         ->name('api.v1.login');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
         ->middleware('throttle:3,1')
         ->name('api.v1.forgot-password');

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
         ->middleware('throttle:5,1')
         ->name('api.v1.reset-password');

    // Account Registration (public)
    Route::post('/register', [AccountController::class, 'register'])
         ->name('api.v1.register');

    // ── FR-IAM-011: Invitation Public Endpoints (invitee) ────────
    Route::get('/invitations/preview/{token}', [InvitationController::class, 'preview'])
         ->name('api.v1.invitations.preview');

    Route::post('/invitations/accept/{token}', [InvitationController::class, 'accept'])
         ->name('api.v1.invitations.accept');

});

// ── Authenticated + Tenant-scoped Routes ──────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {

    // ── Authentication (authenticated) ───────────────────────────
    Route::post('/logout', [AuthController::class, 'logout'])
         ->name('api.v1.logout');

    Route::post('/logout-all', [AuthController::class, 'logoutAll'])
         ->name('api.v1.logout-all');

    Route::get('/me', [AuthController::class, 'me'])
         ->name('api.v1.me');

    Route::put('/change-password', [AuthController::class, 'changePassword'])
         ->name('api.v1.change-password');

    // ── FR-IAM-001: Account ───────────────────────────────────────
    Route::get('/account', [AccountController::class, 'show'])
         ->name('api.v1.account.show');

    // ── FR-IAM-008: Account Settings ─────────────────────────────
    Route::get('/account/settings', [AccountSettingsController::class, 'show'])
         ->name('api.v1.account.settings');

    Route::put('/account/settings', [AccountSettingsController::class, 'update'])
         ->name('api.v1.account.settings.update');

    Route::post('/account/settings/reset', [AccountSettingsController::class, 'reset'])
         ->name('api.v1.account.settings.reset');

    Route::get('/account/settings/options', [AccountSettingsController::class, 'options'])
         ->name('api.v1.account.settings.options');

    // ── FR-IAM-010: Account Type, Organization Profile, KYC ──────
    Route::get('/account/type', [AccountTypeController::class, 'show'])
         ->name('api.v1.account.type');

    Route::post('/account/type-change', [AccountTypeController::class, 'requestTypeChange'])
         ->name('api.v1.account.type-change');

    Route::get('/account/organization', [AccountTypeController::class, 'organizationProfile'])
         ->name('api.v1.account.organization');

    Route::put('/account/organization', [AccountTypeController::class, 'updateOrganizationProfile'])
         ->name('api.v1.account.organization.update');

    Route::get('/account/kyc', [AccountTypeController::class, 'kycStatus'])
         ->name('api.v1.account.kyc');

    Route::post('/account/kyc/submit', [AccountTypeController::class, 'submitKyc'])
         ->name('api.v1.account.kyc.submit');

    // ── FR-IAM-002: User Management ──────────────────────────────
    Route::get('/users/changelog', [UserController::class, 'changelog'])
         ->name('api.v1.users.changelog');

    Route::apiResource('users', UserController::class)
         ->names('api.v1.users');

    Route::patch('/users/{id}/disable', [UserController::class, 'disable'])
         ->name('api.v1.users.disable');

    Route::patch('/users/{id}/enable', [UserController::class, 'enable'])
         ->name('api.v1.users.enable');

    // ── FR-IAM-003: RBAC — Roles & Permissions ───────────────────
    Route::get('/permissions', [RoleController::class, 'permissionsCatalog'])
         ->name('api.v1.permissions.catalog');

    Route::get('/roles/templates', [RoleController::class, 'templates'])
         ->name('api.v1.roles.templates');

    Route::post('/roles/from-template', [RoleController::class, 'createFromTemplate'])
         ->name('api.v1.roles.from-template');

    Route::apiResource('roles', RoleController::class)
         ->names('api.v1.roles');

    Route::post('/roles/{roleId}/assign/{userId}', [RoleController::class, 'assignToUser'])
         ->name('api.v1.roles.assign');

    Route::delete('/roles/{roleId}/revoke/{userId}', [RoleController::class, 'revokeFromUser'])
         ->name('api.v1.roles.revoke');

    Route::get('/users/{id}/permissions', [RoleController::class, 'userPermissions'])
         ->name('api.v1.users.permissions');

    // ── FR-IAM-011: Invitation Management (authenticated) ────────
    Route::get('/invitations', [InvitationController::class, 'index'])
         ->name('api.v1.invitations.index');

    Route::post('/invitations', [InvitationController::class, 'store'])
         ->name('api.v1.invitations.store');

    Route::get('/invitations/{id}', [InvitationController::class, 'show'])
         ->name('api.v1.invitations.show');

    Route::patch('/invitations/{id}/cancel', [InvitationController::class, 'cancel'])
         ->name('api.v1.invitations.cancel');

    Route::post('/invitations/{id}/resend', [InvitationController::class, 'resend'])
         ->name('api.v1.invitations.resend');

    // ── FR-IAM-009: Multi-Store Management ──────────────────────
    Route::get('/stores/stats', [StoreController::class, 'stats'])
         ->name('api.v1.stores.stats');

    Route::get('/stores', [StoreController::class, 'index'])
         ->name('api.v1.stores.index');

    Route::post('/stores', [StoreController::class, 'store'])
         ->name('api.v1.stores.store');

    Route::get('/stores/{id}', [StoreController::class, 'show'])
         ->name('api.v1.stores.show');

    Route::put('/stores/{id}', [StoreController::class, 'update'])
         ->name('api.v1.stores.update');

    Route::delete('/stores/{id}', [StoreController::class, 'destroy'])
         ->name('api.v1.stores.destroy');

    Route::post('/stores/{id}/set-default', [StoreController::class, 'setDefault'])
         ->name('api.v1.stores.set-default');

    Route::post('/stores/{id}/toggle-status', [StoreController::class, 'toggleStatus'])
         ->name('api.v1.stores.toggle-status');

    // ── ST Module: Store Connections & Sync ───────────────────────
    Route::post('/stores/{id}/test-connection', [OrderController::class, 'testConnection'])
         ->name('api.v1.stores.test-connection');

    Route::post('/stores/{id}/register-webhooks', [OrderController::class, 'registerWebhooks'])
         ->name('api.v1.stores.register-webhooks');

    Route::post('/stores/{id}/sync', [OrderController::class, 'syncStore'])
         ->name('api.v1.stores.sync');

    // ── ST Module: Orders ─────────────────────────────────────────
    Route::get('/orders', [OrderController::class, 'index'])
         ->name('api.v1.orders.index');

    Route::get('/orders/stats', [OrderController::class, 'stats'])
         ->name('api.v1.orders.stats');

    Route::get('/orders/{id}', [OrderController::class, 'show'])
         ->name('api.v1.orders.show');

    Route::post('/orders', [OrderController::class, 'store'])
         ->name('api.v1.orders.store');

    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])
         ->name('api.v1.orders.update-status');

    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])
         ->name('api.v1.orders.cancel');

    // ── FR-IAM-014 + FR-IAM-016: KYC Status & Documents ─────────
    Route::get('/kyc/status', [KycController::class, 'status'])
         ->name('api.v1.kyc.status');

    Route::post('/kyc/approve', [KycController::class, 'approve'])
         ->name('api.v1.kyc.approve');

    Route::post('/kyc/reject', [KycController::class, 'reject'])
         ->name('api.v1.kyc.reject');

    Route::post('/kyc/resubmit', [KycController::class, 'resubmit'])
         ->name('api.v1.kyc.resubmit');

    Route::get('/kyc/documents', [KycController::class, 'listDocuments'])
         ->name('api.v1.kyc.documents');

    Route::post('/kyc/documents/upload', [KycController::class, 'uploadDocument'])
         ->name('api.v1.kyc.documents.upload');

    Route::get('/kyc/documents/{id}/download', [KycController::class, 'downloadDocument'])
         ->name('api.v1.kyc.documents.download');

    Route::delete('/kyc/documents/{id}', [KycController::class, 'purgeDocument'])
         ->name('api.v1.kyc.documents.purge');

    // ── FR-IAM-012: Financial Data Masking ───────────────────────
    Route::get('/financial/visibility', [FinancialDataController::class, 'visibility'])
         ->name('api.v1.financial.visibility');

    Route::get('/financial/sensitive-fields', [FinancialDataController::class, 'sensitiveFields'])
         ->name('api.v1.financial.sensitive-fields');

    Route::post('/financial/mask-card', [FinancialDataController::class, 'maskCard'])
         ->name('api.v1.financial.mask-card');

    Route::post('/financial/filter', [FinancialDataController::class, 'filterData'])
         ->name('api.v1.financial.filter');

    // ── FR-IAM-017+019+020: Wallet & Billing ─────────────────────
    Route::get('/wallet', [WalletBillingController::class, 'wallet'])
         ->name('api.v1.wallet');

    Route::get('/wallet/ledger', [WalletBillingController::class, 'ledger'])
         ->name('api.v1.wallet.ledger');

    Route::post('/wallet/topup', [WalletBillingController::class, 'topUp'])
         ->name('api.v1.wallet.topup');

    Route::put('/wallet/threshold', [WalletBillingController::class, 'configureThreshold'])
         ->name('api.v1.wallet.threshold');

    Route::get('/wallet/permissions', [WalletBillingController::class, 'permissions'])
         ->name('api.v1.wallet.permissions');

    Route::get('/billing/methods', [WalletBillingController::class, 'paymentMethods'])
         ->name('api.v1.billing.methods');

    Route::post('/billing/methods', [WalletBillingController::class, 'addPaymentMethod'])
         ->name('api.v1.billing.methods.add');

    Route::delete('/billing/methods/{id}', [WalletBillingController::class, 'removePaymentMethod'])
         ->name('api.v1.billing.methods.remove');

    // ── SH Module: Shipments (FR-SH-001→019) ─────────────────────
    Route::get('/shipments', [ShipmentController::class, 'index'])
         ->name('api.v1.shipments.index');

    Route::get('/shipments/stats', [ShipmentController::class, 'stats'])
         ->name('api.v1.shipments.stats');

    Route::get('/shipments/{id}', [ShipmentController::class, 'show'])
         ->name('api.v1.shipments.show');

    Route::post('/shipments', [ShipmentController::class, 'store'])
         ->name('api.v1.shipments.store');

    Route::post('/shipments/from-order/{orderId}', [ShipmentController::class, 'createFromOrder'])
         ->name('api.v1.shipments.from-order');

    Route::post('/shipments/bulk', [ShipmentController::class, 'bulkCreate'])
         ->name('api.v1.shipments.bulk');

    Route::post('/shipments/{id}/validate', [ShipmentController::class, 'validate'])
         ->name('api.v1.shipments.validate');

    Route::put('/shipments/{id}/status', [ShipmentController::class, 'updateStatus'])
         ->name('api.v1.shipments.update-status');

    Route::post('/shipments/{id}/cancel', [ShipmentController::class, 'cancel'])
         ->name('api.v1.shipments.cancel');

    Route::get('/shipments/{id}/label', [ShipmentController::class, 'label'])
         ->name('api.v1.shipments.label');

    Route::post('/shipments/{id}/return', [ShipmentController::class, 'createReturn'])
         ->name('api.v1.shipments.return');

    Route::post('/shipments/{shipmentId}/parcels', [ShipmentController::class, 'addParcel'])
         ->name('api.v1.shipments.parcels.add');

    Route::delete('/shipments/{shipmentId}/parcels/{parcelId}', [ShipmentController::class, 'removeParcel'])
         ->name('api.v1.shipments.parcels.remove');

    // ── SH Module: Address Book (FR-SH-004) ───────────────────────
    Route::get('/addresses', [AddressController::class, 'index'])
         ->name('api.v1.addresses.index');

    Route::post('/addresses', [AddressController::class, 'store'])
         ->name('api.v1.addresses.store');

    Route::delete('/addresses/{id}', [AddressController::class, 'destroy'])
         ->name('api.v1.addresses.destroy');

    // ── RT Module: Rates & Pricing (FR-RT-001→012) ────────────────
    Route::post('/shipments/{shipmentId}/rates', [RateController::class, 'fetchRates'])
         ->name('api.v1.rates.fetch');

    Route::post('/shipments/{shipmentId}/reprice', [RateController::class, 'reprice'])
         ->name('api.v1.rates.reprice');

    Route::get('/rate-quotes/{quoteId}', [RateController::class, 'showQuote'])
         ->name('api.v1.rate-quotes.show');

    Route::post('/rate-quotes/{quoteId}/select', [RateController::class, 'selectOption'])
         ->name('api.v1.rate-quotes.select');

    Route::get('/pricing-rules', [RateController::class, 'listRules'])
         ->name('api.v1.pricing-rules.index');

    Route::post('/pricing-rules', [RateController::class, 'createRule'])
         ->name('api.v1.pricing-rules.store');

    Route::put('/pricing-rules/{id}', [RateController::class, 'updateRule'])
         ->name('api.v1.pricing-rules.update');

    Route::delete('/pricing-rules/{id}', [RateController::class, 'deleteRule'])
         ->name('api.v1.pricing-rules.destroy');

    // ── FR-IAM-006 + FR-IAM-013: Audit Log ──────────────────────
    Route::get('/audit-logs/categories', [AuditLogController::class, 'categories'])
         ->name('api.v1.audit-logs.categories');

    Route::get('/audit-logs/statistics', [AuditLogController::class, 'statistics'])
         ->name('api.v1.audit-logs.statistics');

    Route::post('/audit-logs/export', [AuditLogController::class, 'export'])
         ->name('api.v1.audit-logs.export');

    Route::get('/audit-logs/entity/{entityType}/{entityId}', [AuditLogController::class, 'entityTrail'])
         ->name('api.v1.audit-logs.entity-trail');

    Route::get('/audit-logs/trace/{requestId}', [AuditLogController::class, 'requestTrace'])
         ->name('api.v1.audit-logs.trace');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
         ->name('api.v1.audit-logs.index');

    Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])
         ->name('api.v1.audit-logs.show');

    // ═══════════════════════════════════════════════════════════════
    // CR Module — Carrier Integration & Labels (FR-CR-001→008)
    // ═══════════════════════════════════════════════════════════════

    // FR-CR-001: Create shipment at carrier
    Route::post('/shipments/{shipmentId}/carrier/create', [CarrierController::class, 'createAtCarrier'])
         ->name('api.v1.carrier.create');

    // FR-CR-005: Re-fetch label
    Route::post('/shipments/{shipmentId}/carrier/refetch', [CarrierController::class, 'refetchLabel'])
         ->name('api.v1.carrier.refetch-label');

    // FR-CR-006: Cancel at carrier
    Route::post('/shipments/{shipmentId}/carrier/cancel', [CarrierController::class, 'cancelAtCarrier'])
         ->name('api.v1.carrier.cancel');

    // FR-CR-003: Retry failed creation
    Route::post('/shipments/{shipmentId}/carrier/retry', [CarrierController::class, 'retryCreation'])
         ->name('api.v1.carrier.retry');

    // Carrier status
    Route::get('/shipments/{shipmentId}/carrier/status', [CarrierController::class, 'carrierStatus'])
         ->name('api.v1.carrier.status');

    // FR-CR-004: Carrier errors
    Route::get('/shipments/{shipmentId}/carrier/errors', [CarrierController::class, 'carrierErrors'])
         ->name('api.v1.carrier.errors');

    // FR-CR-008: Documents (list & download)
    Route::get('/shipments/{shipmentId}/documents', [CarrierController::class, 'listDocuments'])
         ->name('api.v1.carrier.documents');

    Route::get('/shipments/{shipmentId}/documents/{documentId}', [CarrierController::class, 'downloadDocument'])
         ->name('api.v1.carrier.document-download');

    // ═══════════════════════════════════════════════════════════════
    // TR Module — Tracking & Status Normalization (FR-TR-001→007)
    // ═══════════════════════════════════════════════════════════════

    // FR-TR-005: Tracking timeline
    Route::get('/shipments/{shipmentId}/tracking/timeline', [TrackingController::class, 'timeline'])
         ->name('api.v1.tracking.timeline');

    // FR-TR-005: Search/filter by status
    Route::get('/tracking/search', [TrackingController::class, 'search'])
         ->name('api.v1.tracking.search');

    // FR-TR-006: Status dashboard
    Route::get('/tracking/dashboard', [TrackingController::class, 'dashboard'])
         ->name('api.v1.tracking.dashboard');

    // FR-TR-004: Subscribe to tracking updates
    Route::post('/shipments/{shipmentId}/tracking/subscribe', [TrackingController::class, 'subscribe'])
         ->name('api.v1.tracking.subscribe');

    // FR-TR-004: Unsubscribe
    Route::delete('/tracking/subscriptions/{subscriptionId}', [TrackingController::class, 'unsubscribe'])
         ->name('api.v1.tracking.unsubscribe');

    // FR-TR-004: Status mappings
    Route::get('/tracking/status-mappings', [TrackingController::class, 'statusMappings'])
         ->name('api.v1.tracking.status-mappings');

    // FR-TR-001: Manual poll
    Route::post('/tracking/poll/{trackingNumber}', [TrackingController::class, 'manualPoll'])
         ->name('api.v1.tracking.manual-poll');

    // FR-TR-007: Exceptions
    Route::get('/shipments/{shipmentId}/exceptions', [TrackingController::class, 'exceptions'])
         ->name('api.v1.tracking.exceptions');

    Route::post('/exceptions/{exceptionId}/acknowledge', [TrackingController::class, 'acknowledgeException'])
         ->name('api.v1.tracking.exception-acknowledge');

    Route::post('/exceptions/{exceptionId}/resolve', [TrackingController::class, 'resolveException'])
         ->name('api.v1.tracking.exception-resolve');

    Route::post('/exceptions/{exceptionId}/escalate', [TrackingController::class, 'escalateException'])
         ->name('api.v1.tracking.exception-escalate');

    // ═══════════════════════════════════════════════════════════════
    // NTF Module — Notifications (FR-NTF-001→009)
    // ═══════════════════════════════════════════════════════════════

    // FR-NTF-008: Notification log
    Route::get('/notifications', [NotificationController::class, 'index'])
         ->name('api.v1.notifications.index');

    // FR-NTF-001: In-app notifications
    Route::get('/notifications/in-app', [NotificationController::class, 'inApp'])
         ->name('api.v1.notifications.in-app');

    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
         ->name('api.v1.notifications.unread-count');

    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])
         ->name('api.v1.notifications.mark-read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
         ->name('api.v1.notifications.read-all');

    // FR-NTF-003: Preferences
    Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences'])
         ->name('api.v1.notifications.preferences');

    Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences'])
         ->name('api.v1.notifications.update-preferences');

    // FR-NTF-004: Templates
    Route::get('/notifications/templates', [NotificationController::class, 'listTemplates'])
         ->name('api.v1.notifications.templates');

    Route::post('/notifications/templates', [NotificationController::class, 'createTemplate'])
         ->name('api.v1.notifications.create-template');

    Route::put('/notifications/templates/{templateId}', [NotificationController::class, 'updateTemplate'])
         ->name('api.v1.notifications.update-template');

    Route::post('/notifications/templates/{templateId}/preview', [NotificationController::class, 'previewTemplate'])
         ->name('api.v1.notifications.preview-template');

    // FR-NTF-009: Channels
    Route::get('/notifications/channels', [NotificationController::class, 'listChannels'])
         ->name('api.v1.notifications.channels');

    Route::post('/notifications/channels', [NotificationController::class, 'configureChannel'])
         ->name('api.v1.notifications.configure-channel');

    // FR-NTF-002: Test send
    Route::post('/notifications/test', [NotificationController::class, 'testSend'])
         ->name('api.v1.notifications.test');

    // FR-NTF-007: Schedules
    Route::post('/notifications/schedules', [NotificationController::class, 'createSchedule'])
         ->name('api.v1.notifications.create-schedule');

    Route::get('/notifications/schedules', [NotificationController::class, 'listSchedules'])
         ->name('api.v1.notifications.schedules');

    // ═══════════════════════════════════════════════════════════════
    // PAY Module — Payments & Subscriptions (FR-PAY-001→011)
    // ═══════════════════════════════════════════════════════════════

    // FR-PAY-001/004: Wallet top-up
    Route::post('/payments/topup', [PaymentController::class, 'topUp'])
         ->name('api.v1.payments.topup');

    // FR-PAY-001/002: Charge shipping
    Route::post('/payments/charge-shipping', [PaymentController::class, 'chargeShipping'])
         ->name('api.v1.payments.charge-shipping');

    // FR-PAY-008: Wallet & transactions
    Route::get('/payments/wallet', [PaymentController::class, 'walletSummary'])
         ->name('api.v1.payments.wallet');

    Route::get('/payments/transactions', [PaymentController::class, 'transactions'])
         ->name('api.v1.payments.transactions');

    // FR-PAY-010: Refund
    Route::post('/payments/refund', [PaymentController::class, 'refund'])
         ->name('api.v1.payments.refund');

    // FR-PAY-005: Invoices
    Route::get('/payments/invoices', [PaymentController::class, 'listInvoices'])
         ->name('api.v1.payments.invoices');

    Route::get('/payments/invoices/{invoiceId}', [PaymentController::class, 'getInvoice'])
         ->name('api.v1.payments.invoice');

    // FR-PAY-007: Promo codes
    Route::post('/payments/promo/validate', [PaymentController::class, 'validatePromo'])
         ->name('api.v1.payments.validate-promo');

    Route::post('/payments/promo', [PaymentController::class, 'createPromo'])
         ->name('api.v1.payments.create-promo');

    // FR-PAY-004: Gateways
    Route::get('/payments/gateways', [PaymentController::class, 'listGateways'])
         ->name('api.v1.payments.gateways');

    // FR-PAY-011: Balance alerts
    Route::post('/payments/balance-alerts', [PaymentController::class, 'setBalanceAlert'])
         ->name('api.v1.payments.set-alert');

    Route::get('/payments/balance-alerts', [PaymentController::class, 'getBalanceAlerts'])
         ->name('api.v1.payments.alerts');

    // FR-PAY-006: Tax calculator
    Route::get('/payments/tax-calculate', [PaymentController::class, 'calculateTax'])
         ->name('api.v1.payments.tax');

    // FR-PAY-003: Subscriptions
    Route::post('/subscriptions/subscribe', [PaymentController::class, 'subscribe'])
         ->name('api.v1.subscriptions.subscribe');

    Route::post('/subscriptions/cancel', [PaymentController::class, 'cancelSubscription'])
         ->name('api.v1.subscriptions.cancel');

    Route::post('/subscriptions/renew', [PaymentController::class, 'renewSubscription'])
         ->name('api.v1.subscriptions.renew');

    Route::get('/subscriptions/status', [PaymentController::class, 'subscriptionStatus'])
         ->name('api.v1.subscriptions.status');

    Route::get('/subscriptions/plans', [PaymentController::class, 'listPlans'])
         ->name('api.v1.subscriptions.plans');

    // ═══════════════════════════════════════════════════════════════
    // RPT Module — Reports & Analytics (FR-RPT-001→010)
    // ═══════════════════════════════════════════════════════════════

    // FR-RPT-001: Shipment dashboard
    Route::get('/reports/shipment-dashboard', [ReportController::class, 'shipmentDashboard'])
         ->name('api.v1.reports.shipment-dashboard');

    // FR-RPT-002: Profit report
    Route::get('/reports/profit', [ReportController::class, 'profitReport'])
         ->name('api.v1.reports.profit');

    // FR-RPT-003: Export
    Route::post('/reports/export', [ReportController::class, 'createExport'])
         ->name('api.v1.reports.export');

    Route::get('/reports/exports', [ReportController::class, 'listExports'])
         ->name('api.v1.reports.exports');

    // FR-RPT-004: Exception report
    Route::get('/reports/exceptions', [ReportController::class, 'exceptionReport'])
         ->name('api.v1.reports.exceptions');

    // FR-RPT-005: Operational & Financial
    Route::get('/reports/operational', [ReportController::class, 'operationalReport'])
         ->name('api.v1.reports.operational');

    Route::get('/reports/financial', [ReportController::class, 'financialReport'])
         ->name('api.v1.reports.financial');

    // FR-RPT-006: Grouped data
    Route::get('/reports/grouped', [ReportController::class, 'groupedData'])
         ->name('api.v1.reports.grouped');

    // FR-RPT-007: Charts & analytics
    Route::get('/reports/carrier-performance', [ReportController::class, 'carrierPerformance'])
         ->name('api.v1.reports.carrier-performance');

    Route::get('/reports/store-performance', [ReportController::class, 'storePerformance'])
         ->name('api.v1.reports.store-performance');

    Route::get('/reports/revenue', [ReportController::class, 'revenueChart'])
         ->name('api.v1.reports.revenue');

    // FR-RPT-008: Scheduled reports
    Route::post('/reports/schedules', [ReportController::class, 'createSchedule'])
         ->name('api.v1.reports.create-schedule');

    Route::get('/reports/schedules', [ReportController::class, 'listSchedules'])
         ->name('api.v1.reports.schedules');

    Route::delete('/reports/schedules/{scheduleId}', [ReportController::class, 'cancelSchedule'])
         ->name('api.v1.reports.cancel-schedule');

    // FR-RPT-009: Wallet report
    Route::get('/reports/wallet', [ReportController::class, 'walletReport'])
         ->name('api.v1.reports.wallet');

    // FR-RPT-010: Generic reports API
    Route::get('/reports/api/{type}', [ReportController::class, 'reportApi'])
         ->name('api.v1.reports.api');

    // Saved reports
    Route::post('/reports/saved', [ReportController::class, 'saveReport'])
         ->name('api.v1.reports.save');

    Route::get('/reports/saved', [ReportController::class, 'listSavedReports'])
         ->name('api.v1.reports.saved');

    // ═══════════════════════════════════════════════════════════════
    // ADM Module — Platform Administration (FR-ADM-001→010)
    // SECURITY: Protected with admin.access permission middleware
    // ═══════════════════════════════════════════════════════════════

    // FR-ADM-001: System settings
    Route::middleware('permission:admin.access')->group(function () {
    Route::get('/admin/settings/{group}', [AdminController::class, 'getSettings'])
         ->name('api.v1.admin.settings');
    Route::put('/admin/settings', [AdminController::class, 'updateSetting'])
         ->name('api.v1.admin.update-setting');
    Route::post('/admin/test-carrier', [AdminController::class, 'testCarrierConnection'])
         ->name('api.v1.admin.test-carrier');

    // FR-ADM-002/006: Health
    Route::get('/admin/integration-health', [AdminController::class, 'integrationHealth'])
         ->name('api.v1.admin.integration-health');
    Route::get('/admin/system-health', [AdminController::class, 'systemHealth'])
         ->name('api.v1.admin.system-health');

    // FR-ADM-003: Users
    Route::get('/admin/users', [AdminController::class, 'listUsers'])
         ->name('api.v1.admin.users');
    Route::post('/admin/users/{userId}/suspend', [AdminController::class, 'suspendUser'])
         ->name('api.v1.admin.suspend-user');
    Route::post('/admin/users/{userId}/activate', [AdminController::class, 'activateUser'])
         ->name('api.v1.admin.activate-user');

    // FR-ADM-005: Tax rules
    Route::get('/admin/tax-rules', [AdminController::class, 'listTaxRules'])
         ->name('api.v1.admin.tax-rules');
    Route::post('/admin/tax-rules', [AdminController::class, 'createTaxRule'])
         ->name('api.v1.admin.create-tax-rule');

    // FR-ADM-006: Role templates
    Route::get('/admin/role-templates', [AdminController::class, 'listRoleTemplates'])
         ->name('api.v1.admin.role-templates');
    Route::post('/admin/role-templates', [AdminController::class, 'createRoleTemplate'])
         ->name('api.v1.admin.create-role-template');

    // FR-ADM-008: Support tickets
    Route::post('/support/tickets', [AdminController::class, 'createTicket'])
         ->name('api.v1.support.create');
    Route::get('/support/tickets', [AdminController::class, 'listTickets'])
         ->name('api.v1.support.list');
    Route::get('/support/tickets/{ticketId}', [AdminController::class, 'getTicket'])
         ->name('api.v1.support.get');
    Route::post('/support/tickets/{ticketId}/reply', [AdminController::class, 'replyToTicket'])
         ->name('api.v1.support.reply');
    Route::post('/support/tickets/{ticketId}/assign', [AdminController::class, 'assignTicket'])
         ->name('api.v1.support.assign');
    Route::post('/support/tickets/{ticketId}/resolve', [AdminController::class, 'resolveTicket'])
         ->name('api.v1.support.resolve');

    // FR-ADM-009: API keys
    Route::post('/admin/api-keys', [AdminController::class, 'createApiKey'])
         ->name('api.v1.admin.create-api-key');
    Route::get('/admin/api-keys', [AdminController::class, 'listApiKeys'])
         ->name('api.v1.admin.api-keys');
    Route::delete('/admin/api-keys/{keyId}', [AdminController::class, 'revokeApiKey'])
         ->name('api.v1.admin.revoke-api-key');
    Route::post('/admin/api-keys/{keyId}/rotate', [AdminController::class, 'rotateApiKey'])
         ->name('api.v1.admin.rotate-api-key');

    // FR-ADM-010: Feature flags
    Route::get('/admin/feature-flags', [AdminController::class, 'listFeatureFlags'])
         ->name('api.v1.admin.feature-flags');
    Route::post('/admin/feature-flags', [AdminController::class, 'createFeatureFlag'])
         ->name('api.v1.admin.create-feature-flag');
    Route::put('/admin/feature-flags/{flagId}/toggle', [AdminController::class, 'toggleFeatureFlag'])
         ->name('api.v1.admin.toggle-feature-flag');
    Route::get('/admin/feature-flags/{key}/check', [AdminController::class, 'checkFeatureFlag'])
         ->name('api.v1.admin.check-feature-flag');
    }); // End admin.access middleware group

    // ═══════════════════════════════════════════════════════════════
    // ORG Module — Organizations & Teams (FR-ORG-001→010)
    // ═══════════════════════════════════════════════════════════════

    // FR-ORG-001: Create organization
    Route::post('/organizations', [OrganizationController::class, 'create'])
         ->name('api.v1.organizations.create');

    Route::get('/organizations', [OrganizationController::class, 'listForAccount'])
         ->name('api.v1.organizations.list');

    // FR-ORG-002: Profile management
    Route::get('/organizations/{orgId}', [OrganizationController::class, 'show'])
         ->name('api.v1.organizations.show');

    Route::put('/organizations/{orgId}', [OrganizationController::class, 'update'])
         ->name('api.v1.organizations.update');

    // FR-ORG-003: Invitations
    Route::post('/organizations/{orgId}/invites', [OrganizationController::class, 'invite'])
         ->name('api.v1.organizations.invite');

    Route::get('/organizations/{orgId}/invites', [OrganizationController::class, 'listInvites'])
         ->name('api.v1.organizations.invites');

    Route::post('/organizations/invites/accept', [OrganizationController::class, 'acceptInvite'])
         ->name('api.v1.organizations.accept-invite');

    Route::delete('/organizations/invites/{inviteId}', [OrganizationController::class, 'cancelInvite'])
         ->name('api.v1.organizations.cancel-invite');

    Route::post('/organizations/invites/{inviteId}/resend', [OrganizationController::class, 'resendInvite'])
         ->name('api.v1.organizations.resend-invite');

    // FR-ORG-004: Permission catalog
    Route::get('/organizations/permissions/catalog', [OrganizationController::class, 'permissionCatalog'])
         ->name('api.v1.organizations.permission-catalog');

    // FR-ORG-005: Financial access
    Route::put('/organizations/members/{memberId}/financial-access', [OrganizationController::class, 'setFinancialAccess'])
         ->name('api.v1.organizations.financial-access');

    // FR-ORG-006: Permission check
    Route::post('/organizations/{orgId}/check-permission', [OrganizationController::class, 'checkPermission'])
         ->name('api.v1.organizations.check-permission');

    // FR-ORG-007: Members & ownership
    Route::get('/organizations/{orgId}/members', [OrganizationController::class, 'listMembers'])
         ->name('api.v1.organizations.members');

    Route::post('/organizations/{orgId}/transfer-ownership', [OrganizationController::class, 'transferOwnership'])
         ->name('api.v1.organizations.transfer-ownership');

    Route::post('/organizations/members/{memberId}/suspend', [OrganizationController::class, 'suspendMember'])
         ->name('api.v1.organizations.suspend-member');

    Route::delete('/organizations/members/{memberId}', [OrganizationController::class, 'removeMember'])
         ->name('api.v1.organizations.remove-member');

    Route::put('/organizations/members/{memberId}/role', [OrganizationController::class, 'updateMemberRole'])
         ->name('api.v1.organizations.update-member-role');

    // FR-ORG-008: Verification
    Route::post('/organizations/{orgId}/submit-verification', [OrganizationController::class, 'submitVerification'])
         ->name('api.v1.organizations.submit-verification');

    // FR-ORG-009/010: Wallet
    Route::get('/organizations/{orgId}/wallet', [OrganizationController::class, 'walletSummary'])
         ->name('api.v1.organizations.wallet');

    Route::post('/organizations/{orgId}/wallet/topup', [OrganizationController::class, 'topUpWallet'])
         ->name('api.v1.organizations.wallet-topup');

    Route::put('/organizations/{orgId}/wallet/settings', [OrganizationController::class, 'updateWalletSettings'])
         ->name('api.v1.organizations.wallet-settings');

    // ═══════════════════════════════════════════════════════════════
    // BRP Module — Business Rules: Pricing (FR-BRP-001→008)
    // ═══════════════════════════════════════════════════════════════

    // FR-BRP-001: Calculate price
    Route::post('/pricing/calculate', [PricingController::class, 'calculate'])
         ->name('api.v1.pricing.calculate');

    // FR-BRP-006: Breakdowns
    Route::get('/pricing/breakdowns', [PricingController::class, 'listBreakdowns'])
         ->name('api.v1.pricing.breakdowns');

    Route::get('/pricing/breakdowns/{entityType}/{entityId}', [PricingController::class, 'getBreakdown'])
         ->name('api.v1.pricing.breakdown');

    // FR-BRP-008: Rule sets
    Route::post('/pricing/rule-sets', [PricingController::class, 'createRuleSet'])
         ->name('api.v1.pricing.create-rule-set');

    Route::get('/pricing/rule-sets', [PricingController::class, 'listRuleSets'])
         ->name('api.v1.pricing.rule-sets');

    Route::get('/pricing/rule-sets/{ruleSetId}', [PricingController::class, 'getRuleSet'])
         ->name('api.v1.pricing.rule-set');

    Route::post('/pricing/rule-sets/{ruleSetId}/activate', [PricingController::class, 'activateRuleSet'])
         ->name('api.v1.pricing.activate-rule-set');

    // FR-BRP-005: Rounding
    Route::post('/pricing/rounding', [PricingController::class, 'setRounding'])
         ->name('api.v1.pricing.set-rounding');

    // FR-BRP-007: Expired plan policy
    Route::post('/pricing/expired-policy', [PricingController::class, 'setExpiredPolicy'])
         ->name('api.v1.pricing.set-expired-policy');

    // ═══════════════════════════════════════════════════════════════
    // KYC Module — Compliance & Verification (FR-KYC-001→008)
    // ═══════════════════════════════════════════════════════════════

    // FR-KYC-001: Cases
    Route::post('/kyc/cases', [KycComplianceController::class, 'createCase'])
         ->name('api.v1.kyc.create-case');

    Route::get('/kyc/cases/{caseId}', [KycComplianceController::class, 'getCase'])
         ->name('api.v1.kyc.get-case');

    Route::get('/kyc/status', [KycComplianceController::class, 'getStatus'])
         ->name('api.v1.kyc.status');

    // FR-KYC-002: Documents
    Route::post('/kyc/cases/{caseId}/documents', [KycComplianceController::class, 'uploadDocument'])
         ->name('api.v1.kyc.upload-document');

    // FR-KYC-003: Submit
    Route::post('/kyc/cases/{caseId}/submit', [KycComplianceController::class, 'submit'])
         ->name('api.v1.kyc.submit');

    // FR-KYC-004: Restrictions
    Route::post('/kyc/restrictions/check', [KycComplianceController::class, 'checkRestriction'])
         ->name('api.v1.kyc.check-restriction');

    Route::get('/kyc/restrictions', [KycComplianceController::class, 'listRestrictions'])
         ->name('api.v1.kyc.restrictions');

    Route::post('/kyc/restrictions', [KycComplianceController::class, 'createRestriction'])
         ->name('api.v1.kyc.create-restriction');

    // FR-KYC-005: Admin review
    Route::get('/kyc/pending', [KycComplianceController::class, 'listPending'])
         ->name('api.v1.kyc.pending');

    Route::post('/kyc/cases/{caseId}/review', [KycComplianceController::class, 'review'])
         ->name('api.v1.kyc.review');

    // FR-KYC-006: Display
    Route::get('/kyc/display', [KycComplianceController::class, 'statusDisplay'])
         ->name('api.v1.kyc.display');

    // FR-KYC-007: Secure download
    Route::get('/kyc/documents/{documentId}/download', [KycComplianceController::class, 'downloadDocument'])
         ->name('api.v1.kyc.download-document');

    // FR-KYC-008: Audit
    Route::get('/kyc/cases/{caseId}/audit-log', [KycComplianceController::class, 'auditLog'])
         ->name('api.v1.kyc.audit-log');

    Route::get('/kyc/audit-log/export', [KycComplianceController::class, 'exportAuditLog'])
         ->name('api.v1.kyc.export-audit-log');

    // ═══════════════════════════════════════════════════════════════
    // BW Module — Billing & Wallet (FR-BW-001→010)
    // ═══════════════════════════════════════════════════════════════

    // FR-BW-001: Wallet CRUD
    Route::post('/billing/wallets', [BillingWalletController::class, 'create'])
         ->name('api.v1.billing.create-wallet');

    Route::get('/billing/wallets/{walletId}', [BillingWalletController::class, 'show'])
         ->name('api.v1.billing.get-wallet');

    Route::get('/billing/my-wallet', [BillingWalletController::class, 'myWallet'])
         ->name('api.v1.billing.my-wallet');

    Route::get('/billing/wallets/{walletId}/balance', [BillingWalletController::class, 'balance'])
         ->name('api.v1.billing.balance');

    Route::get('/billing/wallets/{walletId}/summary', [BillingWalletController::class, 'summary'])
         ->name('api.v1.billing.summary');

    // FR-BW-002/003: Top-up lifecycle
    Route::post('/billing/wallets/{walletId}/topup', [BillingWalletController::class, 'initiateTopup'])
         ->name('api.v1.billing.initiate-topup');

    Route::post('/billing/topups/{topupId}/confirm', [BillingWalletController::class, 'confirmTopup'])
         ->name('api.v1.billing.confirm-topup');

    Route::post('/billing/topups/{topupId}/fail', [BillingWalletController::class, 'failTopup'])
         ->name('api.v1.billing.fail-topup');

    // FR-BW-004/005: Ledger & Statement
    Route::get('/billing/wallets/{walletId}/statement', [BillingWalletController::class, 'statement'])
         ->name('api.v1.billing.statement');

    // FR-BW-006: Refunds
    Route::post('/billing/wallets/{walletId}/refund', [BillingWalletController::class, 'refund'])
         ->name('api.v1.billing.refund');

    // FR-BW-007: Holds
    Route::post('/billing/wallets/{walletId}/hold', [BillingWalletController::class, 'createHold'])
         ->name('api.v1.billing.create-hold');

    Route::post('/billing/holds/{holdId}/capture', [BillingWalletController::class, 'captureHold'])
         ->name('api.v1.billing.capture-hold');

    Route::post('/billing/holds/{holdId}/release', [BillingWalletController::class, 'releaseHold'])
         ->name('api.v1.billing.release-hold');

    // FR-BW-003: Direct charge
    Route::post('/billing/wallets/{walletId}/charge', [BillingWalletController::class, 'charge'])
         ->name('api.v1.billing.charge');

    // FR-BW-004: Reversal
    Route::post('/billing/wallets/{walletId}/reversal', [BillingWalletController::class, 'reversal'])
         ->name('api.v1.billing.reversal');

    // FR-BW-008: Threshold & Auto-topup
    Route::put('/billing/wallets/{walletId}/threshold', [BillingWalletController::class, 'setThreshold'])
         ->name('api.v1.billing.set-threshold');

    Route::put('/billing/wallets/{walletId}/auto-topup', [BillingWalletController::class, 'configureAutoTopup'])
         ->name('api.v1.billing.configure-auto-topup');

    // FR-BW-010: Reconciliation
    Route::post('/billing/reconciliation', [BillingWalletController::class, 'reconcile'])
         ->name('api.v1.billing.reconcile');

    Route::get('/billing/reconciliation', [BillingWalletController::class, 'reconciliationReports'])
         ->name('api.v1.billing.reconciliation-reports');

    // ═══════════════════════════════════════════════════════════════
    // DG Module — Dangerous Goods Compliance (FR-DG-001→009)
    // ═══════════════════════════════════════════════════════════════

    // FR-DG-001: Create Declaration
    Route::post('/dg/declarations', [DgComplianceController::class, 'create'])
         ->name('api.v1.dg.create-declaration');

    Route::get('/dg/declarations', [DgComplianceController::class, 'list'])
         ->name('api.v1.dg.list-declarations');

    Route::get('/dg/declarations/{declarationId}', [DgComplianceController::class, 'show'])
         ->name('api.v1.dg.get-declaration');

    Route::get('/dg/shipments/{shipmentId}/declaration', [DgComplianceController::class, 'forShipment'])
         ->name('api.v1.dg.shipment-declaration');

    // FR-DG-002: Set DG Flag
    Route::post('/dg/declarations/{declarationId}/dg-flag', [DgComplianceController::class, 'setDgFlag'])
         ->name('api.v1.dg.set-dg-flag');

    // FR-DG-003: Hold Info
    Route::get('/dg/declarations/{declarationId}/hold-info', [DgComplianceController::class, 'holdInfo'])
         ->name('api.v1.dg.hold-info');

    Route::get('/dg/blocked', [DgComplianceController::class, 'listBlocked'])
         ->name('api.v1.dg.list-blocked');

    // FR-DG-004: Accept Waiver
    Route::post('/dg/declarations/{declarationId}/accept-waiver', [DgComplianceController::class, 'acceptWaiver'])
         ->name('api.v1.dg.accept-waiver');

    // FR-DG-007: Validate for Issuance
    Route::post('/dg/validate-issuance', [DgComplianceController::class, 'validateForIssuance'])
         ->name('api.v1.dg.validate-issuance');

    // FR-DG-009: DG Metadata
    Route::post('/dg/declarations/{declarationId}/metadata', [DgComplianceController::class, 'saveDgMetadata'])
         ->name('api.v1.dg.save-metadata');

    // FR-DG-006: Waiver Version Management
    Route::post('/dg/waivers', [DgComplianceController::class, 'publishWaiver'])
         ->name('api.v1.dg.publish-waiver');

    Route::get('/dg/waivers/active', [DgComplianceController::class, 'activeWaiver'])
         ->name('api.v1.dg.active-waiver');

    Route::get('/dg/waivers', [DgComplianceController::class, 'listWaiverVersions'])
         ->name('api.v1.dg.list-waivers');

    // FR-DG-005: Audit Log
    Route::get('/dg/declarations/{declarationId}/audit-log', [DgComplianceController::class, 'auditLog'])
         ->name('api.v1.dg.audit-log');

    Route::get('/dg/shipments/{shipmentId}/audit-log', [DgComplianceController::class, 'shipmentAuditLog'])
         ->name('api.v1.dg.shipment-audit-log');

    Route::get('/dg/audit-log/export', [DgComplianceController::class, 'exportAuditLog'])
         ->name('api.v1.dg.export-audit-log');

    // ═══════════════════════════════════════════════════════════════
    // Phase 2 Expansion — 11 New Modules
    // ═══════════════════════════════════════════════════════════════

    // ── Companies ────────────────────────────────────────────────
    Route::apiResource('companies', \App\Http\Controllers\Api\V1\CompanyController::class)->names('api.v1.companies');
    Route::get('/companies/stats', [\App\Http\Controllers\Api\V1\CompanyController::class, 'stats'])->name('api.v1.companies.stats');

    // ── Branches ─────────────────────────────────────────────────
    Route::get('/branches/stats', [BranchController::class, 'stats'])->name('api.v1.branches.stats');
    Route::apiResource('branches', BranchController::class)->names('api.v1.branches');
    Route::get('/branches/{id}/staff', [BranchController::class, 'staff'])->name('api.v1.branches.staff');
    Route::post('/branches/{id}/staff', [BranchController::class, 'assignStaff'])->name('api.v1.branches.assign-staff');

    // ── Customs & Clearance ──────────────────────────────────────
    Route::get('/customs/stats', [CustomsController::class, 'stats'])->name('api.v1.customs.stats');
    Route::get('/customs/declarations', [CustomsController::class, 'declarations'])->name('api.v1.customs.declarations');
    Route::get('/customs/declarations/{id}', [CustomsController::class, 'showDeclaration'])->name('api.v1.customs.show-declaration');
    Route::post('/customs/declarations', [CustomsController::class, 'createDeclaration'])->name('api.v1.customs.create-declaration');
    Route::put('/customs/declarations/{id}', [CustomsController::class, 'updateDeclaration'])->name('api.v1.customs.update-declaration');
    Route::post('/customs/declarations/{id}/inspect', [CustomsController::class, 'inspect'])->name('api.v1.customs.inspect');
    Route::post('/customs/declarations/{id}/clearance', [CustomsController::class, 'issueClearance'])->name('api.v1.customs.clearance');
    Route::get('/customs/brokers', [CustomsController::class, 'brokers'])->name('api.v1.customs.brokers');
    Route::post('/customs/brokers', [CustomsController::class, 'createBroker'])->name('api.v1.customs.create-broker');
    Route::put('/customs/brokers/{id}', [CustomsController::class, 'updateBroker'])->name('api.v1.customs.update-broker');
    Route::get('/customs/shipments/{shipmentId}/documents', [CustomsController::class, 'documents'])->name('api.v1.customs.documents');
    Route::post('/customs/shipments/{shipmentId}/documents', [CustomsController::class, 'uploadDocument'])->name('api.v1.customs.upload-document');
    Route::patch('/customs/documents/{id}/verify', [CustomsController::class, 'verifyDocument'])->name('api.v1.customs.verify-document');
    Route::get('/customs/shipments/{shipmentId}/duties', [CustomsController::class, 'duties'])->name('api.v1.customs.duties');

    // ── Containers ───────────────────────────────────────────────
    Route::get('/containers/stats', [ContainerController::class, 'stats'])->name('api.v1.containers.stats');
    Route::apiResource('containers', ContainerController::class)->names('api.v1.containers');
    Route::get('/containers/{id}/shipments', [ContainerController::class, 'shipments'])->name('api.v1.containers.shipments');
    Route::post('/containers/{id}/shipments', [ContainerController::class, 'assignShipment'])->name('api.v1.containers.assign-shipment');

    // ── Vessels & Schedules ──────────────────────────────────────
    Route::apiResource('vessels', \App\Http\Controllers\Api\V1\VesselController::class)->names('api.v1.vessels');
    Route::apiResource('vessel-schedules', \App\Http\Controllers\Api\V1\VesselScheduleController::class)->names('api.v1.vessel-schedules');

    // ── Claims & Risk ────────────────────────────────────────────
    Route::get('/claims/stats', [ClaimController::class, 'stats'])->name('api.v1.claims.stats');
    Route::apiResource('claims', ClaimController::class)->names('api.v1.claims');
    Route::post('/claims/{id}/resolve', [ClaimController::class, 'resolve'])->name('api.v1.claims.resolve');
    Route::get('/claims/{id}/history', [ClaimController::class, 'history'])->name('api.v1.claims.history');
    Route::get('/claims/{id}/documents', [ClaimController::class, 'documents'])->name('api.v1.claims.documents');
    Route::post('/claims/{id}/documents', [ClaimController::class, 'uploadDocument'])->name('api.v1.claims.upload-document');
    Route::get('/risk/shipments/{shipmentId}', [RiskController::class, 'shipmentRisk'])->name('api.v1.risk.shipment');
    Route::get('/risk/stats', [RiskController::class, 'stats'])->name('api.v1.risk.stats');

    // ── Support Tickets ──────────────────────────────────────────
    Route::get('/support-tickets/stats', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'stats'])->name('api.v1.tickets.stats');
    Route::apiResource('support-tickets', \App\Http\Controllers\Api\V1\SupportTicketController::class);
    Route::post('/support-tickets/{id}/replies', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'reply'])->name('api.v1.tickets.reply');
    Route::patch('/support-tickets/{id}/close', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'close'])->name('api.v1.tickets.close');
    Route::patch('/support-tickets/{id}/assign', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'assign'])->name('api.v1.tickets.assign');

    // ── Drivers & Last Mile ──────────────────────────────────────
    Route::get('/drivers/stats', [DriverController::class, 'stats'])->name('api.v1.drivers.stats');
    Route::apiResource('drivers', DriverController::class)->names('api.v1.drivers');
    Route::patch('/drivers/{id}/toggle', [DriverController::class, 'toggle'])->name('api.v1.drivers.toggle');
    Route::get('/delivery-assignments', [DriverController::class, 'assignments'])->name('api.v1.delivery-assignments.index');
    Route::post('/delivery-assignments', [DriverController::class, 'assign'])->name('api.v1.delivery-assignments.store');
    Route::post('/delivery-assignments/{id}/complete', [DriverController::class, 'completeDelivery'])->name('api.v1.delivery-assignments.complete');
    Route::get('/proof-of-deliveries', [DriverController::class, 'pods'])->name('api.v1.pods.index');
    Route::get('/proof-of-deliveries/{id}', [DriverController::class, 'getPod'])->name('api.v1.pods.show');

    // ── Incoterms ────────────────────────────────────────────────
    Route::apiResource('incoterms', IncotermController::class);

    // ── HS Codes ─────────────────────────────────────────────────
    Route::get('/hs-codes/search', [HsCodeController::class, 'search'])->name('api.v1.hs-codes.search');
    Route::apiResource('hs-codes', HsCodeController::class)->names('api.v1.hs-codes');

    // ── Tariff Engine ────────────────────────────────────────────
    Route::post('/tariffs/calculate', [TariffController::class, 'calculate'])->name('api.v1.tariffs.calculate');
    Route::apiResource('tariffs', TariffController::class);
    Route::get('/tax-rules', [TariffController::class, 'taxRules'])->name('api.v1.tax-rules.index');
    Route::post('/tax-rules', [TariffController::class, 'createTaxRule'])->name('api.v1.tax-rules.store');

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: COMPANIES & BRANCHES
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('api.v1.companies.index');
        Route::post('/', [CompanyController::class, 'store'])->name('api.v1.companies.store');
        Route::get('/{id}', [CompanyController::class, 'show'])->name('api.v1.companies.show');
        Route::put('/{id}', [CompanyController::class, 'update'])->name('api.v1.companies.update');
        Route::delete('/{id}', [CompanyController::class, 'destroy'])->name('api.v1.companies.destroy');
        Route::get('/{id}/stats', [CompanyController::class, 'stats'])->name('api.v1.companies.stats');
        Route::get('/{id}/branches', [CompanyController::class, 'branches'])->name('api.v1.companies.branches');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: VESSELS & SCHEDULES
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('vessels')->group(function () {
        Route::get('/', [VesselScheduleController::class, 'listVessels'])->name('api.v1.vessels.index');
        Route::post('/', [VesselScheduleController::class, 'createVessel'])->name('api.v1.vessels.store');
        Route::get('/{id}', [VesselScheduleController::class, 'showVessel'])->name('api.v1.vessels.show');
        Route::put('/{id}', [VesselScheduleController::class, 'updateVessel'])->name('api.v1.vessels.update');
        Route::delete('/{id}', [VesselScheduleController::class, 'deleteVessel'])->name('api.v1.vessels.destroy');
    });

    Route::prefix('vessel-schedules')->group(function () {
        Route::get('/', [VesselScheduleController::class, 'listSchedules'])->name('api.v1.schedules.index');
        Route::post('/', [VesselScheduleController::class, 'createSchedule'])->name('api.v1.schedules.store');
        Route::get('/search', [VesselScheduleController::class, 'search'])->name('api.v1.schedules.search');
        Route::get('/stats', [VesselScheduleController::class, 'scheduleStats'])->name('api.v1.schedules.stats');
        Route::get('/{id}', [VesselScheduleController::class, 'showSchedule'])->name('api.v1.schedules.show');
        Route::put('/{id}', [VesselScheduleController::class, 'updateSchedule'])->name('api.v1.schedules.update');
        Route::delete('/{id}', [VesselScheduleController::class, 'deleteSchedule'])->name('api.v1.schedules.destroy');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: BOOKING WORKFLOW
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('booking')->group(function () {
        Route::post('/quotes', [BookingController::class, 'getQuotes'])->name('api.v1.booking.quotes');
        Route::post('/create', [BookingController::class, 'createBooking'])->name('api.v1.booking.create');
        Route::post('/{id}/confirm', [BookingController::class, 'confirmBooking'])->name('api.v1.booking.confirm');
        Route::post('/{id}/cancel', [BookingController::class, 'cancelBooking'])->name('api.v1.booking.cancel');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: SHIPMENT WORKFLOW (Status Transitions)
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('shipment-workflow')->group(function () {
        Route::get('/statuses', [ShipmentWorkflowController::class, 'statuses'])->name('api.v1.workflow.statuses');
        Route::get('/{id}/next-statuses', [ShipmentWorkflowController::class, 'nextStatuses'])->name('api.v1.workflow.next');
        Route::post('/{id}/transition', [ShipmentWorkflowController::class, 'transition'])->name('api.v1.workflow.transition');
        Route::post('/{id}/receive-origin', [ShipmentWorkflowController::class, 'receiveAtOrigin'])->name('api.v1.workflow.receive');
        Route::post('/{id}/export-clearance', [ShipmentWorkflowController::class, 'exportClearance'])->name('api.v1.workflow.export');
        Route::post('/{id}/load-transit', [ShipmentWorkflowController::class, 'loadToTransit'])->name('api.v1.workflow.transit');
        Route::post('/{id}/import-clearance', [ShipmentWorkflowController::class, 'importClearance'])->name('api.v1.workflow.import');
        Route::get('/{id}/sla', [ShipmentWorkflowController::class, 'checkSLA'])->name('api.v1.workflow.sla');
        Route::get('/{id}/predict-delay', [ShipmentWorkflowController::class, 'predictDelay'])->name('api.v1.workflow.delay');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: LAST MILE DELIVERY
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('delivery')->group(function () {
        Route::get('/dashboard', [LastMileDeliveryController::class, 'dashboard'])->name('api.v1.delivery.dashboard');
        Route::get('/pending', [LastMileDeliveryController::class, 'pendingDeliveries'])->name('api.v1.delivery.pending');
        Route::post('/{shipmentId}/assign', [LastMileDeliveryController::class, 'assignDriver'])->name('api.v1.delivery.assign');
        Route::post('/{shipmentId}/pod', [LastMileDeliveryController::class, 'recordPOD'])->name('api.v1.delivery.pod');
        Route::post('/{shipmentId}/failed', [LastMileDeliveryController::class, 'failedDelivery'])->name('api.v1.delivery.failed');
        Route::get('/driver/{driverId}/assignments', [LastMileDeliveryController::class, 'driverAssignments'])->name('api.v1.delivery.driver-assignments');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: INSURANCE
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('insurance')->group(function () {
        Route::post('/quote', [InsuranceController::class, 'quote'])->name('api.v1.insurance.quote');
        Route::post('/{shipmentId}/purchase', [InsuranceController::class, 'purchase'])->name('api.v1.insurance.purchase');
        Route::post('/{shipmentId}/claim', [InsuranceController::class, 'fileClaim'])->name('api.v1.insurance.claim');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: SLA MONITORING
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('sla')->group(function () {
        Route::get('/dashboard', [SLAController::class, 'dashboard'])->name('api.v1.sla.dashboard');
        Route::get('/check/{id}', [SLAController::class, 'check'])->name('api.v1.sla.check');
        Route::get('/scan-breaches', [SLAController::class, 'scanBreaches'])->name('api.v1.sla.scan');
        Route::get('/at-risk', [SLAController::class, 'atRisk'])->name('api.v1.sla.at-risk');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: SUPPORT TICKETS
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('tickets')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('api.v1.tickets.index');
        Route::post('/', [SupportTicketController::class, 'store'])->name('api.v1.tickets.store');
        Route::get('/stats', [SupportTicketController::class, 'stats'])->name('api.v1.tickets.stats');
        Route::get('/{id}', [SupportTicketController::class, 'show'])->name('api.v1.tickets.show');
        Route::put('/{id}', [SupportTicketController::class, 'update'])->name('api.v1.tickets.update');
        Route::post('/{id}/reply', [SupportTicketController::class, 'reply'])->name('api.v1.tickets.reply');
        Route::post('/{id}/assign', [SupportTicketController::class, 'assign'])->name('api.v1.tickets.assign');
        Route::post('/{id}/escalate', [SupportTicketController::class, 'escalate'])->name('api.v1.tickets.escalate');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: GLOBAL ANALYTICS
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('analytics')->group(function () {
        Route::get('/overview', [AnalyticsController::class, 'overview'])->name('api.v1.analytics.overview');
        Route::get('/shipment-trends', [AnalyticsController::class, 'shipmentTrends'])->name('api.v1.analytics.trends');
        Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('api.v1.analytics.revenue');
        Route::get('/carrier-performance', [AnalyticsController::class, 'carrierPerformance'])->name('api.v1.analytics.carriers');
        Route::get('/branch-performance', [AnalyticsController::class, 'branchPerformance'])->name('api.v1.analytics.branches');
        Route::get('/geo-distribution', [AnalyticsController::class, 'geoDistribution'])->name('api.v1.analytics.geo');
        Route::get('/commissions', [AnalyticsController::class, 'commissions'])->name('api.v1.analytics.commissions');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: INTEGRATIONS
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('integrations')->group(function () {
        Route::get('/', [IntegrationController::class, 'index'])->name('api.v1.integrations.index');
        Route::get('/health', [IntegrationController::class, 'health'])->name('api.v1.integrations.health');
        Route::post('/{id}/test', [IntegrationController::class, 'test'])->name('api.v1.integrations.test');
        Route::get('/{id}/logs', [IntegrationController::class, 'logs'])->name('api.v1.integrations.logs');
        Route::get('/webhook-config', [IntegrationController::class, 'webhookConfig'])->name('api.v1.integrations.webhooks');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2: CONTENT DECLARATIONS
    // ═══════════════════════════════════════════════════════════════
    Route::prefix('content-declarations')->group(function () {
        Route::get('/', [ContentDeclarationController::class, 'index'])->name('api.v1.declarations.index');
        Route::post('/', [ContentDeclarationController::class, 'store'])->name('api.v1.declarations.store');
        Route::get('/{id}', [ContentDeclarationController::class, 'show'])->name('api.v1.declarations.show');
        Route::put('/{id}', [ContentDeclarationController::class, 'update'])->name('api.v1.declarations.update');
        Route::post('/{id}/submit', [ContentDeclarationController::class, 'submit'])->name('api.v1.declarations.submit');
        Route::post('/{id}/review', [ContentDeclarationController::class, 'review'])->name('api.v1.declarations.review');
        Route::delete('/{id}', [ContentDeclarationController::class, 'destroy'])->name('api.v1.declarations.destroy');
    });

    // ═══════════════════════════════════════════════════════════════
    // PHASE 3: ENTERPRISE MODULES — 8 Domain Areas
    // ═══════════════════════════════════════════════════════════════

    // ── 1. Route Optimization Engine ─────────────────────────────
    Route::prefix('route-optimization')->group(function () {
        Route::get('/plans', [\App\Http\Controllers\Api\V1\RouteOptimizationController::class, 'plans']);
        Route::get('/plans/{id}', [\App\Http\Controllers\Api\V1\RouteOptimizationController::class, 'showPlan']);
        Route::post('/optimize', [\App\Http\Controllers\Api\V1\RouteOptimizationController::class, 'optimize']);
        Route::patch('/plans/{id}/select', [\App\Http\Controllers\Api\V1\RouteOptimizationController::class, 'selectPlan']);
        Route::get('/cost-factors', [\App\Http\Controllers\Api\V1\RouteOptimizationController::class, 'costFactors']);
        Route::post('/cost-factors', [\App\Http\Controllers\Api\V1\RouteOptimizationController::class, 'createCostFactor']);
        Route::get('/stats', [\App\Http\Controllers\Api\V1\RouteOptimizationController::class, 'stats']);
    });

    // ── 2. Capacity & Load Management ────────────────────────────
    Route::prefix('capacity')->group(function () {
        Route::get('/pools', [\App\Http\Controllers\Api\V1\CapacityController::class, 'pools']);
        Route::get('/pools/{id}', [\App\Http\Controllers\Api\V1\CapacityController::class, 'showPool']);
        Route::post('/pools', [\App\Http\Controllers\Api\V1\CapacityController::class, 'createPool']);
        Route::post('/pools/{id}/book', [\App\Http\Controllers\Api\V1\CapacityController::class, 'book']);
        Route::get('/stats', [\App\Http\Controllers\Api\V1\CapacityController::class, 'stats']);
    });

    // ── 3. Profitability Engine ──────────────────────────────────
    Route::prefix('profitability')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\V1\ProfitabilityController::class, 'dashboard']);
        Route::get('/shipment-costs', [\App\Http\Controllers\Api\V1\ProfitabilityController::class, 'shipmentCosts']);
        Route::get('/shipment-costs/{shipmentId}', [\App\Http\Controllers\Api\V1\ProfitabilityController::class, 'shipmentCost']);
        Route::post('/shipment-costs', [\App\Http\Controllers\Api\V1\ProfitabilityController::class, 'recordCost']);
        Route::get('/branch-pnl', [\App\Http\Controllers\Api\V1\ProfitabilityController::class, 'branchPnl']);
    });

    // ── 4. Multi-Currency Ledger ─────────────────────────────────
    Route::prefix('currency')->group(function () {
        Route::get('/rates', [\App\Http\Controllers\Api\V1\CurrencyController::class, 'rates']);
        Route::post('/rates', [\App\Http\Controllers\Api\V1\CurrencyController::class, 'setRate']);
        Route::post('/convert', [\App\Http\Controllers\Api\V1\CurrencyController::class, 'convert']);
        Route::get('/transactions', [\App\Http\Controllers\Api\V1\CurrencyController::class, 'transactions']);
        Route::get('/fx-report', [\App\Http\Controllers\Api\V1\CurrencyController::class, 'fxReport']);
    });

    // ── 5. IATA/FIATA Compliance Layer ───────────────────────────
    Route::prefix('compliance')->group(function () {
        Route::get('/documents', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'documents']);
        Route::post('/documents', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'createDocument']);
        Route::post('/documents/{id}/validate', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'validateDocument']);
        Route::get('/manifests', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'manifests']);
        Route::post('/manifests', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'createManifest']);
        Route::get('/retention-policies', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'retentionPolicies']);
        Route::post('/retention-policies', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'setRetentionPolicy']);
        Route::get('/audit-log', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'auditLog']);
        Route::get('/audit-log/export', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'exportAudit']);
        Route::get('/stats', [\App\Http\Controllers\Api\V1\ComplianceController::class, 'complianceStats']);
    });

    // ── 6. Data Intelligence Layer ───────────────────────────────
    Route::prefix('intelligence')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'dashboard']);
        Route::get('/snapshots', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'snapshots']);
        Route::get('/route-profitability', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'routeProfitability']);
        Route::get('/sla-metrics', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'slaMetrics']);
        Route::get('/sla-dashboard', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'slaDashboard']);
        Route::get('/clv', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'clv']);
        Route::get('/clv-summary', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'clvSummary']);
        Route::get('/delay-predictions', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'delayPredictions']);
        Route::post('/delay-predictions', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'predictDelay']);
        Route::get('/fraud-signals', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'fraudSignals']);
        Route::patch('/fraud-signals/{id}', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'reviewFraud']);
        Route::get('/fraud-dashboard', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'fraudDashboard']);
        Route::get('/branch-comparison', [\App\Http\Controllers\Api\V1\IntelligenceController::class, 'branchComparison']);
    });

    // ── 7. Customer Self-Service Portal ──────────────────────────
    Route::prefix('portal')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\V1\CustomerPortalController::class, 'portalDashboard']);
        Route::post('/quotes', [\App\Http\Controllers\Api\V1\CustomerPortalController::class, 'getQuote']);
        Route::get('/quotes', [\App\Http\Controllers\Api\V1\CustomerPortalController::class, 'savedQuotes']);
        Route::post('/quotes/{id}/convert', [\App\Http\Controllers\Api\V1\CustomerPortalController::class, 'convertQuote']);
        Route::get('/shipment-analytics', [\App\Http\Controllers\Api\V1\CustomerPortalController::class, 'shipmentAnalytics']);
        Route::get('/api-keys', [\App\Http\Controllers\Api\V1\CustomerPortalController::class, 'apiKeys']);
        Route::post('/api-keys', [\App\Http\Controllers\Api\V1\CustomerPortalController::class, 'createApiKey']);
        Route::delete('/api-keys/{id}', [\App\Http\Controllers\Api\V1\CustomerPortalController::class, 'revokeApiKey']);
    });

});

// ═══════════════════════════════════════════════════════════════════
// Public Routes (No Auth) — Webhooks
// ═══════════════════════════════════════════════════════════════════
Route::prefix('v1/webhooks')->group(function () {
    Route::post('/{platform}/{storeId}', [WebhookController::class, 'handle'])
         ->name('api.v1.webhooks.handle');

    // FR-TR-001/002: DHL Tracking Webhooks (public, no auth — signature verified in service)
    Route::post('/dhl/tracking', [TrackingController::class, 'handleDhlWebhook'])
         ->name('api.v1.webhooks.dhl-tracking');

    // FR-TR-007: External tracking API (API key auth)
    Route::get('/track/{trackingNumber}', [TrackingController::class, 'apiTrack'])
         ->name('api.v1.tracking.public-track');
});
