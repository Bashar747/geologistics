<?php

namespace App\Providers;

use App\Models\DriverVehicleAssignment;
use App\Models\Payment;
use App\Models\Shipment;
use App\Observers\DriverVehicleAssignmentObserver;
use App\Observers\PaymentObserver;
use App\Observers\ShipmentObserver;
use Illuminate\Support\ServiceProvider;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Shipment::observe(ShipmentObserver::class);
        Payment::observe(PaymentObserver::class);
        DriverVehicleAssignment::observe(DriverVehicleAssignmentObserver::class);

        Scramble::configure()
    ->withDocumentTransformers(function (OpenApi $openApi) {
        $openApi->secure(
            SecurityScheme::http('bearer')
        );
    });
    }
}