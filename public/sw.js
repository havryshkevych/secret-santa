// Service Worker for caching audio files
const CACHE_NAME = 'secret-santa-music-v1';
const MUSIC_FILES = [
    '/music/christmas.mp3'
];

// Install event - cache the music file
self.addEventListener('install', function(event) {
    console.log('Service Worker: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Service Worker: Caching music files...');
                return cache.addAll(MUSIC_FILES);
            })
            .then(function() {
                console.log('Service Worker: Music files cached successfully');
                return self.skipWaiting();
            })
            .catch(function(error) {
                console.error('Service Worker: Error caching files', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    console.log('Service Worker: Activating...');
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Deleting old cache', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(function() {
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache if available
self.addEventListener('fetch', function(event) {
    // Only handle requests for music files
    if (event.request.url.includes('/music/')) {
        event.respondWith(
            caches.match(event.request)
                .then(function(response) {
                    // Return cached version if available
                    if (response) {
                        console.log('Service Worker: Serving from cache', event.request.url);
                        return response;
                    }

                    // Otherwise fetch from network and cache it
                    console.log('Service Worker: Fetching from network', event.request.url);
                    return fetch(event.request).then(function(response) {
                        // Check if valid response
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response
                        const responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                                console.log('Service Worker: Cached new music file', event.request.url);
                            });

                        return response;
                    });
                })
        );
    }
});
