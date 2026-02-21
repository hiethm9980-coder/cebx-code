<?php

use App\Http\Controllers\Web\B2BAuthWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| B2B Web Routes — بوابة الأعمال (organization)
|--------------------------------------------------------------------------
| تسجيل الدخول GET/POST /b2b/login يعتمد على web.php (AuthWebController)
| وعرض pages.auth.login-b2b (بريد + كلمة مرور فقط، بدون معرّف المنظمة).
| هنا: logout والمسارات المحمية فقط.
*/

Route::prefix('b2b')->name('b2b.')->middleware('portal:b2b')->group(function () {

    Route::post('/logout', [B2BAuthWebController::class, 'logout'])
        ->middleware('auth:web')
        ->name('logout');

    Route::middleware(['auth:web', 'tenant', 'ensureAccountType:organization'])->group(function () {

        Route::get('/dashboard', function () {
            return view('b2b.dashboard');
        })->name('dashboard');

        Route::prefix('shipments')->name('shipments.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
            Route::get('/create', function () {
                return view('b2b.dashboard');
            })->name('create');
            Route::get('/{id}', function ($id) {
                return view('b2b.dashboard', ['shipmentId' => $id]);
            })->name('show');
        });

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
            Route::get('/{id}', function ($id) {
                return view('b2b.dashboard', ['orderId' => $id]);
            })->name('show');
        });

        Route::prefix('stores')->name('stores.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
        });

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
        });

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
        });

        Route::prefix('invitations')->name('invitations.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
        });

        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', function () {
                return view('b2b.dashboard');
            })->name('index');
        });
    });
});
