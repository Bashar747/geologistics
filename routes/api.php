<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\GeofenceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicTrackingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AuditLogController;

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::get('/track/{trackingNumber}', [PublicTrackingController::class, 'show'])
    ->middleware('throttle:30,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('shipments', ShipmentController::class);
   Route::post('/shipments/{shipment}/assign', [ShipmentController::class, 'assign'])
    ->middleware('role:admin,dispatcher');

    Route::apiResource('vehicles', VehicleController::class)
        ->except(['store', 'update', 'destroy'])->names([
        'index' => 'api.vehicles.index',
        'show' => 'api.vehicles.show',
    ]);;

    Route::post('/vehicles/{vehicle}/location', [VehicleController::class, 'updateLocation']);
    Route::get('/vehicles/{vehicle}/geofences', [GeofenceController::class, 'checkVehicle']);

    Route::get('/drivers/{driver}', [DriverController::class, 'show']);

    Route::get('/shipments/{shipment}/payment', [PaymentController::class, 'show']);
    Route::post('/shipments/{shipment}/payment', [PaymentController::class, 'store']);

    Route::get('/shipments/{shipment}/rating', [RatingController::class, 'show']);
    Route::post('/shipments/{shipment}/rating', [RatingController::class, 'store']);

   Route::get('/geofences', [GeofenceController::class, 'index']);
Route::get('/geofences/check', [GeofenceController::class, 'checkPoint']);
Route::get('/geofences/{geofence}', [GeofenceController::class, 'show']);
      

     Route::get('/notifications', [NotificationController::class, 'index']);
Route::get('/notifications/{notification}', [NotificationController::class, 'show']);


    Route::middleware('role:admin,dispatcher')->group(function () {
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);

        Route::get('/drivers', [DriverController::class, 'index']);
        Route::post('/drivers/assign', [DriverController::class, 'assign']);
        Route::post('/drivers/unassign', [DriverController::class, 'unassign']);
        Route::put('/drivers/{driver}/status', [DriverController::class, 'updateStatus']);

        Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm']);
        Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund']);

        Route::post('/geofences', [GeofenceController::class, 'store']);
        Route::put('/geofences/{geofence}', [GeofenceController::class, 'update']);
        Route::delete('/geofences/{geofence}', [GeofenceController::class, 'destroy']);

        Route::post('/notifications', [NotificationController::class, 'store']);
Route::post('/notifications/{notification}/retry', [NotificationController::class, 'retry']);
    });
    Route::middleware('role:admin')->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
});
});