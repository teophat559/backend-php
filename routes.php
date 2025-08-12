<?php
// Centralized route definitions and dispatcher for SPA-like URLs

function dispatch_route(string $path): void {
    // Normalize path
    $normalized = trim($path, '/');

    // Home
    if ($normalized === '' || $normalized === 'home') {
        include __DIR__ . '/pages/public/home.php';
        return;
    }

    // Admin area
    if (strpos($normalized, 'admin') === 0) {
        $adminPath = trim(substr($normalized, strlen('admin')), '/');

        switch ($adminPath) {
            case '':
            case 'dashboard':
                if (!function_exists('isAdminKeyVerified') || !isAdminKeyVerified()) {
                    header('Location: /admin/verify-key');
                    exit;
                }
                include __DIR__ . '/pages/admin/dashboard.php';
                return;
            case 'login':
                include __DIR__ . '/pages/admin/login.php';
                return;
            case 'verify-key':
                include __DIR__ . '/pages/admin/verify-key.php';
                return;
            case 'logout':
                if (function_exists('adminLogout')) {
                    adminLogout();
                }
                header('Location: /admin/login');
                exit;
            case 'users':
                include __DIR__ . '/pages/admin/users.php';
                return;
            case 'contests':
                include __DIR__ . '/pages/admin/contests.php';
                return;
            case 'contestants':
                include __DIR__ . '/pages/admin/contestants.php';
                return;
            case 'notifications':
                include __DIR__ . '/pages/admin/notifications.php';
                return;
            case 'settings':
                include __DIR__ . '/pages/admin/settings.php';
                return;
            case 'social-login-management':
                include __DIR__ . '/pages/admin/social-login-management.php';
                return;
            case 'session-management':
                include __DIR__ . '/pages/admin/session-management.php';
                return;
            case 'session-details':
                include __DIR__ . '/pages/admin/session-details.php';
                return;
            case 'activity':
                include __DIR__ . '/pages/admin/activity.php';
                return;
            case 'ip-management':
                include __DIR__ . '/pages/admin/ip-management.php';
                return;
            default:
                include __DIR__ . '/pages/404.php';
                return;
        }
    }

    // Public named routes
    switch ($normalized) {
        case 'contests':
            include __DIR__ . '/pages/public/contests.php';
            return;
        case 'rankings':
            include __DIR__ . '/pages/public/rankings.php';
            return;
        case 'login':
            include __DIR__ . '/pages/public/login.php';
            return;
        case 'register':
            include __DIR__ . '/pages/public/register.php';
            return;
        case 'logout':
            if (function_exists('userLogout')) {
                userLogout();
            }
            header('Location: /');
            exit;
    }

    // Public: contest detail as /contest?id= or pretty /contest/{id}
    if ($normalized === 'contest') {
        include __DIR__ . '/pages/public/contest_detail.php';
        return;
    }
    if (preg_match('#^contest/(\d+)$#', $normalized, $m)) {
        $_GET['id'] = $m[1];
        include __DIR__ . '/pages/public/contest_detail.php';
        return;
    }

    // Public: user profile pretty route /user/{username}
    if (preg_match('#^user/([A-Za-z0-9_\.\-]+)$#', $normalized, $m)) {
        $_GET['username'] = $m[1];
        include __DIR__ . '/pages/public/user_profile.php';
        return;
    }

    // Fallback 404
    include __DIR__ . '/pages/404.php';
}

?>
