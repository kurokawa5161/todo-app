// Service Workerバージョン（更新時にインクリメント）
const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `todo-app-${CACHE_VERSION}`;

// キャッシュするリソース（存在するファイルのみ）
const urlsToCache = [
  '/favicon.ico',
];

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
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});

// Fetch時：キャッシュファースト戦略
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});

// プッシュ通知受信時
self.addEventListener('push', (event) => {
  if (!event.data) {
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
