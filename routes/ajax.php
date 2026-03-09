<?php

use App\Http\Controllers\Agency\AgencyController;
use App\Http\Controllers\Indicator\IndicatorController;
use App\Http\Controllers\Mission\MissionController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('indicators/parents-by-group',[IndicatorController::class, 'getParentsByGroup'])
    ->name('indicators.parents-by-group')
    ->middleware('permission:indicators.create');
Route::get('missions/parents-by-group', [MissionController::class, 'getParentsByGroup'])
    ->name('missions.parents-by-group')
    ->middleware('permission:missions.create');
Route::get('/agencies/by-parent/{parent}', [AgencyController::class, 'byParent'])
    ->name('agencies.by-parent')
    ->middleware('auth', 'active');
Route::get('/ajax/agencies/by-group/{group}', [AgencyController::class, 'byGroup'])
    ->name('ajax.agencies.byGroup')
    ->middleware('auth', 'active');
