export function subscribeToNotifications(userId, callbacks = {}) {
    if (!window.Echo || !userId) {
        return null;
    }

    const channel = window.Echo.private(`user.${userId}`);

    channel.listen('.notification.created', (data) => {
        callbacks.onNotification?.(data);
    });

    channel.subscribed(() => {
        callbacks.onConnected?.();
    });

    return {
        leave() {
            window.Echo.leave(`private-user.${userId}`);
        },
    };
}

function initializeNotifications() {
    const userId = window.GeoLogisticsUserId;

    if (!userId || !window.subscribeToNotifications) {
        return;
    }

    const badge = document.getElementById('notification-badge');
    const toast = document.getElementById('notification-toast');
    const toastMessage = document.getElementById('notification-toast-message');

    if (!badge || !toast || !toastMessage) {
        return;
    }

    let notificationCount = 0;
    let toastTimeout;

    window.subscribeToNotifications(userId, {
        onNotification(data) {
            notificationCount++;

            badge.textContent = notificationCount;
            badge.classList.remove('hidden');
            badge.classList.add('flex');

            toastMessage.textContent = data.message ?? 'You have a new notification.';

            toast.classList.remove('hidden');

            clearTimeout(toastTimeout);

            toastTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, 5000);
        },
    });
}

document.addEventListener('DOMContentLoaded', initializeNotifications);

document.addEventListener('livewire:navigated', initializeNotifications);