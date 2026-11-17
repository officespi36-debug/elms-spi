<?php

namespace App\Notifications\Channels;

use App\Models\Notification as ElmsNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ElmsNotificationChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toElmsDatabase')) {
            return;
        }

        $data = $notification->toElmsDatabase($notifiable);
        if (empty($data) || !Schema::hasTable('notifications')) {
            return;
        }

        ElmsNotification::create([
            'title'   => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'target'  => $data['target'] ?? "user:{$notifiable->id}",
        ]);

        Cache::forget("student_notif_payload_{$notifiable->id}");
    }
}
