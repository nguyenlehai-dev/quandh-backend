<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\User;

class UserNotificationService
{
    public function getPreferences(User $user): array
    {
        $preference = $user->userPreference;

        return [
            'notify_email' => $preference?->notify_email ?? true,
            'notify_system' => $preference?->notify_system ?? true,
            'notify_meeting_reminder' => $preference?->notify_meeting_reminder ?? true,
            'notify_vote' => $preference?->notify_vote ?? true,
            'notify_document' => $preference?->notify_document ?? false,
        ];
    }

    public function updatePreferences(User $user, array $data): void
    {
        $user->userPreference()->updateOrCreate(
            ['user_id' => $user->id],
            $data,
        );
    }

    public function listNotifications(User $user): array
    {
        $notifications = $user->notifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Thông báo',
                'subtitle' => $notification->data['subtitle'] ?? $notification->data['body'] ?? '',
                'icon' => $notification->data['icon'] ?? 'tabler-bell',
                'color' => $notification->data['color'] ?? 'primary',
                'time' => $notification->created_at->diffForHumans(),
                'isSeen' => $notification->read_at !== null,
            ])
            ->values();

        return [
            'data' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }

    public function markRead(User $user, array $ids): void
    {
        $user->unreadNotifications()
            ->whereIn('id', $ids)
            ->update(['read_at' => now()]);
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function markUnread(User $user, array $ids): void
    {
        $user->notifications()
            ->whereIn('id', $ids)
            ->update(['read_at' => null]);
    }

    public function destroy(User $user, string $notificationId): void
    {
        $user->notifications()->where('id', $notificationId)->delete();
    }
}
