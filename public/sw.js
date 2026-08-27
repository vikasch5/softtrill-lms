self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    const data = event.data ? event.data.json() : {};
    
    const title = data.title || 'Notification';
    const options = {
        body: data.message || 'You have a new notification.',
        icon: data.icon_url || '/lms/images/favicon.png', // Fallback icon
        badge: '/lms/images/favicon.png',
        data: {
            url: data.target_url || '/'
        },
        requireInteraction: data.priority === 'high'
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const urlToOpen = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(function (windowClients) {
            // Check if there is already a window/tab open with the target URL
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // If the window is not open, check if there's any window open to focus on, and navigate it
            if (windowClients.length > 0 && 'focus' in windowClients[0]) {
                return windowClients[0].focus().then(client => {
                    if ('navigate' in client) {
                        return client.navigate(urlToOpen);
                    }
                });
            }
            
            // Otherwise, open a new window
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
