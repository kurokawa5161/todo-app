// Service Workerバージョン（更新時にインクリメント）
const CACHE_VERSION = 'v1.0.4';
const CACHE_NAME = `todo-app-${CACHE_VERSION}`;
const RUNTIME_CACHE = `todo-app-runtime-${CACHE_VERSION}`;

// 静的リソース（プリキャッシュ）
const urlsToCache = [
  '/manifest.json',
  '/icons/icon.svg',
];

// キャッシュ対象のファイルパターン
const CACHEABLE_EXTENSIONS = ['.css', '.js', '.png', '.jpg', '.jpeg', '.svg', '.ico'];

// インストール時：キャッシュ作成
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache);
    })
  );
  self.skipWaiting(); // すぐにアクティブ化
});

// アクティベート時：古いキャッシュ削除
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME && name !== RUNTIME_CACHE)
          .map((name) => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});

// Fetch時：キャッシュファースト戦略 + 動的キャッシュ
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // 同一オリジンのGETリクエストのみキャッシュ
  if (event.request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  // キャッシュ対象の拡張子チェック
  const isCacheable = CACHEABLE_EXTENSIONS.some(ext => url.pathname.endsWith(ext));

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      // ネットワークから取得
      return fetch(event.request).then((response) => {
        // 有効なレスポンスかチェック
        if (!response || response.status !== 200 || response.type === 'error') {
          return response;
        }

        // キャッシュ対象の場合、動的にキャッシュに追加
        if (isCacheable) {
          const responseToCache = response.clone();
          caches.open(RUNTIME_CACHE).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }

        return response;
      });
    })
  );
});

// プッシュ通知受信時
self.addEventListener('push', (event) => {
  if (!event.data) {
    console.warn('[Service Worker] Push event has no data');
    return;
  }

  const data = event.data.json();
  const options = {
    body: data.body,
    icon: data.icon || '/favicon.ico',
    badge: data.badge || '/favicon.ico',
    data: data.data,
    tag: data.tag,
    renotify: data.renotify || false,
    requireInteraction: false, // 自動で消える
    vibrate: [200, 100, 200], // バイブレーションパターン（モバイル）
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// 通知クリック時：該当ページを開く
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data?.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      // 既に開いているタブがあればフォーカス
      for (const client of clientList) {
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      // なければ新しいタブで開く
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});
