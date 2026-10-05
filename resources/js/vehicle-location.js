export function subscribeToVehicleLocation(vehicleId, callbacks = {}) {
    if (!window.Echo) {
        console.error('Echo not initialized');
        return null;
    }

    const channel = window.Echo.private(`vehicle.${vehicleId}`);

  channel.listen('.location.updated', (e) => {
    if (callbacks.onUpdate) {
        callbacks.onUpdate(e);
    }
});

    channel.subscribed(() => {
        if (callbacks.onConnected) {
            callbacks.onConnected();
        }
    });

    channel.error((error) => {
        if (callbacks.onError) {
            callbacks.onError(error);
        }
    });

    return {
        channel,
        leave() {
            window.Echo.leave(`vehicle.${vehicleId}`);
        },
    };
}