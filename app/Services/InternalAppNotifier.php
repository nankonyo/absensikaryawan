<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InternalAppNotifier
{
    public static function adminIds(): array
    {
        return DB::table('users')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->where('is_admin', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function notify(int $userId, string $title, string $message = '', string $type = 'info', ?string $link = null): void
    {
        if ($userId <= 0) {
            return;
        }
        DB::table('internal_notifications')->insert([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function notifyAdmins(string $title, string $message = '', string $type = 'info', ?string $link = null): void
    {
        foreach (self::adminIds() as $id) {
            self::notify($id, $title, $message, $type, $link);
        }
    }
}
