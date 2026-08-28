self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

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

    options.silent = true; // Disable the default OS notification sound

    if (data.actions && Array.isArray(data.actions)) {
        options.actions = data.actions;
    }

    event.waitUntil(
        self.registration.showNotification(title, options).then(() => {
            // Send a message to all open tabs to play the custom sound and refresh UI
            return clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
                windowClients.forEach(client => {
                    client.postMessage({
                        type: 'NEW_PUSH_NOTIFICATION',
                        priority: data.priority
                    });
                });
            });
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    // Determine the URL to open based on the action clicked, or default to the notification's target URL
    let urlToOpen = event.notification.data.url;
    
    // If the notification payload included custom action URLs mapping, they would be inside data too, 
    // but typically we just use the main URL for the 'view' action.
    if (event.action) {
        if (event.notification.data[event.action + '_url']) {
            urlToOpen = event.notification.data[event.action + '_url'];
        }
    }

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
