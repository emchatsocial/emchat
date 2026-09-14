<?php
declare(strict_types=1);

use App\Router;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\FeedController;
use App\Controllers\HomeController;
use App\Controllers\InteractionController;
use App\Controllers\MessageController;
use App\Controllers\NotificationController;
use App\Controllers\PostController;
use App\Controllers\ProfileController;
use App\Controllers\PulseController;
use App\Controllers\SettingsController;
use App\Controllers\SitemapController;

/** @var Router $router */

$router->get('/', fn () => (new HomeController())->index());
$router->get('/about', fn () => (new HomeController())->about());
$router->get('/privacy', fn () => (new HomeController())->privacyPolicy());
$router->get('/terms', fn () => (new HomeController())->terms());
$router->get('/transparency', fn () => (new HomeController())->transparency());

// SEO
$router->get('/sitemap.xml', fn () => (new SitemapController())->index());
$router->get('/robots.txt', fn () => (new SitemapController())->robots());

// Auth — passwordless
$router->get('/login', fn () => (new AuthController())->showLogin());
$router->post('/login', fn () => (new AuthController())->sendLink());
$router->post('/login/resend', fn () => (new AuthController())->resendLink());
$router->get('/login/check', fn () => (new AuthController())->checkEmail());
$router->get('/auth/callback', fn () => (new AuthController())->callback());
$router->get('/welcome', fn () => (new AuthController())->showOnboard());
$router->post('/welcome', fn () => (new AuthController())->completeOnboard());
$router->get('/x/username-available', fn () => (new AuthController())->usernameAvailable());
$router->post('/logout', fn () => (new AuthController())->logout());

// Feed & discovery
$router->get('/feed', fn () => (new FeedController())->home());
$router->get('/explore', fn () => (new FeedController())->explore());

// Posts
$router->post('/posts', fn () => (new PostController())->store());
$router->get('/p/{id:\d+}', fn ($p) => (new PostController())->show($p));
$router->post('/p/{id:\d+}/edit', fn ($p) => (new PostController())->edit($p));
$router->post('/p/{id:\d+}/delete', fn ($p) => (new PostController())->destroy($p));
$router->post('/p/{id:\d+}/report', fn ($p) => (new PostController())->report($p));
$router->get('/p/{id:\d+}/likes', fn ($p) => (new PostController())->likers($p));

// Interactions (AJAX + form fallback)
$router->post('/x/like/{id:\d+}', fn ($p) => (new InteractionController())->like($p));
$router->post('/x/follow/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->follow($p));
$router->post('/x/unfollow/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->unfollow($p));
$router->post('/x/approve/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->approve($p));
$router->post('/x/deny/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->deny($p));
$router->post('/x/block/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->block($p));
$router->post('/x/unblock/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->unblock($p));
$router->post('/x/mute/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->mute($p));
$router->post('/x/unmute/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->unmute($p));
$router->post('/x/report/{username:[a-zA-Z0-9_]+}', fn ($p) => (new InteractionController())->report($p));

// Messages
$router->get('/messages', fn () => (new MessageController())->inbox());
$router->get('/messages/requests', fn () => (new MessageController())->requests());
$router->get('/messages/new', fn () => (new MessageController())->compose());
$router->get('/messages/new/group', fn () => (new MessageController())->newGroup());
$router->post('/messages/group', fn () => (new MessageController())->createGroup());
$router->get('/messages/new/{username:[a-zA-Z0-9_]+}', fn ($p) => (new MessageController())->start($p));
$router->get('/messages/{id:\d+}', fn ($p) => (new MessageController())->thread($p));
$router->post('/messages/{id:\d+}', fn ($p) => (new MessageController())->send($p));
$router->post('/messages/{id:\d+}/accept', fn ($p) => (new MessageController())->acceptRequest($p));
$router->post('/messages/{id:\d+}/decline', fn ($p) => (new MessageController())->declineRequest($p));
$router->post('/messages/{id:\d+}/delete', fn ($p) => (new MessageController())->deleteChat($p));
$router->get('/messages/{cid:\d+}/info', fn ($p) => (new MessageController())->groupInfo($p));
$router->post('/messages/{cid:\d+}/members', fn ($p) => (new MessageController())->addMembers($p));
$router->post('/messages/{cid:\d+}/members/{uid:\d+}/remove', fn ($p) => (new MessageController())->removeMember($p));
$router->post('/messages/{cid:\d+}/members/{uid:\d+}/role', fn ($p) => (new MessageController())->setRole($p));
$router->post('/messages/{cid:\d+}/rename', fn ($p) => (new MessageController())->renameGroup($p));
$router->post('/messages/{cid:\d+}/photo', fn ($p) => (new MessageController())->setPhoto($p));
$router->post('/messages/{cid:\d+}/leave', fn ($p) => (new MessageController())->leaveGroup($p));
$router->post('/messages/{cid:\d+}/m/{mid:\d+}/edit', fn ($p) => (new MessageController())->editMessage($p));
$router->post('/messages/{cid:\d+}/m/{mid:\d+}/delete', fn ($p) => (new MessageController())->deleteMessage($p));
$router->post('/messages/{cid:\d+}/m/{mid:\d+}/report', fn ($p) => (new MessageController())->reportMessage($p));

// Notifications
$router->get('/notifications', fn () => (new NotificationController())->index());

// Live badges
$router->get('/x/pulse', fn () => (new PulseController())->pulse());

// Settings
$router->get('/settings', fn () => redirect('/settings/profile'));
$router->get('/settings/profile', fn () => (new SettingsController())->profile());
$router->post('/settings/profile', fn () => (new SettingsController())->updateProfile());
$router->post('/settings/profile/avatar/remove', fn () => (new SettingsController())->removeAvatar());
$router->get('/settings/privacy', fn () => (new SettingsController())->privacy());
$router->post('/settings/privacy', fn () => (new SettingsController())->updatePrivacy());
$router->get('/settings/account', fn () => (new SettingsController())->account());
$router->post('/settings/account/export', fn () => (new SettingsController())->exportData());
$router->post('/settings/account/delete', fn () => (new SettingsController())->deleteAccount());

// Admin
$router->get('/admin', fn () => (new AdminController())->dashboard());
$router->get('/admin/users', fn () => (new AdminController())->users());
$router->post('/admin/users/{id:\d+}/role', fn ($p) => (new AdminController())->setRole($p));
$router->post('/admin/users/{id:\d+}/suspend', fn ($p) => (new AdminController())->suspend($p));
$router->post('/admin/users/{id:\d+}/unsuspend', fn ($p) => (new AdminController())->unsuspend($p));
$router->get('/admin/reports', fn () => (new AdminController())->reports());
$router->post('/admin/reports/{id:\d+}/dismiss', fn ($p) => (new AdminController())->dismissReport($p));
$router->post('/admin/reports/{id:\d+}/action', fn ($p) => (new AdminController())->actionReport($p));

// Profiles — keep last: greedy @handle routes
$router->get('/@{username:[a-zA-Z0-9_]+}', fn ($p) => (new ProfileController())->show($p));
$router->get('/@{username:[a-zA-Z0-9_]+}/card', fn ($p) => (new ProfileController())->card($p));
$router->get('/@{username:[a-zA-Z0-9_]+}/followers', fn ($p) => (new ProfileController())->followers($p));
$router->get('/@{username:[a-zA-Z0-9_]+}/following', fn ($p) => (new ProfileController())->following($p));
