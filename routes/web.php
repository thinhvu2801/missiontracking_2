<?php

use App\Http\Controllers\Agency\AgencyController;
use App\Http\Controllers\Agency\AgencyGroupController;
use App\Http\Controllers\Indicator\IndicatorController;
use App\Http\Controllers\Indicator\IndicatorGroupController;
use App\Http\Controllers\Resolution\ResolutionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DelayReasonController;
use App\Http\Controllers\Indicator\IndicatorReportController;
use App\Http\Controllers\Mission\MissionController;
use App\Http\Controllers\Mission\MissionGroupController;
use App\Http\Controllers\Mission\MissionReportController;
use App\Http\Controllers\MissionDashboardController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;




Route::get('/', function () {
    return redirect()->route('auth.login');
});

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::prefix('users')->middleware(['auth', 'active'])->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:users.index');
    Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('permission:users.create');
    Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.destroy');
    //change password
    Route::get('/change-password', [UserController::class, 'showChangePasswordForm'])
        ->name('password.form');
    Route::post('/change-password', [UserController::class, 'changePassword'])
        ->name('password.update');
});


Route::resource('agency-groups', AgencyGroupController::class)->middleware('auth', 'active');
Route::resource('resolutions', ResolutionController::class)->middleware('auth', 'active');
Route::resource('indicator-groups', IndicatorGroupController::class)->middleware('auth', 'active');
Route::prefix('indicators')->middleware('auth', 'active')->name('indicators.')->group(function () {
    Route::get('/index/{resolution}', [IndicatorController::class, 'index'])->name('index')->middleware('permission:indicators.index');
    Route::get('/create', [IndicatorController::class, 'create'])->name('create')->middleware('permission:indicators.create');
    Route::post('/', [IndicatorController::class, 'store'])->name('store')->middleware('permission:indicators.create');
    Route::get('/{indicator}/edit', [IndicatorController::class, 'edit'])->name('edit')->middleware('permission:indicators.edit');
    Route::put('/{indicator}', [IndicatorController::class, 'update'])->name('update')->middleware('permission:indicators.edit');
    Route::delete('/{indicator}', [IndicatorController::class, 'destroy'])->name('destroy')->middleware('permission:indicators.destroy');
    Route::get('/{indicator}/report', [IndicatorReportController::class, 'create'])->name('report.create')->middleware('permission:indicators.report');
    Route::post('/{indicator}/report', [IndicatorReportController::class, 'store'])->name('report.store')->middleware('permission:indicators.report');
    Route::get('/dashboard', [IndicatorReportController::class, 'dashboard'])->name('report.dashboard');
    Route::get('/{indicator}/details', [IndicatorReportController::class, 'details'])->name('details')->middleware('permission:indicators.details');
});
Route::resource('mission-groups', MissionGroupController::class)->middleware('auth', 'active');
Route::prefix('missions')->middleware('auth')->name('missions.')->group(function () {
    Route::get('/index/{resolution}', [MissionController::class, 'index'])->name('index')->middleware('permission:missions.index');
    Route::get('/create', [MissionController::class, 'create'])->name('create')->middleware('permission:missions.create');
    Route::post('/', [MissionController::class, 'store'])->name('store')->middleware('permission:missions.create');
    Route::get('/{mission}/edit', [MissionController::class, 'edit'])->name('edit')->middleware('permission:missions.edit');
    Route::put('/{mission}', [MissionController::class, 'update'])->name('update')->middleware('permission:missions.edit');
    Route::delete('/{mission}', [MissionController::class, 'destroy'])->name('destroy')->middleware('permission:missions.destroy');
    Route::get('/{mission}/report', [MissionReportController::class, 'create'])->name('report.create')->middleware('permission:missions.report');
    Route::post('/{mission}/report', [MissionReportController::class, 'store'])->name('report.store')->middleware('permission:missions.report');
    Route::get('/dashboard', [MissionReportController::class, 'dashboard'])->name('report.dashboard');
    Route::get('/{mission}/details', [MissionReportController::class, 'details'])->name('details')->middleware('permission:missions.details');
});
Route::middleware(['auth', 'role:admin|supervisor', 'active'])
    ->resource('delay-reasons', DelayReasonController::class);
// Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index')->middleware('auth', 'active');

// Route::middleware('auth', 'active')->prefix('dashboard')->group(function () {
//     Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

//     // API cho Vue IOC
//     Route::get('/summary', [DashboardController::class, 'summary']);
//     Route::get('/pie', [DashboardController::class, 'pie']);
//     Route::get('/trend', [DashboardController::class, 'trend']);
//     Route::get('/slow-indicators', [DashboardController::class, 'slowIndicators']);
//     Route::get('/status-by-agency', [DashboardController::class, 'statusByAgency']);
// });

// Route::get('/dashboard/missions', [MissionDashboardController::class, 'index'])->middleware('auth', 'active');

// Route::prefix('dashboard/missions')->middleware('auth', 'active')->group(function () {
//     Route::get('/periods', [MissionDashboardController::class, 'periods']);
//     Route::get('/summary-by-period', [MissionDashboardController::class, 'summaryByPeriod']);
//     Route::get('/status-pie', [MissionDashboardController::class, 'statusPie']);
//     Route::get('/top-agencies-bar', [MissionDashboardController::class, 'topAgenciesBar']);
//     Route::get('/details', [MissionDashboardController::class, 'missionDetails']);
// });
use App\Http\Controllers\Dashboard\OverviewDashboardController;

Route::get('/dashboard/overview', [OverviewDashboardController::class, 'index'])
    ->name('dashboard.overview');
Route::get('/agencies/manage', [AgencyController::class, 'manage'])->name('agencies.manage')->middleware('auth', 'active');
Route::resource('agencies', AgencyController::class)->except(['show'])->middleware('auth', 'active');
Route::get('/dashboard/overview/filters', [OverviewDashboardController::class, 'filters'])
    ->name('dashboard.overview.filters');
Route::get('/dashboard/overview/data', [OverviewDashboardController::class, 'data'])
    ->name('dashboard.overview.data');

Route::middleware(['auth', 'active'])->prefix('dashboard')->group(function () {
    Route::get('/overview', [OverviewDashboardController::class, 'index'])->name('dashboard.overview');
    Route::get('/overview/filters', [OverviewDashboardController::class, 'filters']);
    Route::get('/overview/data', [OverviewDashboardController::class, 'data'])->name('dashboard.overview.data');

});