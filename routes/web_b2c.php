<?php

use App\Http\Controllers\Web\B2CAuthWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| B2C Web Routes — بوابة الأفراد (individual)
|--------------------------------------------------------------------------
| تسجيل الدخول GET/POST /b2c/login يعتمد على web.php (AuthWebController)
| وعرض pages.auth.login-b2c (نفس تصميم بوابة الأعمال: لوحتان).
| هنا: logout والمسارات المحمية فقط.
*/

Route::prefix('b2c')->name('b2c.')->middleware('portal:b2c')->group(function () {

    Route::post('/logout', [B2CAuthWebController::class, 'logout'])
        ->middleware('auth:web')
        ->name('logout');

    // مسارات محمية
    Route::middleware(['auth:web', 'tenant', 'ensureAccountType:individual'])->group(function () {

        Route::get('/dashboard', function () {
            return view('b2c.dashboard');
        })->name('dashboard');

        Route::prefix('shipments')->name('shipments.')->group(function () {
            Route::get('/', function () {
                return view('b2c.dashboard');
            })->name('index');
            Route::get('/create', function () {
                return view('b2c.dashboard');
            })->name('create');
            Route::get('/{id}', function ($id) {
                return view('b2c.dashboard', ['shipmentId' => $id]);
            })->name('show');
        });

        Route::prefix('tracking')->name('tracking.')->group(function () {
            Route::get('/', function () {
                return view('b2c.dashboard');
            })->name('index');
            Route::get('/{trackingNumber}', function ($trackingNumber) {
                return view('b2c.dashboard', ['trackingNumber' => $trackingNumber]);
            })->name('show');
        });

        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', function () {
                return view('b2c.dashboard');
            })->name('index');
        });

        Route::prefix('addresses')->name('addresses.')->group(function () {
            Route::get('/', function () {
                return view('b2c.dashboard');
            })->name('index');
        });

        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', function () {
                return view('b2c.dashboard');
            })->name('index');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', function () {
                return view('b2c.dashboard');
            })->name('index');
        });
    });
});
