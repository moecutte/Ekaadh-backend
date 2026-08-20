<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ManagesPanelNotifications;

class NotificationController extends Controller
{
    use ManagesPanelNotifications;

    protected function notificationView(): string
    {
        return 'admin.notifications.index';
    }

    protected function notificationIndexRoute(): string
    {
        return route('admin.notifications.index');
    }
}
