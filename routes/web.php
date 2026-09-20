<?php

use App\Http\Controllers\AuditReportController;
use App\Http\Controllers\NcrTemplateController;
use App\Livewire\Assistant\QualityAssistant;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard;
use App\Livewire\Documents\DocumentList;
use App\Livewire\Ncr\NcrCreate;
use App\Livewire\Ncr\NcrImport;
use App\Livewire\Ncr\NcrList;
use App\Livewire\Ncr\NcrShow;
use App\Livewire\Reports\AuditReport;
use App\Livewire\Settings\UserManagement;
use App\Livewire\Signals\SignalsDashboard;
use App\Livewire\Suppliers\SupplierList;
use App\Livewire\Suppliers\SupplierShow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/ncrs', NcrList::class)->name('ncrs.index');
    Route::get('/ncrs/create', NcrCreate::class)->name('ncrs.create');
    Route::get('/ncrs/import', NcrImport::class)->name('ncrs.import');
    Route::get('/ncrs/template', NcrTemplateController::class)->name('ncrs.template');
    Route::get('/ncrs/{ncr}', NcrShow::class)->name('ncrs.show');

    Route::get('/suppliers', SupplierList::class)->name('suppliers.index');
    Route::get('/suppliers/{supplier}', SupplierShow::class)->name('suppliers.show');

    Route::get('/documents', DocumentList::class)->name('documents.index');

    Route::get('/assistant', QualityAssistant::class)->name('assistant');

    Route::get('/signals', SignalsDashboard::class)->name('signals.index');

    Route::get('/reports', AuditReport::class)->name('reports');
    Route::get('/reports/download', AuditReportController::class)->name('reports.download');

    Route::get('/settings', UserManagement::class)->name('settings');
});
