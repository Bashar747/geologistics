<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Shipment::with(['items', 'customer']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === 'driver') {
            $vehicleId = $user->vehicleAssignments()
                ->where('is_active', true)
                ->value('vehicle_id');

            $query->where('vehicle_id', $vehicleId);
        }

        $shipments = $query->latest()->paginate(15);

        return ShipmentResource::collection($shipments);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.dimensions' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.fragile' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
          if (! $request->user()->can('create', Shipment::class)) {
    return response()->json([
        'message' => 'You are not authorized to create shipments',
    ], 403);
}
        $shipment = DB::transaction(function () use ($request) {
            $shipment = Shipment::create([
                'tracking_number' => 'GL-' . strtoupper(Str::random(10)),
                'customer_id' => $request->user()->id,
                'pickup_location' => Point::makeGeodetic($request->pickup_lat, $request->pickup_lng),
                'dropoff_location' => Point::makeGeodetic($request->dropoff_lat, $request->dropoff_lng),
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                $shipment->items()->create($item);
            }

            ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'status' => 'pending',
                'changed_by' => $request->user()->id,
                'note' => 'Shipment created',
            ]);

            return $shipment;
        });

        return response()->json([
            'message' => 'Shipment created successfully',
            'shipment' => new ShipmentResource($shipment->load('items')),
        ], 201);
    }

    public function show(Request $request, Shipment $shipment)
    {
        $this->authorizeAccess($request, $shipment);

        $shipment->load(['items', 'statusHistory.changedBy', 'customer', 'vehicle.currentAssignment.driver', 'payment', 'rating.ratedBy']);

        return new ShipmentResource($shipment);
    }

  public function update(Request $request, Shipment $shipment)
{
    $user = $request->user();

if (! $request->user()->can('update', $shipment)) {
    return response()->json([
        'message' => 'You are not authorized to update this shipment\'s status',
    ], 403);
}
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:pending,assigned,picked_up,in_transit,delivered,cancelled'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($user->role === 'driver') {
            $vehicleIds = $user->vehicleAssignments()->where('is_active', true)->pluck('vehicle_id');
            if (! in_array($shipment->vehicle_id, $vehicleIds->toArray())) {
                return response()->json(['message' => 'You are not authorized to update this shipment'], 403);
            }

            $driverTransitions = [
                'assigned' => 'picked_up',
                'picked_up' => 'in_transit',
                'in_transit' => 'delivered',
            ];

            if (!isset($driverTransitions[$shipment->status]) || $driverTransitions[$shipment->status] !== $request->status) {
                return response()->json(['message' => 'Invalid status transition for driver'], 422);
            }
        }

        DB::transaction(function () use ($request, $shipment, $user) {
            $shipment->update(['status' => $request->status]);

            ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'status' => $request->status,
                'changed_by' => $user->id,
                'note' => $request->note ?? "Status changed to {$request->status}",
            ]);
        });

        return response()->json([
            'message' => 'Shipment status updated',
            'shipment' => new ShipmentResource($shipment->fresh(['items', 'statusHistory'])),
        ]);
    }
    public function assign(Request $request, Shipment $shipment)
{
    $validator = Validator::make($request->all(), [
        'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }
    if (! $request->user()->can('update', $shipment)) {
    return response()->json([
        'message' => 'You are not authorized to assign this shipment',
    ], 403);
}

    if (! in_array($shipment->status, ['pending', 'assigned'], true)) {
        return response()->json([
            'message' => 'Only pending or already assigned shipments can be assigned.',
        ], 422);
    }

    $user = $request->user();

    DB::transaction(function () use ($request, $shipment, $user) {
        $shipment->update([
            'vehicle_id' => $request->integer('vehicle_id'),
            'status' => 'assigned',
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'assigned',
            'changed_by' => $user->id,
            'note' => 'Vehicle assigned to shipment',
        ]);
    });

    return response()->json([
        'message' => 'Vehicle assigned to shipment successfully',
        'shipment' => new ShipmentResource(
            $shipment->fresh([
                'items',
                'customer',
                'vehicle.currentAssignment.driver',
                'statusHistory',
            ])
        ),
    ]);
}
   public function destroy(Request $request, Shipment $shipment)
{
    $user = $request->user();

    if (! $user->can('delete', $shipment)) {
        return response()->json([
            'message' => 'You are not authorized to delete this shipment',
        ], 403);
    }

    if ($shipment->status !== 'pending') {
        return response()->json([
            'message' => 'Cannot delete a shipment that has already been assigned or progressed',
        ], 422);
    }

    $shipment->delete();

    return response()->json([
        'message' => 'Shipment deleted',
    ]);
}
    private function authorizeAccess(Request $request, Shipment $shipment): void
    {
        $user = $request->user();

        if ($user->role === 'customer' && $shipment->customer_id !== $user->id) {
            abort(403, 'You are not authorized to access this shipment');
        }

        if ($user->role === 'driver') {
            $activeVehicleId = $user->vehicleAssignments()
                ->where('is_active', true)
                ->value('vehicle_id');

            if ($shipment->vehicle_id !== $activeVehicleId) {
                abort(403, 'You are not authorized to access this shipment');
            }
        }
    }
}