<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Notification;
use App\Models\Social;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $user = require_login();
        $items = Notification::list((int) $user['id']);
        $requests = Social::pendingRequests((int) $user['id']);
        Notification::markAllRead((int) $user['id']);

        $this->render('notifications', [
            'meta'     => $this->meta(['title' => 'Notifications — ' . config('app_name'), 'robots' => 'noindex,nofollow']),
            'items'    => $items,
            'requests' => $requests,
            'viewer'   => $user,
        ]);
    }
}
