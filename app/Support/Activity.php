<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Services\PushNotifier;

class Activity
{
    public static function log(string $aksi, ?string $deskripsi = null, ?int $userId = null): void
    {
        ActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 250),
        ]);
    }

    public static function notify(iterable $userIds, string $judul, ?string $pesan, ?string $url): void
    {
        $ids = collect($userIds)->filter()->unique()->reject(fn ($id) => $id == auth()->id())->values();
        foreach ($ids as $id) {
            AppNotification::create(['user_id' => $id, 'judul' => $judul, 'pesan' => $pesan, 'url' => $url]);
        }
        if ($ids->isNotEmpty() && PushNotifier::enabled()) {
            app(PushNotifier::class)->send($ids, $judul, $pesan, $url);
        }
    }
}
