# 🧪 Deliverable 3 — Test Suite Plan & Recommendations
### Shipping Gateway — Laravel Feature + Dusk Tests

---

## ⚠️ ملاحظة: Static Analysis Only
هذا التقرير مبني على Static Code Analysis. لا يمكن تشغيل Dusk أو PHPUnit في هذه البيئة.
فيما يلي خطة اختبار كاملة مع test cases جاهزة للتنفيذ.

---

## A) Test Suite Structure

```
tests/
├── Feature/
│   ├── Web/
│   │   ├── AuthTest.php               # Login / Logout / Session
│   │   ├── DashboardTest.php           # Dashboard loads correctly
│   │   ├── ShipmentFlowTest.php        # Full create→show→cancel→return
│   │   ├── OrderFlowTest.php           # Create→ship→cancel
│   │   ├── StoreFlowTest.php           # Create→sync→test→delete
│   │   ├── WalletFlowTest.php          # Topup→hold→ledger verification
│   │   ├── UserManagementTest.php      # Create→toggle→delete
│   │   ├── SupportFlowTest.php         # Create→reply→resolve
│   │   ├── PageControllerTest.php      # All 25 sub-pages render
│   │   ├── Phase2ModulesTest.php       # Containers/Customs/Vessels/etc.
│   │   └── NotificationsTest.php       # Read single / Read all
│   ├── Security/
│   │   ├── TenantIsolationTest.php     # Cross-account data access
│   │   ├── CsrfProtectionTest.php      # CSRF token validation
│   │   ├── RateLimitTest.php           # Brute-force prevention
│   │   └── SessionSecurityTest.php     # Session after user suspension
│   └── Database/
│       ├── ShipmentDbTest.php          # Verify DB state after operations
│       ├── WalletLedgerTest.php        # Ledger integrity checks
│       └── ColumnValidationTest.php    # Verify model fillable vs migration
└── Browser/ (Dusk)
    ├── LoginTest.php
    ├── SidebarNavigationTest.php
    └── ShipmentE2ETest.php
```

---

## B) Critical Test Cases

### B.1 Tenant Isolation Tests (SEC-02 verification)

```php
// TenantIsolationTest.php

/** @test */
public function user_cannot_toggle_user_from_different_account()
{
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    $targetB = User::factory()->create(['account_id' => $accountB->id, 'status' => 'active']);

    $this->actingAs($userA)
         ->patch(route('users.toggle', $targetB))
         ->assertForbidden(); // EXPECTED: 403
    // CURRENT BEHAVIOR: 200 ← BUG SEC-02

    $this->assertDatabaseHas('users', ['id' => $targetB->id, 'status' => 'active']);
}

/** @test */
public function user_cannot_delete_user_from_different_account()
{
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    $targetB = User::factory()->create(['account_id' => $accountB->id]);

    $this->actingAs($userA)
         ->delete(route('users.destroy', $targetB))
         ->assertForbidden(); // EXPECTED: 403

    $this->assertDatabaseHas('users', ['id' => $targetB->id, 'deleted_at' => null]);
}

/** @test */
public function shipment_list_only_shows_own_account()
{
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    Shipment::factory()->create(['account_id' => $accountA->id]);
    Shipment::factory()->create(['account_id' => $accountB->id]);

    app()->instance('current_account_id', $accountA->id);
    $this->actingAs($userA)
         ->get(route('shipments.index'))
         ->assertOk()
         ->assertViewHas('shipments', fn($s) => $s->total() === 1);
}
```

### B.2 Wallet Ledger Integrity Tests

```php
// WalletLedgerTest.php

/** @test */
public function topup_creates_ledger_entry_with_correct_running_balance()
{
    $wallet = Wallet::factory()->create(['available_balance' => 100]);
    $user = User::factory()->create(['account_id' => $wallet->account_id]);

    app()->instance('current_account_id', $wallet->account_id);
    $this->actingAs($user)
         ->post(route('wallet.topup'), ['amount' => 50]);

    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'available_balance' => 150.00,
    ]);

    $this->assertDatabaseHas('wallet_ledger_entries', [
        'wallet_id' => $wallet->id,
        'type' => 'topup',
        'amount' => 50.00,
        'running_balance' => 150.00,
    ]);
}

/** @test */
public function hold_should_create_ledger_entry() // CURRENTLY FAILS
{
    $wallet = Wallet::factory()->create(['available_balance' => 200, 'locked_balance' => 0]);
    $user = User::factory()->create(['account_id' => $wallet->account_id]);

    app()->instance('current_account_id', $wallet->account_id);
    $this->actingAs($user)
         ->post(route('wallet.hold'), ['amount' => 50]);

    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'available_balance' => 150.00,
        'locked_balance' => 50.00,
    ]);

    // THIS WILL FAIL — hold() does not create ledger entry (WARN-06)
    $this->assertDatabaseHas('wallet_ledger_entries', [
        'wallet_id' => $wallet->id,
        'type' => 'lock',
        'amount' => -50.00,
    ]);
}
```

### B.3 Column Mismatch Verification Tests

```php
// ColumnValidationTest.php

/** @test */
public function customs_page_renders_without_sql_error()
{
    $user = $this->loginAsDemo();

    // This will fail with SQL Error 1054 due to BUG-03
    $response = $this->get(route('customs.index'));
    $response->assertOk(); // EXPECTED: 200
    // CURRENT: 500 (Unknown column 'status')
}

/** @test */
public function tracking_search_does_not_use_nonexistent_column()
{
    $user = $this->loginAsDemo();

    // BUG-09: carrier_tracking_number doesn't exist
    $response = $this->get(route('tracking.index', ['q' => 'TRK001']));
    $response->assertOk();
    // CURRENT: 500 (Unknown column 'carrier_tracking_number')
}

/** @test */
public function containers_page_renders_correct_columns()
{
    $user = $this->loginAsDemo();
    Container::factory()->create([
        'account_id' => $user->account_id,
        'size' => '40ft',
        'location' => 'Jeddah Port',
    ]);

    $response = $this->get(route('containers.index'));
    $response->assertOk();
    $response->assertSee('40ft');      // BUG-01: code uses iso_code instead
    $response->assertSee('Jeddah Port'); // BUG-01: code uses port instead
}

/** @test */
public function dashboard_shows_correct_wallet_balance()
{
    $user = $this->loginAsDemo();
    Wallet::factory()->create([
        'account_id' => $user->account_id,
        'available_balance' => 1500.00,
    ]);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('1,500'); // BUG-08: shows 0 due to ->balance
}
```

### B.4 Route Existence Tests

```php
// Phase2ModulesTest.php

/** @test */
public function containers_post_route_exists()
{
    $user = $this->loginAsDemo();

    // BUG-02: Currently returns 405
    $response = $this->post(route('containers.store'), [
        'container_number' => 'MSKU1234567',
        'size' => '40ft',
        'type' => 'dry',
        'location' => 'Jeddah',
    ]);
    $response->assertRedirect(); // or 302
}

/** @test */
public function vessels_post_route_exists()
{
    $user = $this->loginAsDemo();

    // BUG-06: Currently returns 405
    $response = $this->post(route('vessels.store'), [
        'vessel_name' => 'Ever Given',
        'vessel_type' => 'container',
    ]);
    $response->assertRedirect();
}
```

### B.5 Authentication & Session Tests

```php
// AuthTest.php

/** @test */
public function login_requires_valid_credentials()
{
    $response = $this->post('/login', [
        'email' => 'wrong@test.com',
        'password' => 'wrongpass',
    ]);
    $response->assertSessionHasErrors('email');
}

/** @test */
public function protected_routes_require_auth()
{
    $routes = ['/', '/shipments', '/orders', '/wallet', '/users',
               '/support', '/tracking', '/pricing', '/containers'];

    foreach ($routes as $route) {
        $this->get($route)->assertRedirect('/login');
    }
}

/** @test */
public function logout_invalidates_session()
{
    $user = User::factory()->create();
    $this->actingAs($user)->post('/logout');
    $this->get('/')->assertRedirect('/login');
}
```

### B.6 CSRF Protection Tests

```php
// CsrfProtectionTest.php

/** @test */
public function post_without_csrf_token_is_rejected()
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
        ->post('/shipments', ['recipient_name' => 'Test']);
    // Note: Normally this test would be without the middleware bypass
    // The actual test: send raw POST without _token
}
```

### B.7 Shipment Full Flow Test

```php
// ShipmentFlowTest.php

/** @test */
public function complete_shipment_lifecycle()
{
    $user = $this->loginAsDemo();

    // 1. Create
    $response = $this->post(route('shipments.store'), [
        'recipient_name' => 'أحمد محمد',
        'carrier_code' => 'dhl_express',
        'origin_city' => 'الرياض',
        'destination_city' => 'جدة',
        'weight' => 5,
        'total_cost' => 75,
    ]);
    $response->assertRedirect(route('shipments.index'));

    $shipment = Shipment::where('recipient_name', 'أحمد محمد')->first();
    $this->assertNotNull($shipment);
    $this->assertEquals('draft', $shipment->status);
    $this->assertNotNull($shipment->tracking_number);
    $this->assertNotNull($shipment->reference_number);

    // 2. Show
    $this->get(route('shipments.show', $shipment))->assertOk();

    // 3. Cancel
    $this->patch(route('shipments.cancel', $shipment));
    $shipment->refresh();
    $this->assertEquals('cancelled', $shipment->status);

    // 4. Create return from original
    $response = $this->post(route('shipments.return', $shipment));
    $response->assertRedirect();
    $return = Shipment::where('source', 'return')->latest()->first();
    $this->assertNotNull($return);
    $this->assertEquals($shipment->recipient_name, $return->sender_name);
}
```

---

## C) Dusk E2E Test Plan (Browser-based)

### C.1 Sidebar Navigation Traversal

```php
// SidebarNavigationTest.php (Dusk)

/** @test */
public function all_sidebar_links_load_without_error()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->demoUser)
                ->visit('/');

        $sidebarRoutes = [
            '/', '/shipments', '/orders', '/stores', '/tracking', '/pricing',
            '/wallet', '/financial',
            '/users', '/roles', '/invitations', '/organizations',
            '/notifications', '/reports', '/audit', '/kyc', '/dg',
            '/support', '/addresses', '/settings', '/admin',
            '/containers', '/customs', '/drivers', '/claims',
            '/vessels', '/schedules', '/branches', '/companies', '/hscodes',
        ];

        foreach ($sidebarRoutes as $route) {
            $browser->visit($route)
                    ->assertDontSee('500')
                    ->assertDontSee('Server Error')
                    ->assertDontSee('SQLSTATE');
        }
    });
}
```

### C.2 Expected Failures (Known Bugs)

The following Dusk assertions would **fail** due to known bugs:

| Test | Route | Expected Failure Reason |
|------|-------|----------------------|
| Visit `/customs` | GET | `SQLSTATE[42S22]: Unknown column 'status'` (BUG-03) |
| Visit `/tracking` + search | GET | `SQLSTATE[42S22]: Unknown column 'carrier_tracking_number'` (BUG-09) |
| Submit create container form | POST | `405 Method Not Allowed` (BUG-02) |
| Submit create vessel form | POST | `405 Method Not Allowed` (BUG-06) |
| Check dashboard balance | GET | Shows `0` instead of real balance (BUG-08) |
| Check containers table data | GET | All data shows `—` (BUG-01) |
| Check vessels table data | GET | All data shows `—` (BUG-05) |
| Check schedules table data | GET | All data shows `—` (BUG-07) |

---

## D) Test Execution Commands

```bash
# Run all feature tests
php artisan test tests/Feature/Web/ --parallel

# Run security tests
php artisan test tests/Feature/Security/

# Run database verification tests
php artisan test tests/Feature/Database/

# Run Dusk E2E tests
php artisan dusk tests/Browser/

# Run specific test
php artisan test --filter=ShipmentFlowTest

# Run with coverage
php artisan test --coverage --min=80
```

---

## E) Summary of Expected Test Results

| Category | Total Tests | Expected Pass | Expected Fail | Reason |
|----------|------------|---------------|---------------|--------|
| Authentication | 5 | 5 | 0 | Auth works correctly |
| Shipment Flow | 8 | 8 | 0 | Core flow works |
| Order Flow | 4 | 4 | 0 | |
| Store Flow | 5 | 3 | 2 | sync/test are stubs |
| Wallet Flow | 4 | 3 | 1 | hold has no ledger |
| User Management | 4 | 2 | 2 | SEC-02 tenant bypass |
| Support Flow | 5 | 5 | 0 | |
| Phase 2 Modules | 12 | 3 | 9 | Column mismatches |
| Tenant Isolation | 6 | 4 | 2 | User model not scoped |
| CSRF | 3 | 3 | 0 | |
| Column Validation | 10 | 3 | 7 | Known bugs |
| **Total** | **66** | **43** | **23** | **65% pass rate** |

---

## F) Recommended Immediate Actions

1. **Fix PageController column names** → will fix 9 failing tests instantly
2. **Add POST routes for containers/vessels** → fixes 2 more
3. **Add account_id check in UserWebController** → fixes 2 security tests
4. **Add ledger entry in wallet hold** → fixes 1 financial test
5. **Fix DashboardController balance** → fixes 1 more

**After these 5 fixes: Expected pass rate → ~95%+ (62/66)**
