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
 
});