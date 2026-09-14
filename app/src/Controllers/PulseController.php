<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Messaging;
use App\Models\Notification;

/** Lightweight polling endpoint for live nav badges. */
final class PulseController extends Controller
{
    public function pulse(): void
    {
        $user = current_user();
        if (!$user) {
            json_response(['ok' => false, 'auth' => false], 401);
        }
        $uid = (int) $user['id'];
        json_response([
            'ok'            => true,
            'notifications' => Notification::unreadCount($uid),
            'messages'      => Messaging::totalUnread($uid),
            'requests'      => Messaging::requestCount($uid),
            'ts'            => time(),
        ]);
    }
}
