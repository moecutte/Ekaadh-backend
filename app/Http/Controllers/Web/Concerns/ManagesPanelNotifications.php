<?php

namespace App\Http\Controllers\Web\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

trait ManagesPanelNotifications
{
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->paginate(20);

        return view($this->notificationView(), compact('notifications'));
    }

    public function open(string $notification): RedirectResponse
    {
        $note = auth()->user()->notifications()->whereKey($notification)->firstOrFail();
        $note->markAsRead();

        $url = is_string($note->data['url'] ?? null) && $note->data['url'] !== ''
            ? $note->data['url']
            : $this->notificationIndexRoute();

        return redirect()->to($url);
    }

    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    abstract protected function notificationView(): string;

    abstract protected function notificationIndexRoute(): string;
}
