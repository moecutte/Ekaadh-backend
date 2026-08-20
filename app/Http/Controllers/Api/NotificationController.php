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
        $user = $request->user();
        $notes = $user->notifications()->latest()->paginate(40);

        return response()->json([
            'data' => $notes->getCollection()->map(fn (DatabaseNotification $n) => $this->payload($n))->values(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true, 'unread_count' => 0]);
    }

    public function open(Request $request, string $notification): JsonResponse
    {
        $note = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $note->markAsRead();

        return response()->json([
            'data' => $this->payload($note->fresh()),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(DatabaseNotification $note): array
    {
        $data = is_array($note->data) ? $note->data : [];

        return [
            'id' => $note->id,
            'title' => (string) ($data['title'] ?? ''),
            'body' => (string) ($data['body'] ?? ''),
            'kind' => (string) ($data['kind'] ?? ''),
            'url' => $data['url'] ?? null,
            'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : [],
            'read_at' => $note->read_at?->toIso8601String(),
            'created_at' => $note->created_at?->toIso8601String(),
        ];
    }
}
