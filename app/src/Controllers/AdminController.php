<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Request;
use App\Models\Messaging;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;

final class AdminController extends Controller
{
    public function dashboard(): void
    {
        $admin = require_admin();
        $this->render('admin/dashboard', [
            'meta'    => $this->meta(['title' => 'Admin · ' . config('app_name'), 'robots' => 'noindex,nofollow']),
            'admin'   => $admin,
            'section' => 'dashboard',
            'users'   => User::counts(),
            'posts'   => Post::countAll(),
            'messages' => Messaging::countAll(),
            'pending_reports' => Report::countPending(),
        ], 'admin');
    }

    public function users(): void
    {
        $admin = require_admin();
        $q = trim((string) Request::input('q', ''));
        $page = max(1, Request::int('page') ?: 1);
        [$rows, $total] = User::adminList($q, $page);

        $this->render('admin/users', [
            'meta'    => $this->meta(['title' => 'Admin: Users · ' . config('app_name'), 'robots' => 'noindex,nofollow']),
            'admin'   => $admin,
            'section' => 'users',
            'people'  => $rows,
            'total'   => $total,
            'page'    => $page,
            'per_page' => 25,
            'query'   => $q,
        ], 'admin');
    }

    public function setRole(array $params): void
    {
        $admin = require_admin();
        $this->verifyCsrf();
        $id = (int) $params['id'];
        $role = (string) Request::input('role', 'user');

        if ($id === (int) $admin['id']) {
            $this->fail('You can\'t change your own role.', '/admin/users');
        }
        $role = $role === 'admin' ? 'admin' : 'user';
        User::setRole($id, $role);
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Role updated.', 'role' => $role]);
        }
        flash('Role updated.', 'success');
        redirect('/admin/users');
    }

    public function suspend(array $params): void
    {
        $admin = require_admin();
        $this->verifyCsrf();
        $id = (int) $params['id'];

        if ($id === (int) $admin['id']) {
            $this->fail('You can\'t suspend your own account.', '/admin/users');
        }
        User::setSuspended($id, true);
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Account suspended.', 'suspended' => true]);
        }
        flash('Account suspended.', 'success');
        redirect('/admin/users');
    }

    public function unsuspend(array $params): void
    {
        require_admin();
        $this->verifyCsrf();
        User::setSuspended((int) $params['id'], false);
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Account reinstated.', 'suspended' => false]);
        }
        flash('Account reinstated.', 'success');
        redirect('/admin/users');
    }

    public function reports(): void
    {
        $admin = require_admin();
        $reports = Report::pending();

        // Hydrate a short preview of whatever each report points at.
        foreach ($reports as &$r) {
            $r['subject_preview'] = $this->subjectPreview($r['subject_type'], (int) $r['subject_id']);
        }
        unset($r);

        $this->render('admin/reports', [
            'meta'    => $this->meta(['title' => 'Admin: Reports · ' . config('app_name'), 'robots' => 'noindex,nofollow']),
            'admin'   => $admin,
            'section' => 'reports',
            'reports' => $reports,
        ], 'admin');
    }

    private function subjectPreview(string $type, int $id): ?array
    {
        return match ($type) {
            'post' => Post::find($id) ?: null,
            'user' => User::find($id) ?: null,
            'message' => Messaging::messageById($id) ?: null,
            default => null,
        };
    }

    public function dismissReport(array $params): void
    {
        $admin = require_admin();
        $this->verifyCsrf();
        Report::resolve((int) $params['id'], (int) $admin['id'], 'dismissed');
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Report dismissed.']);
        }
        flash('Report dismissed.', 'success');
        redirect('/admin/reports');
    }

    public function actionReport(array $params): void
    {
        $admin = require_admin();
        $this->verifyCsrf();
        $report = Report::find((int) $params['id']);
        if (!$report || $report['handled_at'] !== null) {
            $this->fail('That report is no longer open.', '/admin/reports');
        }

        $subjectId = (int) $report['subject_id'];
        match ($report['subject_type']) {
            'post'    => Post::adminDelete($subjectId),
            'message' => Messaging::adminDeleteMessage($subjectId),
            'user'    => User::setSuspended($subjectId, true),
            default   => null,
        };
        Report::resolve((int) $report['id'], (int) $admin['id'], 'removed');

        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Content removed and report resolved.']);
        }
        flash('Content removed and report resolved.', 'success');
        redirect('/admin/reports');
    }

    private function fail(string $message, string $back): never
    {
        if (Request::wantsJson()) {
            json_response(['ok' => false, 'error' => $message], 422);
        }
        flash($message, 'error');
        redirect($back);
    }
}
