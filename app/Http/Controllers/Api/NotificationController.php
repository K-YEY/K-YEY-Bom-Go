<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $this->findOwnedNotification($request, $notificationId);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    private function findOwnedNotification(Request $request, string $notificationId): DatabaseNotification
    {
        return $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();
    }
}
