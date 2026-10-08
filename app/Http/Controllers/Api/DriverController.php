<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverVehicleAssignment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\DriverVehicleAssignmentResource;
use App\Http\Resources\UserResource;

class DriverController extends Controller
{
   
  public function index()
{
    $drivers = User::where('role', 'driver')
        ->with(['driverProfile', 'vehicleAssignments' => function ($q) {
            $q->where('is_active', true)->with('vehicle');
        }])
        ->paginate(15);

    return UserResource::collection($drivers);
}

 
   public function show(Request $request, User $driver)
{
    if ($driver->role !== 'driver') {
        return response()->json([
            'message' => 'User is not a driver',
        ], 404);
    }

    $user = $request->user();

    if ($user->role === 'driver' && $user->id !== $driver->id) {
        return response()->json([
            'message' => 'You are not authorized to view this driver',
        ], 403);
    }

    if ($user->role === 'customer') {
        $hasShipmentWithDriver = $driver->vehicleAssignments()
            ->where('is_active', true)
            ->whereHas('vehicle.shipments', function ($query) use ($user) {
                $query->where('customer_id', $user->id);
            })
            ->exists();

        if (! $hasShipmentWithDriver) {
            return response()->json([
                'message' => 'You are not authorized to view this driver',
            ], 403);
        }
    }

    $driver->load([
        'driverProfile',
        'vehicleAssignments' => function ($q) {
            $q->where('is_active', true)->with('vehicle');
        },
    ]);

    return new UserResource($driver);
}

    // تخصيص سائق لمركبة (أدمن/موزّع فقط)
    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => ['required', 'exists:users,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $driver = User::find($request->driver_id);
        $vehicle = Vehicle::find($request->vehicle_id);

        if ($driver->role !== 'driver') {
            return response()->json(['message' => 'Selected user is not a driver'], 422);
        }

        $assignment = DB::transaction(function () use ($driver, $vehicle) {
            // نلغي أي تخصيص نشط سابق لنفس السائق
            DriverVehicleAssignment::where('driver_id', $driver->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'unassigned_at' => now()]);

            // نلغي أي تخصيص نشط سابق لنفس المركبة (لسائق تاني)
            DriverVehicleAssignment::where('vehicle_id', $vehicle->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'unassigned_at' => now()]);

            return DriverVehicleAssignment::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'assigned_at' => now(),
                'is_active' => true,
            ]);
        });

       return response()->json([
    'message' => 'Driver assigned to vehicle successfully',
    'assignment' => new DriverVehicleAssignmentResource($assignment->load(['driver', 'vehicle'])),
], 201);
    }

    // إلغاء تخصيص سائق حالي (أدمن/موزّع فقط)
    public function unassign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => ['required', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = DriverVehicleAssignment::where('driver_id', $request->driver_id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'unassigned_at' => now()]);

        if (! $updated) {
            return response()->json(['message' => 'No active assignment found for this driver'], 404);
        }

        return response()->json(['message' => 'Driver unassigned successfully']);
    }

    // تحديث حالة السائق (available / on_duty / suspended) - أدمن/موزّع فقط
    public function updateStatus(Request $request, User $driver)
    {
        if ($driver->role !== 'driver' || ! $driver->driverProfile) {
            return response()->json(['message' => 'User is not a driver'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:available,on_duty,suspended'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $driver->driverProfile->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Driver status updated',
            'driver_profile' => $driver->driverProfile->fresh(),
        ]);
    }
}