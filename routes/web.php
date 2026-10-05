<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});


Route::livewire('/register', 'pages::auth.⚡register');

Route::livewire('/login', 'pages::auth.⚡login')->name('login');

Route::livewire('/track/{trackingNumber}', 'pages::tracking.⚡show')
    ->name('tracking.show');

Route::livewire('/tracking', 'pages::tracking.⚡index')
    ->name('tracking.index');

Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard');
    Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
});

     Route::livewire('/shipments', 'pages::shipments.⚡index')
    ->name('shipments.index');

    Route::livewire('/shipments/create', 'pages::shipments.⚡create')
    ->name('shipments.create');
   
    Route::livewire('/shipments/{shipment}/edit', 'pages::shipments.⚡edit')
    ->name('shipments.edit');

    Route::livewire('/shipments/{shipment}', 'pages::shipments.⚡show')
    ->name('shipments.show');




    Route::livewire('/vehicles', 'pages::vehicles.⚡index')
    ->name('vehicles.index');

Route::livewire('/vehicles/create', 'pages::vehicles.⚡create')
    ->name('vehicles.create');

Route::livewire('/vehicles/{vehicle}', 'pages::vehicles.⚡show')
    ->name('vehicles.show');

Route::livewire('/vehicles/{vehicle}/edit', 'pages::vehicles.⚡edit')
    ->name('vehicles.edit');
  
    Route::livewire('/payments', 'pages::payments.⚡index')
    ->name('payments.index');

    Route::livewire('/users', 'pages::users.⚡index')
        ->middleware('role:admin')
        ->name('users.index');
        
        Route::livewire('/settings', 'pages::settings.⚡index')
    ->middleware('role:admin')
    ->name('settings.index');

    Route::livewire('/drivers', 'pages::drivers.⚡index')
        ->middleware('role:admin,dispatcher')
        ->name('drivers.index');

    Route::livewire('/drivers/{driver}', 'pages::drivers.⚡show')
        ->name('drivers.show');

    Route::livewire('/geofences', 'pages::geofences.⚡index')
        ->name('geofences.index');

    Route::livewire('/geofences/create', 'pages::geofences.⚡create')
        ->middleware('role:admin,dispatcher')
        ->name('geofences.create');

    Route::livewire('/geofences/{geofence}', 'pages::geofences.⚡show')
        ->name('geofences.show');

    Route::livewire('/geofences/{geofence}/edit', 'pages::geofences.⚡edit')
        ->middleware('role:admin,dispatcher')
        ->name('geofences.edit');

    Route::livewire('/notifications', 'pages::notifications.⚡index')
        ->name('notifications.index');

    Route::livewire('/audit-logs', 'pages::audit-logs.⚡index')
        ->middleware('role:admin')
        ->name('audit-logs.index');


        Route::livewire('/profile', 'pages::profile.⚡index')
    ->name('profile');

    Route::livewire('/reports', 'pages::reports.⚡index')
    ->middleware('role:admin,dispatcher')
    ->name('reports.index');
  
});