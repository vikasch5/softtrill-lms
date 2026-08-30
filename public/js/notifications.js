const NotificationSystem = {
    vapidPublicKey: null, // Should be injected into the window object via Blade
    
    init: function() {
        // Grab the key injected from blade
        if (window.NotificationSystem && window.NotificationSystem.vapidPublicKey) {
            this.vapidPublicKey = window.NotificationSystem.vapidPublicKey;
        }

        if (!('Notification' in window)) {
            console.warn('This browser does not support desktop notification');
            return;
        }

        // Handle bell clicks and polling
        this.initBell();

        if (this.vapidPublicKey && 'serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });

            // Listen for messages from the service worker (e.g., when a push notification arrives)
            navigator.serviceWorker.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'NEW_PUSH_NOTIFICATION') {
                    // Play the custom sound for all new pushes
                    NotificationSystem.playAlertSound();
                    
                    // Instantly refresh the UI to show the new notification without waiting for the 60s poll
                    NotificationSystem.fetchNotifications();
                }
            });
            
            // Automatically prompt for push notifications on first visit if not disabled
            // Browsers like Firefox and Edge block prompts that don't originate from a user gesture.
            // We bind it to the very first click on the document to ensure the prompt is allowed.
            if (Notification.permission === 'default' && localStorage.getItem('push_prompt_disabled') !== 'true') {
                const _this = this;
                
                const promptPushPermission = function() {
                    // Remove listener so it only fires once
                    document.removeEventListener('click', promptPushPermission);
                    
                    _this.subscribeUser().then(() => {
                        // Success, they allowed it
                        $('#pref_browser_notifications').prop('checked', true);
                    }).catch((err) => {
                        // They blocked or ignored it
                        console.log('User declined or blocked auto-prompt');
                        localStorage.setItem('push_prompt_disabled', 'true');
                    });
                };
                
                // Wait for the user's first interaction with the page
                document.addEventListener('click', promptPushPermission);
            }
        }
    },

    initBell: function() {
        const _this = this;
        
        // Fetch initially
        _this.fetchNotifications();

        // Poll every 60 seconds
        setInterval(function() {
            _this.fetchNotifications();
        }, 60000);

        // Bind Read All
        $(document).on('click', '.mark-all-read', function(e) {
            e.preventDefault();
            _this.markAllAsRead();
        });

        // Bind individual read
        $(document).on('click', '.mark-read-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = $(this).data('id');
            _this.markAsRead(id);
        });
    },

    lastSeenNotificationId: null,

    fetchNotifications: function() {
        const _this = this;
        $.ajax({
            url: '/notifications/fetch',
            type: 'GET',
            success: function(response) {
                if (response.unread_count !== undefined) {
                    $('.notification-count-badge').text(response.unread_count);
                    if(response.unread_count > 0) {
                        $('.notification-count-badge').show();
                        $('.mark-all-read-container').show();
                    } else {
                        $('.notification-count-badge').hide();
                        $('.mark-all-read-container').hide();
                    }
                }

                if (response.notifications) {
                    let html = '';
                    let hasNewHighPriority = false;

                    if (response.notifications.length === 0) {
                        html = `<span class="text-sm text-secondary-light flex-shrink-0 px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between">No notifications found</span>`;
                    } else {
                        response.notifications.forEach(function(notif) {
                            if (_this.lastSeenNotificationId !== null && notif.id !== _this.lastSeenNotificationId) {
                                // Check if this is a new high priority notification we haven't seen in this session
                                if (notif.priority === 'high' && (!_this.lastSeenNotificationIds || !_this.lastSeenNotificationIds.includes(notif.id))) {
                                    hasNewHighPriority = true;
                                }
                            }
                            
                            // Keep track of seen IDs to avoid playing sound on every poll
                            if (!_this.lastSeenNotificationIds) _this.lastSeenNotificationIds = [];
                            if (!_this.lastSeenNotificationIds.includes(notif.id)) {
                                _this.lastSeenNotificationIds.push(notif.id);
                            }

                            html += `
                                <a href="${notif.url}" class="px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between">
                                    <div class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                        <span class="w-44-px h-44-px bg-success-subtle text-success-main rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                                            <iconify-icon icon="${notif.icon}" class="icon text-xxl"></iconify-icon>
                                        </span>
                                        <div>
                                            <h6 class="text-md fw-semibold mb-1">${notif.title}</h6>
                                            <p class="mb-0 text-sm text-secondary-light">${notif.message}</p>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="text-sm text-secondary-light flex-shrink-0 mb-2">${notif.time_ago}</span>
                                        <button type="button" class="btn btn-sm btn-light mark-read-btn" data-id="${notif.id}">
                                            <iconify-icon icon="iconoir:check"></iconify-icon>
                                        </button>
                                    </div>
                                </a>
                            `;
                        });
                        
                    }
                    
                    $('#notification-list-container').html(html);

                    if (response.notifications.length > 0) {
                        _this.lastSeenNotificationId = response.notifications[0].id;
                    }

                    if (hasNewHighPriority) {
                        _this.playAlertSound();
                    }
                }
            }
        });
    },

    playAlertSound: function() {
        try {
            // console.log("Playing alert sound...");
            const audio = new Audio('/audio/notify.mp3');
            const playPromise = audio.play();
            
            if (playPromise !== undefined) {
                playPromise.catch(function(error) {
                    // console.log('Autoplay prevented by browser. User must interact with the page first.', error);
                    /* 
                    if (typeof notify_it === 'function') {
                        notify_it('warning', 'Browser blocked notification sound. Please click anywhere on the page to enable sounds.');
                    }
                    */
                });
            }
        } catch(e) {
            // console.error('Error playing sound', e);
        }
    },

    markAsRead: function(id) {
        const _this = this;
        $.ajax({
            url: `/notifications/${id}/read`,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                _this.fetchNotifications();
            }
        });
    },

    markAllAsRead: function() {
        const _this = this;
        $.ajax({
            url: '/notifications/read-all',
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                _this.fetchNotifications();
            }
        });
    },

    urlBase64ToUint8Array: function(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    },

    subscribeUser: function() {
        const _this = this;
        if (!('serviceWorker' in navigator) || !this.vapidPublicKey) {
            return Promise.reject('Service Worker or VAPID key missing.');
        }

        return navigator.serviceWorker.ready.then(function(registration) {
            const subscribeOptions = {
                userVisibleOnly: true,
                applicationServerKey: _this.urlBase64ToUint8Array(_this.vapidPublicKey)
            };

            return registration.pushManager.subscribe(subscribeOptions);
        }).then(function(pushSubscription) {
            // Send the subscription details to the server
            return $.ajax({
                url: '/notifications/push/subscribe',
                type: 'POST',
                data: Object.assign({}, pushSubscription.toJSON(), {
                    _token: $('meta[name="csrf-token"]').attr('content')
                })
            });
        });
    },
    
    unsubscribeUser: function() {
        return navigator.serviceWorker.ready.then(function(registration) {
            return registration.pushManager.getSubscription();
        }).then(function(subscription) {
            if (subscription) {
                // Send unsubscribe request to backend before unsubscribing in browser
                $.ajax({
                    url: '/notifications/push/unsubscribe',
                    type: 'POST',
                    data: {
                        endpoint: subscription.endpoint,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    }
                });
                return subscription.unsubscribe();
            }
        });
    },

    initSettingsModal: function() {
        const _this = this;

        // Check if browser push is subscribed to set the toggle state initially
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(function(registration) {
                registration.pushManager.getSubscription().then(function(subscription) {
                    if (subscription) {
                        $('#pref_browser_notifications').prop('checked', true);
                    }
                });
            });
        }

        // Handle Browser Push toggle
        $('#pref_browser_notifications').on('change', function() {
            const $toggle = $(this);
            $toggle.prop('disabled', true);

            if ($toggle.is(':checked')) {
                notify_it('info', 'Enabling push notifications, please wait...');
                
                _this.subscribeUser().then(() => {
                    notify_it('success', 'Browser push notifications enabled!');
                    $toggle.prop('disabled', false);
                    localStorage.removeItem('push_prompt_disabled');
                }).catch((err) => {
                    console.error(err);
                    notify_it('error', 'Failed to enable push notifications. Check browser permissions.');
                    $toggle.prop('checked', false).prop('disabled', false);
                    localStorage.setItem('push_prompt_disabled', 'true');
                });
            } else {
                notify_it('info', 'Disabling push notifications...');
                
                _this.unsubscribeUser().then(() => {
                    notify_it('success', 'Browser push notifications disabled.');
                    $toggle.prop('disabled', false);
                    localStorage.setItem('push_prompt_disabled', 'true');
                }).catch((err) => {
                    console.error(err);
                    $toggle.prop('disabled', false);
                });
            }
        });

        // Save Preferences
        $('#save-notification-prefs-btn').on('click', function() {
            const btn = $(this);
            btn.text('Saving...').prop('disabled', true);
            
            let prefs = {};
            $('.pref-toggle').each(function() {
                prefs[$(this).data('key')] = $(this).is(':checked');
            });

            $.ajax({
                url: '/notifications/preferences',
                type: 'POST',
                data: {
                    preferences: prefs,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    btn.text('Saved!').removeClass('btn-primary').addClass('btn-success');
                    setTimeout(() => {
                        btn.text('Save Preferences').removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
                        $('#notificationSettingsModal').modal('hide');
                    }, 1000);
                },
                error: function() {
                    notify_it('error', 'Failed to save preferences.');
                    btn.text('Save Preferences').prop('disabled', false);
                }
            });
        });
    }
};

$(document).ready(function() {
    NotificationSystem.init();
    NotificationSystem.initSettingsModal();
});
