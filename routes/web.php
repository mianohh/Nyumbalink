<?php

use App\Http\Controllers\PaymentReceiptController;
use App\Livewire\Dashboard;
use App\Livewire\Houses\Create as HouseCreate;
use App\Livewire\Houses\Edit as HouseEdit;
use App\Livewire\Houses\Index as HouseIndex;
use App\Livewire\Houses\Show as HouseShow;
use App\Livewire\Payments\Create as PaymentCreate;
use App\Livewire\Payments\Index as PaymentIndex;
use App\Livewire\Payments\Show as PaymentShow;
use App\Livewire\Staff\Create as StaffCreate;
use App\Livewire\Staff\Index as StaffIndex;
use App\Livewire\Tenants\Create as TenantCreate;
use App\Livewire\Tenants\Edit as TenantEdit;
use App\Livewire\Tenants\Index as TenantIndex;
use App\Livewire\Tenants\Show as TenantShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::prefix('houses')->name('houses.')->group(function () {
        Route::get('/', HouseIndex::class)->name('index');
        Route::get('create', HouseCreate::class)->name('create');
        Route::get('{house}', HouseShow::class)->name('show');
        Route::get('{house}/edit', HouseEdit::class)->name('edit');
    });

    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/', TenantIndex::class)->name('index');
        Route::get('create', TenantCreate::class)->name('create');
        Route::get('{tenant}', TenantShow::class)->name('show');
        Route::get('{tenant}/edit', TenantEdit::class)->name('edit');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', PaymentIndex::class)->name('index');
        Route::get('create', PaymentCreate::class)->name('create');
        Route::get('{payment}', PaymentShow::class)->name('show');
        Route::get('{payment}/receipt', PaymentReceiptController::class)->name('receipt');
    });

    Route::prefix('staff')->name('staff.')->middleware('admin')->group(function () {
        Route::get('/', StaffIndex::class)->name('index');
        Route::get('create', StaffCreate::class)->name('create');
    });
});

require __DIR__.'/settings.php';
