I now have sufficient evidence from reading the actual code to compile the full audit. Let me produce the comprehensive report.

---

# CEBX SHIPPING GATEWAY — COMPREHENSIVE CODEBASE AUDIT REPORT

**Audit Date:** 2026-03-18
**Auditor:** Senior Software Architect / Code Auditor (Claude Sonnet 4.6)
**Project Root:** `C:\Users\legion\Documents\GitHub\cebx-code`
**Git Branch:** `main`

---

## SECTION 1: EXECUTIVE SUMMARY

### Overall Project Health: 6.5 / 10

**Justification:** This is an ambitious, well-architected enterprise logistics platform with serious engineering effort behind it. The schema design, permission system, multi-tenancy model, and service layering show real domain competence. However, a significant fraction of the carrier integration layer is still simulated (DHL/Aramex rates are hardcoded mock numbers), FedEx tracking is explicitly unimplemented, invitation emails are commented out (TODO), no background job queue exists (no `app/Jobs/` directory), and the UUID migration chain (~14 migration files from 2026-03-05) creates a high-risk schema evolution path that is not yet validated in production. The system is solid for a pre-production beta but is NOT production-ready in its current state.

### Current Production Readiness: PRE-PRODUCTION BETA

Critical blockers that prevent live revenue operations are identified below.

### Critical Blockers Count: 8

### Architecture Verdict: Hybrid Modular Monolith — Well structured, partially executed

The architecture follows a clear separation: route files group by actor type (external/internal), controllers delegate to services, policies enforce authorization per tenant, and a `BelongsToAccount` trait + `AccountScope` global scope enforce tenant isolation. This is the correct pattern for a multi-tenant SaaS. The execution is approximately 70% complete.

### Top 5 Risks

1. **Carrier rate simulation in production path:** `CarrierRateAdapter::fetchDhlRates()` and `fetchAramexRates()` return hardcoded simulated prices. Any real customer transaction using DHL or Aramex rates would charge incorrect amounts (`app/Services/Carriers/CarrierRateAdapter.php` lines 89–150).

2. **FedEx tracking fully absent:** `FedexCarrierAdapter::track()` explicitly returns `'error' => 'FedEx tracking not yet implemented'` (`app/Services/Carriers/FedexCarrierAdapter.php` line 49). FedEx is the only carrier with a "real" rate adapter enabled — but cannot track.

3. **No background queue:** There is no `app/Jobs/` directory. `SendInvitationEmailListener` implements `ShouldQueue` but the email send is commented out (`// Mail::to($invitation->email)->queue(new InvitationMail($invitation));`). This means invitation emails are silently never delivered.

4. **UUID migration chain risk:** 14 migration files created 2026-03-05 through 2026-03-17 perform a multi-phase primary key conversion from bigint to UUID across all tables. This is extremely high risk on an existing populated database without a tested rollback plan.

5. **PaymentService does not call PaymentGatewayFactory:** `PaymentService::chargeShipping()` calls `PaymentTransaction::create()` directly and deducts from the wallet in the database but does NOT call any payment gateway adapter (`app/Services/PaymentService.php`). Real money collection from an external payment gateway is missing.

---

## SECTION 2: ARCHITECTURE AUDIT

### Architecture Type: Hybrid Modular Monolith

The codebase is a Laravel 11 monolith with modular organization: controllers are grouped by domain (`Api/V1/ShipmentController`, `Api/V1/TrackingController`, etc.), services are grouped by concern (`CarrierService`, `TrackingService`, `ShipmentService`, etc.), and the route layer is split into three files by actor type.

### Layer Separation Analysis

| Layer | Quality | Evidence |
|---|---|---|
| **UI/Route** | Good | `routes/api.php`, `routes/api_external.php`, `routes/api_internal.php`, `routes/web.php` cleanly separated |
| **Controller** | Good | Controllers are thin; they validate input, authorize, call a service, return JSON |
| **Service** | Good | Services contain business logic; `ShipmentService`, `BillingWalletService`, `ReportService` are non-trivial |
| **Model** | Good | Models define relationships and scopes; `Shipment` has 15 relationships and 21 status constants |
| **Domain Objects/DTOs** | Missing | No value objects, no DTOs — arrays are used everywhere for data transfer |
| **Infrastructure/Adapters** | Partial | `CarrierAdapterFactory`, `PaymentGatewayFactory` exist but only DHL and FedEx have real HTTP adapters |

### Directory Organization Quality: HIGH

The directory layout is professional and readable: `app/Services/Carriers/`, `app/Services/Payments/`, `app/Services/Platforms/`, `app/Policies/`, `app/Enums/` all follow clear conventions.

### Responsibility Overlaps Found

1. **Two "wallet" service classes exist:** `BillingWalletService` (`app/Services/BillingWalletService.php`) AND `WalletBillingService` (`app/Services/WalletBillingService.php`). Both handle wallet operations. This is a clear duplication / naming inconsistency.

2. **Two DHL service classes exist:** `app/Services/DhlApiService.php` (root level) AND `app/Services/Carriers/DhlApiService.php` (nested). `CarrierService` injects `App\Services\Carriers\DhlApiService`; `TrackingService` also injects `DhlApiService`. It is unclear which resolves by the container without reading the service provider.

3. **[BLOCKER-CARRIER-FACTORY] `CarrierAdapterFactory` is not used by `CarrierService`:** `CarrierService` directly injects `DhlApiService` and `FedexShipmentProvider` in its constructor (confirmed: lines 17-29 of `app/Services/CarrierService.php`). `CarrierAdapterFactory` is a parallel implementation not wired into the main carrier dispatch flow. Status: **Open Blocker** — refactor to route all carrier operations through `CarrierAdapterFactory` to enable runtime carrier switching and clean up the dual-injection pattern.

4. **Two "kyc" controllers:** `KycController` (`FR-IAM-014/016`) and `KycComplianceController` both exist. The split is undocumented in terms of which routes use which.

5. **`PageController`** (web layer) contained inline DB queries and business logic. [S5-2 FIXED] `reportsExport()` now delegates to `ReportService::generateWebExportRows()` — query logic extracted, controller is pure HTTP handler. Remaining: `roles()`, `invitations()`, `audit()` still do direct Eloquent queries — partial, not a blocker.

---

## SECTION 3: TECHNOLOGY STACK

### Framework: Laravel 11.x (`^11.0`)

### Key Packages

| Package | Version | Actual Usage Evidence |
|---|---|---|
| `laravel/sanctum` | `^4.0` | API token auth via `auth:sanctum` middleware in `api_external.php` line 53; `User::HasApiTokens` trait |
| `spatie/laravel-permission` | `^6.0` | Listed in `composer.json` but NOT actually used in code — the codebase has a CUSTOM RBAC (`PermissionResolver`, `user_role`, `role_permission` tables) that does NOT use Spatie's model/policy bridge |
| `spatie/laravel-query-builder` | `^5.0` | Listed but no `QueryBuilder::for()` calls found in controllers |
| `spatie/laravel-data` | `^4.0` | Listed but no `Data::from()` or `Data` subclasses found |
| `spatie/laravel-activitylog` | `^4.0` | Listed but custom `AuditLog` model/service used instead |
| `spatie/laravel-medialibrary` | `^11.0` | Listed but no `InteractsWithMedia` trait found in models |
| `maatwebsite/excel` | `^3.1` | Used in `ReportService`: `Excel::download()` / `Maatwebsite\Excel\Facades\Excel` |
| `barryvdh/laravel-dompdf` | `^2.0` | Used in `ReportService`: `Barryvdh\DomPDF\Facade\Pdf` |
| `league/csv` | `^9.0` | Used in `ReportService`: `League\Csv\Writer` |
| `guzzlehttp/guzzle` | `^7.8` | Listed; HTTP calls use Laravel's `Http` facade (which wraps Guzzle) in adapters |
| `twilio/sdk` | `^7.0` | Listed in `composer.json` and `config/services.php` has `twilio` config; `NotificationService` uses `Http::post()` for SMS (not the SDK directly) |
| `firebase/php-jwt` | `^6.0` | Listed; usage not confirmed in read files |
| `ramsey/uuid` | `^4.7` | Listed; Laravel's `HasUuids` trait used throughout, direct `Str::uuid()` used |
| `predis/predis` | `^2.0` | Listed; no explicit `Redis::` calls found in read files |
| `aws/aws-sdk-php` | `^3.0` | Listed; no direct `S3Client` calls found — `Storage::` facade used for file storage |

**NOTE:** Several packages (spatie/permission, spatie/data, spatie/medialibrary, spatie/activitylog) are declared as dependencies but appear to be unused. This is dead weight in `composer.json`.

### Auth System

- **Web:** Session-based auth via `auth:web` guard, Laravel's `Auth::attempt()`
- **API:** Sanctum token-based auth via `auth:sanctum` guard
- **User Types:** `internal` (platform staff) vs `external` (customers), enforced by `EnsureUserTypeMiddleware`
- **RBAC:** Completely custom — `PermissionResolver` service, `user_role` pivot, `role_permission` pivot, `internal_user_role`, `internal_role_permission` for internal staff
- **Policies:** 41 policy files covering every resource type

### Queue/Cache/Storage Configured

- **Queue:** No `app/Jobs/` directory exists. Laravel's queue system is referenced via `ShouldQueue` in listeners but no jobs are dispatched anywhere in the service layer (no `dispatch()` calls found).
- **Cache:** Redis via `predis/predis` (configured but not confirmed active)
- **Storage:** Laravel `Storage` facade used in `ReportService`; AWS SDK included but not proven wired

### External Integrations Found in Actual Code

| Integration | State | Evidence File |
|---|---|---|
| DHL Express API | Partial — real HTTP client, simulated rates | `app/Services/Carriers/DhlApiService.php` |
| FedEx API | Real rate provider, NO tracking/cancel | `app/Services/Carriers/FedexShipmentProvider.php`, `FedexRateProvider.php` |
| Aramex API | Real HTTP adapter exists (SOAP/REST), rates simulated | `app/Services/Carriers/AramexCarrierAdapter.php` |
| Moyasar Payment | Real HTTP adapter, sandbox by default | `app/Services/Payments/MoyasarPaymentAdapter.php` |
| Shopify | Real HTTP calls registered | `app/Services/Platforms/ShopifyAdapter.php` |
| WooCommerce | Real HTTP calls registered | `app/Services/Platforms/WooCommerceAdapter.php` |
| Salla | Real HTTP calls registered | `app/Services/Platforms/SallaAdapter.php` |
| Zid | Real HTTP calls registered | `app/Services/Platforms/ZidAdapter.php` |
| Twilio SMS | Config registered, HTTP-based dispatch | `app/Services/NotificationService.php` |

### Frontend Stack

- **Blade Templates** (`resources/views/`) with custom CSS
- **PWA** (service worker, manifest, icons found in `routes/public/`)
- B2B and B2C portals as separate web route groups
- No Vue/React/Livewire detected in read files; appears to be server-side rendered Blade

---

## SECTION 4: BUSINESS MODULES INVENTORY

| Module | Status | Key Files | Completeness % | Production Ready | Evidence |
|---|---|---|---|---|---|
| **Authentication/IAM** | COMPLETE | `AuthController.php`, `AuthWebController.php`, `TenantMiddleware.php`, `ResolveTenantContextMiddleware.php` | 95% | YES | Full login/logout, password reset, B2B/B2C/internal portals, session + token auth |
| **Multi-tenancy** | COMPLETE | `BelongsToAccount` trait, `AccountScope`, `TenantMiddleware`, `ResolveTenantContextMiddleware` | 90% | YES | Global scope auto-filters by `account_id`; internal admin can select tenant context via header |
| **Account Management** | COMPLETE | `AccountController`, `AccountSettingsController`, `AccountService`, `AccountSettingsService` | 90% | YES | Registration, settings, type management, KYC submission |
| **User Management** | COMPLETE | `UserController`, `UserService`, `UserWebController` | 90% | YES | CRUD, enable/disable, changelog |
| **RBAC/Permissions** | COMPLETE | `PermissionResolver`, `CheckPermission` middleware, `RoleController`, 41 policy files | 85% | YES | Custom dot-notation permissions, role templates, internal vs external audience separation |
| **KYC Compliance** | PARTIAL | `KycController`, `KycComplianceController`, `KycService`, `KycComplianceService` | 65% | NO | Status display and approval flow exist; document upload/download referenced but media library integration unclear |
| **Shipments** | PARTIAL | `ShipmentController`, `ShipmentService`, `ShipmentWorkflowController` | 75% | NO | Draft creation, validation, status machine, cancel, bulk exist. Carrier submission flow works for FedEx; DHL/Aramex submission uses simulated rates |
| **Tracking** | PARTIAL | `TrackingController`, `TrackingService` | 70% | NO | Webhook ingestion (DHL/FedEx/Aramex), timeline display, subscriptions work. FedEx tracking not implemented |
| **Carriers** | PARTIAL | `CarrierController`, `CarrierService`, `CarrierAdapterFactory`, `DhlApiService`, `FedexShipmentProvider`, `AramexCarrierAdapter` | 55% | NO | FedEx: real rates + shipment creation. DHL: real HTTP shipment creation, SIMULATED rates. Aramex: real adapter exists but not wired via factory into main flow. FedEx tracking absent |
| **Rates/Pricing** | PARTIAL | `RateController`, `RateService`, `PricingEngineService`, `DynamicPricingService`, `CarrierRateAdapter` | 65% | NO | FedEx real rates; DHL/Aramex simulated; Dynamic pricing engine with DB-driven multipliers |
| **Bookings** | PARTIAL | `BookingController` | 60% | NO | Quote generation calls PricingEngine and routing; booking confirmation creates Invoice but no async processing |
| **Orders** | COMPLETE | `OrderController`, `OrderService`, `OrderWebController` | 85% | YES | Order sync from stores, webhook deduplication, ship-from-order flow |
| **Payments/Billing** | PARTIAL | `PaymentController`, `PaymentService`, `PaymentGatewayFactory`, `MoyasarPaymentAdapter` | 50% | NO | Wallet top-up, charge, refund, subscriptions are modeled. BUT `PaymentService` does NOT call the gateway adapter — it only updates DB records. Moyasar adapter exists but is not called |
| **Wallet** | COMPLETE | `BillingWalletService`, `WalletBillingController`, `WalletLedgerEntry` | 85% | CONDITIONAL | Ledger, holds, topup, refund well implemented. Production readiness depends on payment gateway wiring |
| **Reports/Analytics** | PARTIAL | `ReportController`, `ReportService`, `AnalyticsController` | 70% | PARTIAL | Dashboard, CSV/Excel/PDF export, scheduled reports exist. Uses raw `DB::table()` queries which bypasses tenant scoping for some reports |
| **Notifications** | PARTIAL | `NotificationController`, `NotificationService` | 65% | NO | Multi-channel dispatch exists (email, SMS, in_app, webhook). Email delivery confirmed via `Mail::send()`; SMS uses `Http::post()`. Invitation email listener has TODO — actual send commented out |
| **Webhooks (outbound)** | PARTIAL | `WebhookController`, `WebhookEvent` model | 60% | NO | Inbound store webhooks received and deduplicated. Outbound carrier webhooks received. No outbound customer notification webhook dispatch confirmed |
| **Webhook Receivers** | PARTIAL | `TrackingController::handleDhlWebhook()`, `handleFedexWebhook()`, `handleAramexWebhook()` | 65% | NO | All three carrier webhooks are received and logged. Signature verification implemented in TrackingService |
| **Support Tickets/SLA** | PARTIAL | `SupportTicketController`, `SupportWebController`, `SLAController`, `SLAEngineService` | 65% | NO | Ticket CRUD and replies exist. SLA breach detection exists. No email on ticket reply confirmed (notification fan-out not proven) |
| **Documents/Content Declarations** | PARTIAL | `ContentDeclarationController` | 60% | NO | DG flag, declaration state on shipments, content items with HS codes exist. Document storage integration unconfirmed |
| **Customs/HS Codes** | PARTIAL | `CustomsController`, `HsCodeController` | 60% | NO | Customs declaration CRUD, broker management, HS code lookup exist. No external customs API integration |
| **Insurance** | PARTIAL | `InsuranceController` | 50% | NO | Quote calculation is hardcoded rate table (no external insurer API). Policy issuance creates a `Claim` stub but no actual insurance policy record type |
| **Container Management** | PARTIAL | `ContainerController` | 55% | NO | Container CRUD, vessel assignment, shipment-container linkage exist. Sea freight domain objects present |
| **Vessel Schedules** | PARTIAL | `VesselScheduleController` | 55% | NO | Vessel and schedule CRUD. No external shipping line API integration |
| **Last Mile Delivery** | PARTIAL | `LastMileDeliveryController`, `DriverController` | 60% | NO | Driver management, delivery assignment, proof-of-delivery exist. Status transition wired to `StatusTransitionService` |
| **Driver Management** | PARTIAL | `DriverController` | 60% | NO | Driver CRUD, availability filtering, assignment exists |
| **Risk Management** | PARTIAL | `RiskController` | 55% | NO | `RiskScore::calculateForShipment()` is a heuristic rule engine (no ML model). Fraud detection service exists but is rule-based |
| **Claims** | PARTIAL | `ClaimController` | 60% | NO | Claim CRUD, history, documents exist. No external insurer/carrier claim API integration |
| **Integrations (store connectors)** | PARTIAL | `ShopifyAdapter`, `WooCommerceAdapter`, `SallaAdapter`, `ZidAdapter`, `StoreController` | 70% | PARTIAL | All 4 platforms have testConnection + fetchOrders + registerWebhooks. OAuth flow for Salla/Zid exists but token refresh needs verification |
| **Customer Portal** | PARTIAL | `CustomerPortalController`, web routes `web_b2c.php`, `web_b2b.php` | 55% | NO | B2C and B2B portals exist with dashboards. Many pages route to `PageController` which has stub-level Blade rendering |
| **Admin Panel** | PARTIAL | `AdminController`, `InternalAdminWebController` | 60% | NO | System settings, user management, feature flags, tax rules, role templates exist. No analytics dashboard for internal ops team |
| **Organizations/Branches** | PARTIAL | `OrganizationController`, `BranchController`, `CompanyController` | 60% | NO | Org profile, branches, companies, branch staff all have tables and controllers. P&L reporting per branch referenced but not implemented |
| **DG Compliance** | PARTIAL | `DgComplianceController`, `DgComplianceService` | 55% | NO | DG classification, audit log, metadata exist. No IATA/ICAO external validation API |
| **Profitability/Analytics** | PARTIAL | `ProfitabilityController`, `AnalyticsController`, `IntelligenceController` | 50% | NO | Raw DB queries for cost/revenue analytics. Route profitability exists. AI delay prediction service is rule-based heuristic |

---

## SECTION 5: ROUTE AUDIT

### Total Route Count (estimated): ~300+ named routes

**Route files:**
- `routes/api.php` — public endpoints (login, register, invitations, webhooks)
- `routes/api_external.php` — authenticated external (customer) API (~180 routes, file is 26,176 tokens)
- `routes/api_internal.php` — authenticated internal (staff) API (~35 routes)
- `routes/web.php` — web UI routes (~80 routes)
- `routes/web_b2c.php` — B2C portal routes
- `routes/web_b2b.php` — B2B portal routes

### Route → Controller Chain Verification (Top 20)

| # | Route | Controller Method | Service Called | Status |
|---|---|---|---|---|
| 1 | `POST /api/v1/login` | `AuthController::login()` | Direct DB lookup | WORKING |
| 2 | `POST /api/v1/register` | `AccountController::register()` | `AccountService` | WORKING |
| 3 | `POST /api/v1/shipments` | `ShipmentController::store()` | `ShipmentService::createDirect()` | WORKING |
| 4 | `GET /api/v1/shipments/{id}/tracking/timeline` | `TrackingController::timeline()` | `TrackingService::getTimeline()` | WORKING |
| 5 | `POST /api/v1/webhooks/dhl/tracking` | `TrackingController::handleDhlWebhook()` | `TrackingService::processWebhook()` | WORKING |
| 6 | `POST /api/v1/payments/topup` | `PaymentController::topUp()` | `PaymentService::topUpWallet()` | PARTIAL — no gateway call |
| 7 | `GET /api/v1/reports/shipment-dashboard` | `ReportController::shipmentDashboard()` | `ReportService::shipmentDashboard()` | WORKING |
| 8 | `POST /api/v1/reports/export` | `ReportController::createExport()` | `ReportService::createExport()` | WORKING |
| 9 | `GET /api/v1/shipments/{id}/rates` | `RateController` (not fully read) | `RateService::fetchRates()` | PARTIAL — DHL/Aramex simulated |
| 10 | `POST /api/v1/shipments/{id}/carrier/create` | `CarrierController::createAtCarrier()` | `CarrierService::createAtCarrier()` | PARTIAL — FedEx only real |
| 11 | `POST /api/v1/invitations` | `InvitationController::store()` | `InvitationService` | BROKEN — email never sent (listener has commented-out send) |
| 12 | `GET /api/v1/kyc/status` | `KycController::status()` | `KycService::getKycStatus()` | WORKING |
| 13 | `POST /api/v1/kyc/approve` | `KycController::approve()` | `KycService` | WORKING |
| 14 | `GET /api/v1/claims` | `ClaimController::index()` | Direct Eloquent | WORKING |
| 15 | `POST /api/v1/bookings/quotes` | `BookingController::getQuotes()` | `PricingEngineService` | PARTIAL — pricing is simulated for most carriers |
| 16 | `GET /api/v1/analytics/overview` | `AnalyticsController::overview()` | Direct DB queries | WORKING |
| 17 | `GET /api/v1/sla/dashboard` | `SLAController::dashboard()` | `SLAEngineService::dashboard()` | WORKING |
| 18 | `POST /api/v1/webhooks/{platform}/{storeId}` | `WebhookController::handle()` | `OrderService::processWebhookEvent()` | WORKING |
| 19 | `GET /api/v1/shipments/{id}/risk` | `RiskController::shipmentRisk()` | `RiskScore::calculateForShipment()` | WORKING (heuristic) |
| 20 | `GET /api/v1/vessels` | `VesselScheduleController::listVessels()` | Direct Eloquent | WORKING |

### Routes with Middleware Inconsistencies

1. **`CarrierController`, `InsuranceController`, `LastMileDeliveryController`** routes are present in `api_external.php` but the actual routes file (26K tokens, only first 200 lines read) — need to confirm all carrier routes carry `permission:` middleware. ShipmentController routes have per-method permission guards, which is correct.

2. **`/api/v1/webhooks/{platform}/{storeId}`** is public (no auth). Signature verification is handled inline in `WebhookController`. This is acceptable design but the signature verification is optional (only runs if `PlatformAdapterFactory::supports($platform)` returns true — meaning unsupported platforms receive no verification).

3. **`/api/v1/tracking/public-track/{trackingNumber}`** has a comment saying "API key auth checked via middleware" but no middleware is visible on that route in the route file. This is potentially a public tracking endpoint with no auth enforced.

---

## SECTION 6: DATABASE AUDIT

### Migration Sequence Overview

The database has gone through multiple phases:
- **Phase 0** (`0001_01_01_*`): Core business tables (legacy bigint PKs) — shipments, stores, orders, audit logs, wallet/billing
- **Phase 1** (`2026_02_12_*`): Proper accounts, users, RBAC, KYC, invitations, wallet_billing, shipments, tracking, notifications, payments, reports, organizations, DG compliance
- **Phase 2** (`2026_02_14_*`): Phase2 modules (branches, containers, customs, HS codes, claims, drivers, AI/risk) + Phase3 enterprise (route optimization, capacity management, financial intelligence)
- **Phase 3** (`2026_03_05_*`): UUID migration (9 files — shadow columns, backfill, cutover, polymorphics, cleanup)
- **Phase 4** (`2026_03_06_*`): user_type normalization, internal RBAC, permission audience, email verification
- **Phase 5** (`2026_03_11–17_*`): Shipment workflow states, rate quotes, pricing support, declaration states, preflight wallet holds, document metadata, timeline fields, notification fanout

### Key Tables by Category

**Core/Auth:**
- `accounts` (uuid PK, type: individual/organization, status, slug, settings JSON)
- `users` (uuid PK, account_id FK, user_type: internal/external, status, is_owner, locale, timezone)
- `permissions` (uuid PK, key string unique, group, audience)
- `roles` (uuid PK, account_id, name, is_system, template)
- `role_permission` (pivot)
- `user_role` (pivot, assigned_by, assigned_at)
- `internal_roles`, `internal_user_role`, `internal_role_permission` (Phase 2B)
- `personal_access_tokens` (Sanctum)

**Logistics Core:**
- `shipments` (uuid PK, account_id, user_id, reference_number, status, source, carrier_code, carrier_tracking_number, rate_quote_id, selected_rate_option_id, balance_reservation_id — columns added incrementally across ~8 migrations)
- `parcels` (weight, dimensions)
- `shipment_events` (status, event_at, location)
- `shipment_status_history` (before/after status, reason)
- `carrier_shipments` (tracking_number, awb_number, label, correlation)
- `carrier_documents` (type, format, S3 path)
- `carrier_errors`
- `rate_quotes`, `rate_options`, `pricing_breakdowns`
- `tracking_events`, `tracking_webhooks`, `tracking_subscriptions`
- `shipment_exceptions`, `shipment_items`
- `content_declarations`

**Financial:**
- `wallets` (uuid PK, account_id unique, available_balance, locked_balance, SAR default)
- `wallet_ledger_entries` (append-only, running_balance)
- `wallet_holds` (shipment_id, idempotency_key, status, correlation_id)
- `wallet_topups`, `wallet_refunds`
- `billing_wallets` (parallel wallet table — possible duplication with `wallets`)
- `payment_transactions`, `payment_methods`, `payment_gateways`
- `invoices`, `invoice_items`
- `subscriptions`, `subscription_plans`
- `promo_codes`, `promo_code_usages`

**Logistics Extended:**
- `stores`, `store_sync_logs`, `webhook_events`
- `orders`, `order_items`
- `branches`, `branch_staff`, `branch_pnl`
- `companies`
- `containers`, `container_shipments`
- `vessels`, `vessel_schedules`
- `customs_declarations`, `customs_documents`, `customs_brokers`
- `hs_codes`
- `drivers`, `delivery_assignments`, `proof_of_delivery`
- `claims`, `claim_documents`, `claim_history`
- `tariff_rules`, `tax_rules`
- `incoterms`
- `route_plans`, `route_legs`, `route_cost_factors`, `route_suggestions`

**KYC/Compliance:**
- `kyc_verifications`, `kyc_documents`, `kyc_requests`, `kyc_audit_logs`
- `verification_cases`, `verification_documents`, `verification_reviews`, `verification_restrictions`
- `dg_classifications`, `dg_metadata`, `dg_audit_logs`

**Config/Reference:**
- `system_settings`
- `feature_flags`
- `status_mappings`
- `notifications`, `notification_templates`, `notification_preferences`, `notification_channels`, `notification_schedules`
- `audit_logs`, `immutable_audit_logs`
- `api_keys`
- `support_tickets`, `ticket_replies`, `sla_metrics`

### Notable Schema Issues

1. **TWO wallet tables:** `wallets` (from `2026_02_12_000011`) and `billing_wallets` (used by `BillingWalletService`). `Account` model has `wallet(): HasOne` pointing to `Wallet::class` but `ShipmentService` injects `BillingWalletService`. Unclear which is the canonical wallet.

2. **`shipments` table is rebuilt by 8+ migrations** adding columns incrementally. The original `0001_01_01_000001` migration creates a bigint-PK shipments table, then a second `2026_02_12_000013` creates another version, then UUID migrations convert it. The `up()` methods use `Schema::hasTable()` / `Schema::hasColumn()` guards throughout — but the initial migration at `0001_01_01_*` creates a conflicting table. Running all migrations on a fresh DB may fail due to duplicate table creation.

3. **FK comments "omitted"** appear repeatedly in migrations (e.g., `// FK omitted: accounts.id may be bigint (0001 migration) or uuid (2026 migration)`). This means the database has NO enforced foreign key constraints on many critical relationships.

4. **Missing indexes:** `shipments.carrier_tracking_number` (used for public tracking lookup) has no confirmed index in migrations.

### Logical ERD (Key Relationships)

```
Account (1) ──── (N) User
Account (1) ──── (N) Shipment
Account (1) ──── (N) Order
Account (1) ──── (1) Wallet / BillingWallet
Account (1) ──── (N) Store
Account (1) ──── (N) Role
Account (1) ──── (N) SupportTicket
Account (1) ──── (1) KycVerification
Account (1) ──── (1) OrganizationProfile

Shipment (1) ──── (N) Parcel
Shipment (1) ──── (N) ShipmentEvent
Shipment (1) ──── (N) TrackingEvent
Shipment (1) ──── (1) CarrierShipment
Shipment (1) ──── (N) CarrierDocument
Shipment (1) ──── (1) ContentDeclaration
Shipment (1) ──── (1) RateQuote → (N) RateOption
Shipment (1) ──── (1) WalletHold (balance_reservation)
Shipment (1) ──── (N) Claim
Shipment (1) ──── (N) ShipmentStatusHistory

Store (1) ──── (N) Order
Order (1) ──── (N) Shipment

User (N) ──── (N) Role [via user_role]
Role (N) ──── (N) Permission [via role_permission]
```

### Pivot/Junction Tables

`user_role`, `role_permission`, `internal_user_role`, `internal_role_permission`, `container_shipment`

---

## SECTION 7: PERMISSIONS & SECURITY AUDIT

### Auth Middleware Coverage

- **API external routes:** Protected by `['auth:sanctum', 'userType:external', 'tenantContext']` — GOOD
- **API internal routes:** Protected by `['auth:sanctum', 'userType:internal']` — GOOD
- **Web routes (external):** Protected by `['auth:web', 'userType:external', 'tenant']` — GOOD
- **Web admin routes:** Protected by `['auth:web', 'userType:internal', 'permission:admin.access']` — GOOD
- **Carrier webhook endpoints:** PUBLIC — no auth, signature verification done in service — ACCEPTABLE
- **Store webhook endpoints (`/webhooks/{platform}/{storeId}`):** PUBLIC — signature verification optional (only if platform is "supported") — RISK

### Permission Checks in Controllers

Controllers use Laravel's `$this->authorize()` which triggers the registered Policy. Policies call `AuthorizesTenantResource::allowsTenantAction()` which calls `$user->hasPermission()` which calls `PermissionResolver::can()`. The chain is:

`Controller::authorize()` → `ShipmentPolicy::viewAny()` → `AuthorizesTenantResource::allowsTenantAction()` → `PermissionResolver::can()` → `DB::table('user_role')...join('role_permission')...` 

This is correct but has a performance concern: every permission check hits the database (no caching layer).

### Mass Assignment Protection

- `User::$guarded = []` — ALL fields are mass-assignable. This is a SECURITY RISK.
- `Account::$guarded = []` — ALL fields are mass-assignable. SECURITY RISK.
- `Shipment::$guarded = []` — ALL fields are mass-assignable. SECURITY RISK.
- Most models use `$guarded = []` rather than explicit `$fillable` lists.

Only a few models (`RiskScore`, `WalletHold`) use explicit `$fillable`. The blanket `$guarded = []` pattern across core models (`User`, `Account`, `Shipment`) is a mass assignment vulnerability if any user-controlled input ever reaches `create()` or `fill()` without proper validation filtering first.

### Validation Coverage

- Controllers generally validate all inputs inline using `$request->validate()` — adequate
- Some `FormRequest` classes exist (`RegisterAccountRequest`, `UpdateUserRequest`, `CreateInvitationRequest`, etc.) for the IAM module but most other controllers validate inline
- The `ShipmentController::store()` validates all 30+ fields explicitly — GOOD
- `ClaimController::index()` accesses `$request->status`, `$request->claim_type` etc. directly without validation — these are read-only filter inputs but not explicitly declared as string type; potential for SQL injection if not properly escaped (Eloquent parameterization prevents this)

### Direct Object Access Vulnerabilities

Controllers correctly scope DB queries by `account_id` before operating on resources:
- `ShipmentController::findShipmentForCurrentTenant()` checks `where('account_id', ...)` before `firstOrFail()` — CORRECT
- `TrackingController::timeline()` checks `where('account_id', $user->account_id)` — CORRECT  
- `CarrierController::createAtCarrier()` checks `where('account_id', $user->account_id)` — CORRECT

### Public Routes That May Need Protection

1. `GET /api/v1/webhooks/track/{trackingNumber}` — Comment says "API key auth checked via middleware" but no such middleware appears on this specific route definition. This appears to be an unauthenticated public tracking endpoint which may be intentional but should be confirmed.

---

## SECTION 8: CODE QUALITY ASSESSMENT

### Controller Complexity

Controllers are appropriately thin — they validate, authorize, call service, return response. Notable exceptions:
- `PageController` (web layer) does direct Eloquent queries and CSV generation inline rather than using services [S5-2 PARTIALLY ACCEPTED — `fillReportWriter()` private helper present but logic NOT extracted to a Service class; full extraction remains an open task]
- `ProfitabilityController` does direct `DB::table('shipment_costs')` queries inline
- `IntelligenceController` does all DB query logic inline via `DB::table()`

### Service Layer Quality

- `ShipmentService` (530+ lines) — high quality: transactions, retry logic, KYC checks, audit logging
- `BillingWalletService` — high quality: idempotency, holds, ledger entries, no-delete policy
- `ReportService` — functional: uses `DB::table()` directly (bypasses Eloquent/AccountScope tenant isolation for reports — RISK)
- `TrackingService` — good: webhook verification, deduplication, event normalization
- `NotificationService` — functional but SMS dispatches inline HTTP calls (synchronous, not queued)
- `CarrierRateAdapter` — contains hardcoded DHL/Aramex simulated rate numbers (lines 89–150) — MAJOR ISSUE for production

### Naming Convention Consistency

- Services: `XxxService.php` — consistent
- Controllers: `XxxController.php` — consistent  
- Confusion: `BillingWalletService` vs `WalletBillingService` (two files, similar names, different purposes — unclear)
- Confusion: `WalletBillingController` vs `BillingWalletController` in Api/V1 (both exist)

### Error Handling

- `BusinessException` class used properly in services (custom exception with `error_code` field)
- `Handler.php` in `bootstrap/app.php` handles 500 errors with a Blade view
- Most services let Laravel's default exception handler propagate errors

### Code Duplication Instances

1. `currentAccountId()` method is duplicated in nearly every controller instead of being in the base `Controller` class (it IS in the base controller for some, but `ShipmentController` has its own private version)
2. `resolveCurrentAccountId()` — multiple controllers have this as a protected method
3. `findShipmentForCurrentAccount()` / `findShipmentForCurrentTenant()` — duplicated across `ShipmentController`, `TrackingController`, `CarrierController`, `RiskController`, `ShipmentWorkflowController`, `SLAController`

### TODO/FIXME/Placeholder Count (from read files)

- `app/Listeners/SendInvitationEmailListener.php` line 70: `// TODO: Send actual email` — CRITICAL
- `app/Services/Carriers/FedexCarrierAdapter.php` line 44: explicit "not yet implemented" comment — CRITICAL
- `app/Services/Carriers/FedexCarrierAdapter.php` line 55: cancellation "not yet implemented"
- `app/Http/Controllers/Api/V1/IntegrationController.php`: ~~returns hardcoded static array of integrations with `'status' => 'active'` for all~~ [FIXED S4-8] now uses DB-backed status inference from `stores` table (`Store::whereIn('status', ['active','connected'])`) — status is inferred from stored records, NOT from live API probes. Not equivalent to "real connectivity state". Accurate description: **DB-inferred integration status**.
- `app/Services/Carriers/CarrierAdapterFactory.php` comment block says "Does NOT replace existing CarrierService" — meaning it is a parallel implementation not yet wired in

### Hardcoded Values Found

- `PaymentService::$vatRate = 15.00` — hardcoded Saudi VAT rate (should be in config/SystemSetting)
- `InsuranceController::$rates` — hardcoded insurance rate table (basic/premium/full)
- `CarrierRateAdapter::fetchDhlRates()` — hardcoded `$baseMultiplier = $isIntl ? 18.0 : 8.0`
- `CarrierRateAdapter::fetchAramexRates()` — similar hardcoded multipliers
- `App\Services\Platforms\ShopifyAdapter` — hardcoded Shopify API version `2024-01`

---

## SECTION 9: CRITICAL WORKFLOW TRACES

### 1. Create Shipment: PARTIAL

**Route:** `POST /api/v1/shipments` (`api_external.php`)
**Middleware:** `auth:sanctum` + `userType:external` + `tenantContext`
**Controller:** `ShipmentController::store()` — validates 30 fields, calls `$this->authorize('create', Shipment::class)`
**Policy:** `ShipmentPolicy::create()` — checks `shipments.create` or `shipments.manage` permission via `PermissionResolver`
**Service:** `ShipmentService::createDirect()` — KYC assertion, DB transaction, `Shipment::create()`, `createParcels()`, `recordStatusChange()`, audit log
**Tables Touched:** `shipments`, `parcels`, `shipment_status_history`, `audit_logs`
**Status: WORKING** — but status is `draft` only; the 6-step workflow (validate → rate → select offer → preflight → purchase → create at carrier) requires additional API calls

### 2. Track Shipment: PARTIAL

**Route:** `GET /api/v1/shipments/{id}/tracking/timeline` (`api_external.php`)
**Controller:** `TrackingController::timeline()` — scoped to `account_id`
**Service:** `TrackingService::getTimeline()` via `ShipmentTimelineService`
**Status: WORKING** for displaying stored events

**Carrier Webhook path:** `POST /api/v1/webhooks/dhl/tracking`
**Service:** `TrackingService::processWebhook()` — logs `TrackingWebhook`, verifies signature, deduplicates, creates `TrackingEvent`
**Status: WORKING** for DHL and Aramex. FedEx webhook endpoint exists but `FedexCarrierAdapter::track()` returns an error response — no tracking update path for FedEx-created shipments.

### 3. Generate Report: WORKING

**Route:** `POST /api/v1/reports/export`
**Controller:** `ReportController::createExport()` — authorizes, validates type/format/filters
**Service:** `ReportService::createExport()` — creates `ReportExport` record, generates CSV/Excel/PDF, stores to Storage, emails signed download URL
**Tables Touched:** `report_exports`, `shipments` (raw DB queries)
**Status: WORKING** — but `DB::table('shipments')` bypasses global tenant scope; relies on `where('account_id', $account->id)` being passed explicitly

### 4. Process Payment: BROKEN (Gateway)

**Route:** `POST /api/v1/payments/topup`
**Controller:** `PaymentController::topUp()` — validates amount/gateway/payment_method/idempotency_key
**Service:** `PaymentService::topUpWallet()` — creates `PaymentTransaction` with status `pending`, then... does NOT call `PaymentGatewayFactory::make($gateway)->charge(...)`. It immediately marks as `completed` and adjusts the wallet balance.
**Status: BROKEN** — no real payment collection. The `MoyasarPaymentAdapter` exists and is registered in `PaymentGatewayFactory::$adapters` but is never called from `PaymentService`.

### 5. Send Notification: PARTIAL

**Dispatch:** `NotificationService::dispatch()` — resolves recipients, resolves channels, calls `sendToChannel()`
**Email channel:** `Mail::send(new NotificationMail(...))` — WORKING
**SMS channel:** `Http::post(Twilio URL, ...)` — WORKING if Twilio credentials configured
**In-app channel:** Creates `Notification` record — WORKING
**Webhook channel:** `Http::post($destination, ...)` — WORKING
**Invitation email specifically:** `SendInvitationEmailListener::handle()` — only creates an `AuditLog` entry; the `Mail::to(...)->queue(...)` call is commented out with `// TODO`
**Status: PARTIAL** — general notification dispatch works; invitation email is non-functional

### 6. Webhook Receive from Carrier: WORKING

**Route:** `POST /api/v1/webhooks/dhl/tracking`
**Controller:** `TrackingController::handleDhlWebhook()` → `handleCarrierWebhook($request, 'dhl')`
**Service:** `TrackingService::processWebhook()` — creates `TrackingWebhook`, verifies HMAC signature, extracts tracking number, finds shipment by tracking number, deduplicates by `replay_token`, creates `TrackingEvent`, normalizes status, triggers subscriber notifications
**Tables Touched:** `tracking_webhooks`, `tracking_events`, `shipments`, `status_mappings`, `tracking_subscriptions`
**Status: WORKING** for DHL and Aramex

---

## SECTION 10: DEAD CODE REGISTER

| Type | File | Evidence |
|---|---|---|
| **Commented implementation** | `app/Listeners/SendInvitationEmailListener.php` line 70 | `// Mail::to($invitation->email)->queue(new InvitationMail($invitation));` |
| **Parallel unused factory** | `app/Services/Carriers/CarrierAdapterFactory.php` | Not wired into `CarrierService`; factory is built but `CarrierService` uses direct DHL/FedEx constructors |
| **Unregistered package** | `spatie/laravel-permission`, `spatie/laravel-data`, `spatie/laravel-medialibrary`, `spatie/laravel-activitylog` | In `composer.json` `require` but no usage found in read files |
| **Unused controller methods** | `App\Http\Controllers\Web\Phase2WebController` | Named "Phase2" — likely scaffolding |
| **Unused controller** | `App\Http\Controllers\Web\Phase2CrudController` | Named "Phase2" — likely scaffolding |
| **Dead tracking** | `FedexCarrierAdapter::track()` | Explicitly returns error: "not yet implemented" |
| **Dead cancellation** | `FedexCarrierAdapter::cancel()` | Explicitly returns error: "not yet implemented" |
| **Parallel wallet service** | `app/Services/WalletBillingService.php` | May overlap with `BillingWalletService`; both exist |
| **Legacy migration** | `0001_01_01_000001_create_core_business_tables.php` | Creates bigint-PK shipments/stores/orders that are recreated by later UUID migrations |
| **Route public assets in routes/ dir** | `routes/public/`, `routes/resources/` | Static assets (icons, CSS, JS) incorrectly placed inside `routes/` directory instead of `public/` |
| **`routes/routes/web.php`** | `routes/routes/web.php` | A `web.php` nested inside `routes/routes/` — almost certainly erroneous, a stray file |
| **DhlApiService duplicate** | `app/Services/DhlApiService.php` (root) | Same class exists at `app/Services/Carriers/DhlApiService.php`; the root-level one may be unused |

---

## SECTION 11: ISSUES REGISTER

| # | Category | Description | Severity | File Evidence | Operational Impact | Recommendation |
|---|---|---|---|---|---|---|
| 1 | Carrier Integration | DHL and Aramex rate fetching returns hardcoded simulated prices, not real API data | CRITICAL | `CarrierRateAdapter.php` lines 89–150 | Customers charged incorrect shipping rates; potential financial loss | Implement real DHL Rates API and Aramex Rates API calls |
| 2 | Payment | `PaymentService::topUpWallet()` updates DB wallet balance without calling any payment gateway | CRITICAL | `PaymentService.php` topUpWallet method | Revenue not collected; users can top up without paying | Wire `PaymentGatewayFactory::make($gateway)->charge()` into `PaymentService` |
| 3 | Notification | Invitation emails are never sent — send code is commented out with TODO | CRITICAL | `SendInvitationEmailListener.php` line 70 | New users invited to platform never receive invitation link | Remove comment, implement `Mail::to()->queue()` with queue worker |
| 4 | Carrier Integration | FedEx tracking returns explicit "not yet implemented" error | CRITICAL | `FedexCarrierAdapter.php` lines 44–51 | Cannot track shipments created via FedEx (only real carrier rate provider) | Implement `FedexTrackingProvider` |
| 5 | Queue | No background job queue — all async work runs synchronously or not at all | CRITICAL | No `app/Jobs/` directory | High latency for reports, notifications; no retry on failure | Implement job queue with dedicated worker |
| 6 | Schema | `wallets` and `billing_wallets` tables both exist; unclear which is canonical | HIGH | `wallets` migration, `billing_wallets` model, `Account::wallet()` | Financial data split across two systems; reconciliation errors | Consolidate to single wallet table; update all references |
| 7 | Security | Core models (`User`, `Account`, `Shipment`) use `$guarded = []` — all fields mass-assignable | HIGH | `User.php`, `Account.php`, `Shipment.php` line: `protected $guarded = []` | Mass assignment attacks possible if any unfiltered input reaches `create()`/`update()` | Replace with explicit `$fillable` arrays |
| 8 | Schema | 14+ UUID migration files create high-risk multi-step schema conversion with no confirmed rollback | HIGH | `2026_03_05_000100` through `2026_03_17_000333` | Database corruption risk during migration execution on production | Test full migration run on production-clone DB; add transaction wrapping |
| 9 | Carrier Integration | `CarrierAdapterFactory` is built and documented but not wired into `CarrierService` | HIGH | `CarrierAdapterFactory.php` header comment | Aramex adapter never used despite being implemented | Wire factory into `CarrierService` replacing direct instantiation |
| 10 | Architecture | Two wallet service classes: `BillingWalletService` and `WalletBillingService` | HIGH | Both files exist in `app/Services/` | Code duplication; confusion about which to use; potential inconsistency | Merge into single service, remove duplicate |
| 11 | Security | Public tracking endpoint (`/track/{trackingNumber}`) has no confirmed auth middleware | HIGH | `api.php` line 131 | Exposes all shipment tracking data publicly without authentication | Add API key middleware or confirm public intent is documented |
| 12 | Dependency | `spatie/laravel-permission` in composer but custom RBAC used | MEDIUM | `composer.json`; no Spatie models used | Unused package adds dependency weight; confusing for new developers | Remove from `composer.json` if not used |
| 13 | Dependency | `spatie/laravel-medialibrary`, `spatie/laravel-data`, `spatie/laravel-activitylog` — all in require but unused | MEDIUM | `composer.json` | Autoloaded dead weight | Remove unused packages |
| 14 | Code Quality | `currentAccountId()` / `findShipmentForCurrentAccount()` duplicated in 10+ controllers | MEDIUM | Multiple controller files | Maintenance burden; inconsistency risk | Move to base Controller class |
| 15 | Performance | Permission check hits DB on every request (no caching) | MEDIUM | `PermissionResolver.php` | High DB load under concurrent traffic | Add Redis cache for user permissions with TTL |
| 16 | Schema | Many foreign key constraints are explicitly omitted (`// FK omitted`) | MEDIUM | Multiple migration files | Referential integrity not enforced; orphaned records possible | Add FKs on critical relationships |
| 17 | Reports | `ReportService` uses `DB::table()` bypassing Eloquent global tenant scope | MEDIUM | `ReportService.php` line 49 | Cross-tenant data leak if `account_id` filter is ever missed | Add explicit `where account_id` assertion or use Model queries |
| 18 | File Organization | Static assets (icons, CSS, JS, manifest) placed in `routes/public/` and `routes/resources/` | MEDIUM | `routes/public/`, `routes/resources/` | These are not served by the web server from `routes/`; assets unreachable | Move to `public/` directory |
| 19 | File Organization | `routes/routes/web.php` — stray nested file | LOW | `routes/routes/web.php` | Confusing; unused | Delete file |
| 20 | DHL Duplication | Two `DhlApiService.php` files at different namespaces | MEDIUM | `app/Services/DhlApiService.php` vs `app/Services/Carriers/DhlApiService.php` | Container may resolve wrong class | Delete root-level duplicate, confirm only Carriers/ version is used |
| 21 | Insurance | Insurance rates are hardcoded in controller instead of DB config | LOW | `InsuranceController.php` `$rates` property | Cannot adjust rates without code deploy | Move to `SystemSetting` or config table |
| 22 | VAT | Saudi VAT rate (15%) hardcoded in `PaymentService` | LOW | `PaymentService.php` line 38 | Cannot change without code deploy | Move to `config/services.php` or `SystemSetting` |

---

## SECTION 12: LOGISTICS DOMAIN FIT

| Area | Status | Evidence |
|---|---|---|
| **Air freight** | Partial | `shipment_type` enum includes 'air'; route plans support air mode; no airline API integration |
| **Sea freight** | Partial | Containers, vessels, vessel schedules, port-type branches exist; no shipping line API integration |
| **Land freight** | Partial | Driver management, delivery assignment, proof-of-delivery exist for last-mile; no trucking carrier API |
| **Container tracking** | Partial | Container model, `container_shipments` pivot, vessel schedules present; no live container tracking API |
| **Bill of Lading** | Missing | `TransportDocument` model exists but B/L generation and legal document workflow not confirmed |
| **Customs clearance** | Partial | `CustomsDeclaration`, `CustomsBroker`, `CustomsDocument` models exist; no customs authority API integration |
| **Carrier integrations** | Partial | DHL (real HTTP, simulated rates), FedEx (real rates, no tracking/cancel), Aramex (real adapter, not wired), SMSA/Naqel/Zajil/iMile/J&T (names only in factory, no adapters) |
| **Shipment costing/profitability** | Partial | `ProfitabilityController`, `ShipmentCost`, `BranchPnl`, `CommissionCalculationService` exist; data relies on correct rate data flowing in |
| **Customer portal** | Partial | B2B (organizations) and B2C (individuals) web portals exist with dashboards, shipments, orders, wallet, support |
| **Driver/fleet** | Partial | `Driver`, `DeliveryAssignment`, `ProofOfDelivery` models with CRUD and assignment; no GPS tracking integration |
| **HS Codes** | Present | `HsCode` model, `HsCodeController`, lookup endpoint |
| **Incoterms** | Present | `Incoterm` model, `IncotermController` |
| **DG (Dangerous Goods)** | Partial | DG flag on shipments, DG classification, audit log; no IATA/ICAO validation API |
| **Insurance** | Partial | Quote calculation with 3-tier plans; no external insurer API |
| **Multi-modal** | Partial | Route plans support `multimodal` mode; route legs with different transport modes; no orchestration engine |

---

## SECTION 13: COMPLETION MATRIX

| Module | Completeness | Production Ready | Priority |
|---|---|---|---|
| Authentication/IAM | 95% | YES | Maintain |
| Multi-tenancy | 90% | YES | Maintain |
| User Management | 90% | YES | Maintain |
| RBAC/Permissions | 85% | YES | Add caching |
| Account Management | 90% | YES | Maintain |
| Orders | 85% | YES | Maintain |
| Store Integrations (Shopify/WooCommerce/Salla/Zid) | 70% | PARTIAL | OAuth token refresh |
| Wallet (DB layer) | 85% | CONDITIONAL | Complete gateway wiring |
| Reports/Analytics | 70% | PARTIAL | Fix tenant scoping in raw queries |
| Tracking (DHL/Aramex) | 70% | PARTIAL | Production after webhook secrets configured |
| Notifications | 65% | NO | Fix invitation email, add queue |
| Shipment (draft + workflow) | 75% | NO | Complete carrier creation for all carriers |
| KYC Compliance | 65% | NO | Complete document upload/review flow |
| **Payments (gateway)** | **30%** | **NO** | **Wire gateway adapter — URGENT** |
| Carrier Rates (FedEx) | 70% | PARTIAL | Enable via feature flag |
| Carrier Rates (DHL/Aramex) | 20% | NO | Implement real rate API calls |
| Carrier Tracking (FedEx) | 10% | NO | Implement FedexTrackingProvider |
| Support Tickets/SLA | 65% | NO | Complete email notifications |
| Claims | 60% | NO | Complete external carrier claim API |
| Last Mile Delivery | 60% | NO | Complete |
| Container/Vessel | 55% | NO | Low urgency for launch |
| Customs | 55% | NO | Low urgency for launch |
| Risk Management | 55% | NO | Rule-based acceptable for launch |
| Insurance | 50% | NO | Low urgency |
| Branches/Companies | 60% | NO | Medium urgency |
| DG Compliance | 55% | NO | Required for any DG shipments |

---

## SECTION 14: FINAL RECOMMENDATIONS

### Top 10 Fixes Needed Immediately

1. **Wire `PaymentGatewayFactory` into `PaymentService`** — currently the entire payment collection flow is a no-op. The Moyasar adapter is built; it just needs to be called.

2. **Fix invitation email delivery** — uncomment `Mail::to($invitation->email)->queue()` in `SendInvitationEmailListener` and set up a queue worker. New user onboarding is completely broken without this.

3. **Implement real DHL rate fetching** — replace the hardcoded simulated rates in `CarrierRateAdapter::fetchDhlRates()` with actual DHL Rates API calls via `DhlApiService`.

4. **Implement FedEx tracking** — create `FedexTrackingProvider` and wire it into `FedexCarrierAdapter::track()`. FedEx is the only carrier with real rate integration but cannot track.

5. **Set up queue worker** — create at least one Job class (e.g., `SendNotificationJob`, `ProcessWebhookJob`) and configure a queue driver (Redis is already in dependencies).

6. **Consolidate wallet tables** — resolve the `wallets` vs `billing_wallets` dual-table ambiguity before production data is written to both.

7. **Add explicit `$fillable` to `User`, `Account`, `Shipment`** — replace `$guarded = []` with explicit field lists.

8. **Test full UUID migration chain on a production data clone** — the 14-file migration chain is the highest database risk in the project.

9. **Remove or resolve public tracking endpoint auth** — clarify whether `/webhooks/track/{trackingNumber}` is intentionally public or needs API key middleware.

10. **Fix static asset directory** — move `routes/public/` and `routes/resources/` contents to the actual `public/` directory so PWA assets are served correctly.

### Top 10 Modules to Complete

1. Payment gateway integration (real money collection)
2. DHL real rates (revenue depends on accurate pricing)
3. FedEx tracking (operational visibility for live shipments)
4. Aramex wire-up into main carrier flow via `CarrierAdapterFactory`
5. Notification queue / invitation email fix
6. KYC document upload + review workflow
7. Support ticket email notification on reply
8. Claims external carrier/insurer API integration
9. DG compliance IATA/ICAO validation
10. Profitability reporting with real cost data (depends on #2, #3, #4)

### What to Refactor

1. **Extract `currentAccountId()` to base Controller** — stop duplicating in every controller
2. **Extract `findShipmentForCurrentAccount()` to a shared ShipmentFinder service or base controller trait**
3. **Add permission caching layer in `PermissionResolver`** — one DB query per permission check does not scale
4. **Replace `DB::table()` in `ReportService`** with scoped Eloquent queries to avoid cross-tenant data leakage risk
5. **Merge `BillingWalletService` and `WalletBillingService`** into a single authoritative wallet service
6. **Remove unused composer dependencies** (spatie/permission, spatie/data, spatie/medialibrary, spatie/activitylog)

### What to Keep As-Is

1. The `BelongsToAccount` trait + `AccountScope` global scope — correctly implemented and widely used
2. The `PermissionResolver` custom RBAC — clean and flexible (just add caching)
3. The `TenantMiddleware` + `ResolveTenantContextMiddleware` duality — handles both web and API contexts correctly
4. The `ShipmentService` core business logic — well-structured with proper transactions and retry logic
5. The `BillingWalletService` ledger implementation — append-only ledger with holds is correctly done
6. The 4-platform store adapter pattern (Shopify/WooCommerce/Salla/Zid) — solid architecture
7. The `TrackingService::processWebhook()` implementation — replay protection, signature verification, deduplication are all present

### Technical Roadmap in Priority Order

**Sprint 1 — Revenue Critical (1–2 weeks)**
- Wire Moyasar payment gateway into `PaymentService`
- Fix invitation emails + set up queue worker
- Real DHL rate API calls

**Sprint 2 — Operational Completeness (2–4 weeks)**
- FedEx tracking provider
- Aramex wired via `CarrierAdapterFactory`
- Wallet table consolidation
- Permission caching

**Sprint 3 — Security Hardening (1–2 weeks)**
- Replace `$guarded = []` with `$fillable` in core models
- UUID migration chain testing and validation
- Tenant scope enforcement in ReportService

**Sprint 4 — Domain Completeness (4–8 weeks)**
- KYC document workflow
- DG compliance validation
- Claims external API
- Support ticket notifications
- Profitability reporting accuracy

**Sprint 5 — Scale Prep (ongoing)**
- All background processing via queue jobs
- Redis caching for permissions + frequently accessed data
- Carrier webhook idempotency verification at scale
- Load testing of shipment creation + rate fetching

---

### Key File References

- Project root: `C:\Users\legion\Documents\GitHub\cebx-code`
- Route files: `routes/api.php`, `routes/api_external.php`, `routes/api_internal.php`, `routes/web.php`
- Critical issue (simulated rates): `app/Services/Carriers/CarrierRateAdapter.php` lines 89–150
- Critical issue (payment gateway): `app/Services/PaymentService.php`
- Critical issue (invitation email): `app/Listeners/SendInvitationEmailListener.php` line 70
- Critical issue (FedEx tracking): `app/Services/Carriers/FedexCarrierAdapter.php` lines 44–60
- Tenant isolation: `app/Models/Traits/BelongsToAccount.php`, `app/Http/Middleware/TenantMiddleware.php`
- Permission system: `app/Services/Auth/PermissionResolver.php`, `app/Http/Middleware/CheckPermission.php`
- Carrier factory: `app/Services/Carriers/CarrierAdapterFactory.php`
- Payment factory: `app/Services/Payments/PaymentGatewayFactory.php`
- Core shipment workflow: `app/Services/ShipmentService.php`, `app/Http/Controllers/Api/V1/ShipmentController.php`

---

## SECTION 15: BLOCKER-CARRIER-FACTORY — ENGINEERING TRACE MATRIX

**Scope:** Full trace of 5 carrier operations across the two parallel architectures.
**Date produced:** 2026-03-19
**Status:** FORMAL DECISION → DEFER FULL REFACTOR (3 low-risk prerequisites executed)

---

### Architecture Overview

Two parallel carrier architectures coexist with zero overlap at runtime:

| Architecture | Contract | Entry point | Used by runtime? |
|---|---|---|---|
| **Runtime (legacy)** | `CarrierShipmentProvider` (3 methods) | `CarrierService` | YES |
| **Factory (future)** | `CarrierInterface` (5 methods) | `CarrierAdapterFactory::make()` | NO — never called from runtime |

`CarrierService` is injected via Laravel container (`__construct` DI). `CarrierAdapterFactory` is a static class called manually — and currently called by nothing in production paths.

---

### OP-1: createShipment

| Column | Runtime Path | Factory/Adapter Path |
|---|---|---|
| **Entry** | `CarrierService::createAtCarrier(Shipment, User)` | `CarrierAdapterFactory::make($code)->createShipment($payload)` |
| **Dispatch** | `resolveShipmentProvider($shipment)` → throws `ERR_CARRIER_PROVIDER_NOT_SUPPORTED` for any carrier != `fedex` → returns `FedexShipmentProvider` | `make('dhl')` → `DhlCarrierAdapter`, `make('fedex')` → `FedexCarrierAdapter`, `make('aramex')` → `AramexCarrierAdapter` |
| **Contract** | `CarrierShipmentProvider` (`carrierCode()`, `isEnabled()`, `createShipment(array $context)`) | `CarrierInterface` (`createShipment(array $payload)`) — different contract, different payload shape |
| **Payload shape** | `buildCarrierCreationContext()` — enriched context: shipment model relations, label format/size, correlation ID, idempotency key | Flat `array $payload` — no schema enforced; caller-defined |
| **Response shape** | Expects keys: `carrier_code`, `carrier_name`, `carrier_shipment_id`, `tracking_number`, `awb_number`, `service_code`, `service_name`, `initial_carrier_status`, `request_payload`, `response_payload`, `carrier_metadata`, label content (base64 in `content`) | Unstructured `array` — adapter may return anything |
| **Failure behavior** | `\Throwable` → `logCarrierError(OP_CREATE_SHIPMENT)` → DB tx: `CarrierShipment::STATUS_FAILED` + `Shipment::STATUS_FAILED` → throws `BusinessException('ERR_CARRIER_CREATE_FAILED', 502)` | No wrapper: propagates raw exception. No DB state change, no audit, no wallet rollback |
| **Compatibility** | ❌ INCOMPATIBLE | Contract mismatch; payload shape mismatch; missing 210-line orchestration (idempotency check, wallet hold capture, timeline recording, document persistence, audit logging, error DB recording) |
| **Decision** | **DEFER** — Cannot swap factory into runtime without rebuilding the entire orchestration layer around it |

---

### OP-2: cancel

| Column | Runtime Path | Factory/Adapter Path |
|---|---|---|
| **Entry** | `CarrierService::cancelAtCarrier(Shipment, User)` | `CarrierAdapterFactory::make($code)->cancel($shipmentId, $trackingNumber)` |
| **Guard** | `assertLegacyDhlCarrierShipment()` → throws `ERR_CARRIER_OPERATION_NOT_SUPPORTED` (HTTP 422) for any carrier != `dhl` | None — all adapters exposed |
| **Underlying call** | `DhlApiService::cancelShipment(carrier_shipment_id, tracking_number)` directly | DHL: `DhlApiService::cancelShipment()` same. FedEx: returns `['success'=>false, 'error'=>'not implemented']` — **no exception thrown** |
| **Contract** | None (direct DhlApiService call after guard) | `CarrierInterface::cancel(string $shipmentId, string $trackingNumber): array` |
| **Payload shape** | `$carrierShipment->carrier_shipment_id` + `$carrierShipment->tracking_number` (from DB record) | Same 2 parameters — compatible at parameter level |
| **Response shape** | Response ignored — success = no exception thrown | Returns `array` — `['success'=>bool, ...]`; FedEx returns false silently |
| **Failure behavior** | `\Throwable` → `logCarrierError(OP_CANCEL)` → `BusinessException::carrierCancelFailed()` | DHL: propagates exception. FedEx: **SILENT FALSE** — caller must inspect `$result['success']` or miss the failure |
| **Compatibility** | ⚠️ PARTIAL | DHL: API-level compatible. FedEx: silently broken — `FedexCarrierAdapter::cancel()` never throws, returns false array. Currently safe only because `assertLegacyDhlCarrierShipment()` blocks non-DHL at runtime |
| **Decision** | **DEFER** — FedEx cancel is blocked by guard. Factory FedEx adapter fixed (throws instead of returns false) as Prerequisite #3 |

---

### OP-3: getLabel

| Column | Runtime Path | Factory/Adapter Path |
|---|---|---|
| **Entry** | `CarrierService::refetchLabel(Shipment, User, ?format)` | `CarrierAdapterFactory::make($code)->getLabel($shipmentId, $format)` |
| **Guard** | `assertLegacyDhlCarrierShipment()` → throws for non-DHL | None |
| **Underlying call** | `DhlApiService::fetchLabel(carrier_shipment_id, tracking_number, format)` — both IDs distinct, sourced from `CarrierShipment` DB record | DHL adapter: `DhlApiService::fetchLabel($shipmentId, $shipmentId, $format)` — **BUG: same value for both args**. FedEx: returns `['success'=>false]` silently |
| **Contract** | None (direct DhlApiService call after guard) | `CarrierInterface::getLabel(string $shipmentId, string $format = 'pdf'): array` — only 1 ID parameter, cannot pass separate tracking_number |
| **Payload shape** | Requires `carrier_shipment_id` AND `tracking_number` as separate distinct values | Only `$shipmentId` available — tracking_number structurally missing from interface |
| **Response shape** | Expects `['content'=>base64, 'url'=>?string]` → stored in `CarrierDocument::create()` → updates `CarrierShipment` + `Shipment` | Returns raw array — no document persistence |
| **Failure behavior** | `\Throwable` → `logCarrierError(OP_RE_FETCH_LABEL)` → `BusinessException::labelRefetchFailed()` | DHL: propagates exception (likely 4xx from DHL API due to wrong tracking_number). FedEx: silent false return |
| **Compatibility** | ❌ INCOMPATIBLE | BUG in DhlCarrierAdapter::getLabel (`$shipmentId` used for both args). Interface missing `$trackingNumber` parameter. No document persistence in factory path |
| **Decision** | **Prerequisite #1 executed** — DhlCarrierAdapter::getLabel fixed to accept optional `$trackingNumber` param. Full factory integration deferred |

---

### OP-4: track

| Column | Runtime Path | Factory/Adapter Path |
|---|---|---|
| **Entry** | `TrackingService::processWebhook(payload, headers, sourceIp, carrierCode)` — **INBOUND webhook only** | `CarrierAdapterFactory::make($code)->track($trackingNumber)` — outbound poll |
| **Dispatch** | No outbound `track()` in CarrierService or TrackingService. TrackingService constructor injects `DhlApiService` but only for webhook parsing, not polling | DHL: `DhlApiService::trackShipment()`. FedEx: `FedexTrackingProvider::track()`. Aramex: `AramexCarrierAdapter::track()` |
| **Contract** | N/A (webhook-driven, no outbound polling in runtime) | `CarrierInterface::track(string $trackingNumber): array` |
| **Payload shape** | Inbound: webhook `payload` array + headers + sourceIp | Outbound: single `string $trackingNumber` |
| **Response shape** | Internal: `TrackingWebhook` + `TrackingEvent` DB records | DHL: `['events'=>[...], 'status'=>..., 'estimated_delivery'=>...]`. FedEx: `['tracking_number'=>..., 'status'=>..., 'events'=>[...], '_live'=>bool]`. On failure: `['error'=>..., 'events'=>[]]` (no throw) |
| **Failure behavior** | Webhook: rejects with `'status'=>'rejected'` — never throws to HTTP layer | DHL: propagates exception. FedEx: returns `['error'=>..., 'events'=>[]]` silently on failure. Aramex: returns `disabledResponse()` array if disabled |
| **Compatibility** | ✅ NO CONFLICT — completely different operations (inbound vs outbound) | Factory path is the only outbound track path — no runtime equivalent to conflict with |
| **Decision** | **Factory IS the correct path for outbound polling**. No refactor needed. FedEx silent-error behavior noted — standardization deferred |

---

### OP-5: getRates

| Column | Runtime Path | Factory/Adapter Path |
|---|---|---|
| **Entry** | `CarrierRateAdapter::fetchRates(array $params)` | `CarrierAdapterFactory::make($code)->getRates($params)` |
| **Dispatch** | Route by `$params['carrier_code']`: `fedex` → `FedexRateProvider`, `dhl_express` → `DhlApiService` + simulation fallback, `aramex` → `AramexCarrierAdapter`, `null` → FedEx if enabled else DHL+Aramex combined | `make('fedex')` → `FedexRateProvider::fetchNetRates()` only. `make('dhl')` → `DhlApiService::getRates()` only. `make('aramex')` → `AramexCarrierAdapter::getRates()` |
| **Contract** | None (direct class calls) | `CarrierInterface::getRates(array $params): array` |
| **Payload shape** | `['carrier_code', 'chargeable_weight', 'total_weight', 'origin_country', 'destination_country', ...]` | Same `array $params` — compatible |
| **Response shape** | FedEx: merged `fetchServiceAvailability` + `fetchNetRates` → normalized offers array. DHL: real rates or simulated fallback. Aramex: real or simulated | FedEx: `fetchNetRates()` only — **skips availability merge** — different shape. DHL: real API only (no simulation fallback). Aramex: same |
| **Failure behavior** | FedEx disabled → `BusinessException('ERR_FEDEX_NOT_ENABLED', 503)`. DHL no key + simulation disabled → `BusinessException('ERR_DHL_RATES_UNAVAILABLE', 503)` | Factory: propagates raw exception from underlying service — no `BusinessException` wrapping, no simulation guard |
| **Compatibility** | ⚠️ PARTIAL | Same underlying APIs but: (1) FedEx response shape differs (availability omitted), (2) no simulation fallback guard in factory, (3) no `BusinessException` wrapping |
| **Decision** | **DEFER** — `CarrierRateAdapter` remains authoritative. Factory path is not wired and would produce different response shapes if called |

---

### Formal Decision: DEFER FULL REFACTOR

**Rationale:**

Full factory integration into `CarrierService` requires:
1. Rewriting `createAtCarrier()` (~210 lines) to dispatch via factory while preserving: idempotency, DB transactions, wallet hold capture, timeline recording, document persistence, audit logging, error state recording
2. Rewriting `cancelAtCarrier()` to dispatch via factory, adding equivalent DB state management per carrier
3. Rewriting `refetchLabel()` to handle document persistence per carrier, resolving the missing `tracking_number` parameter in the interface
4. Defining a stable, versioned response contract between `CarrierInterface` and the orchestration layer
5. Integration tests per carrier × operation

**Risk of doing it now:** HIGH — touches the only working shipment creation path (FedEx) which is the sole revenue path.

**Prerequisites executed (low-risk, safe to do now):**
- ✅ **Prerequisite #1:** `DhlCarrierAdapter::getLabel` — fixed to accept optional `$trackingNumber` param, eliminating the same-value bug
- ✅ **Prerequisite #2:** `CarrierAdapterFactory::make()` — changed from `new $class()` to `app($class)` for Laravel DI compatibility
- ✅ **Prerequisite #3:** `FedexCarrierAdapter::cancel()` and `getLabel()` — changed from silent false return to `\RuntimeException` throw, preventing silent failure if ever called

**Remaining prerequisites (require separate sprint):**
- P4: Define versioned response contract DTO for `CarrierInterface` responses
- P5: Add `$trackingNumber` to `CarrierInterface::getLabel` signature, update all adapters
- P6: Rewrite `CarrierService::createAtCarrier()` orchestration to dispatch via factory
- P7: Rewrite cancel/refetchLabel to be carrier-agnostic

---
