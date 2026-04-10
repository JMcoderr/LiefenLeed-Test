<?php

use App\Http\Controllers\AdminController;
use App\Enums\EventCostStatus;
use App\Http\Controllers\Auth\MagicLoginController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\SepaController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventCostsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('request', function () {
    return Inertia::render('Request', [
        'events' => \App\Models\EventCost::with('event')->withStatus(EventCostStatus::Active)->orderBy('event_id')->get()
    ]);
})->middleware(['magic.valid'])->name('requests');

Route::resource('requests', RequestController::class)->middleware(['magic.valid'])->parameters(['requests' => 'requestModel'])->only('store');

// ->prefix('admin') to add /admin before route
Route::name('admin.')->middleware(['magic.valid', 'magic.admin'])->group(function () {
    Route::resource('requests', RequestController::class)->parameters(['requests' => 'requestModel'])->except('store');

    Route::middleware(['magic.super'])->group(function () {
        Route::resource('events', EventController::class)
            ->only(['index', 'store']);
        Route::resource('events.costs', EventCostsController::class)
            ->scoped(['event' => 'id'])
            ->only(['store', 'update', 'destroy'])
            ->parameters(['costs' => 'eventCost']);
        Route::resource('admins', AdminController::class);
        Route::resource('members', MemberController::class);
        Route::name('download.')->prefix('download')->group(function () {
            Route::get('sepa', [SepaController::class, 'sepa'])->name('sepa');
            Route::get('rapport', [RapportController::class , 'rapport'])->name('rapport');
            Route::post('validate-sepa', [SepaController::class, 'validateDebtor'])->name('validate-sepa');
            Route::post('validate-rapport', [RapportController::class, 'validateRapportDate'])->name('validate-rapport');
        });
    });
});

Route::middleware(['magic.guest'])->group(function () {
    Route::get('/login', [MagicLoginController::class, 'login'])->name('login');
    Route::post('/login', [MagicLoginController::class, 'sendLink'])->name('magic-login.send-link')->middleware('throttle:4,1');
    Route::get('/magic-login', [MagicLoginController::class, 'verify'])->name('magic-login.verify')->middleware('throttle:10,1');;
});
Route::get('/logout', [MagicLoginController::class, 'logout'])->middleware(['magic.valid'])->name('logout');


require __DIR__.'/settings.php';
//require __DIR__.'/auth.php';
