<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ManagesPanelNotifications;

class NotificationController extends Controller
{
    use ManagesPanelNotifications;

    protected function notificationView(): string
    {
        return 'organizer.notifications.index';
    }

    protected function notificationIndexRoute(): string
    {
        return route('organizer.notifications.index');
    }
}
