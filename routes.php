<?php
// Centralized route definitions and dispatcher for SPA-like URLs

function dispatch_route(string $path): void {
    // Normalize path
    $normalized = trim($path, '/');

    // API passthrough: map extensionless /api/... to actual PHP files under api/
    if ($normalized === 'api' || str_starts_with($normalized, 'api/')) {
        $rest = trim(substr($normalized, 3), '/');
        if ($rest === '') {
            // Basic API index
            jsonResponse(['success' => true, 'message' => 'API root']);
        }
        // Special handling for contest nested routes
        if (preg_match('#^contest/(\d+)(?:/contestants)?$#', $rest, $m)) {
            if (str_ends_with($rest, '/contestants')) {
                include __DIR__ . '/api/contest/contestants.php';
                return;
            }
            include __DIR__ . '/api/contest/[id].php';
            return;
        }
        $apiFile = __DIR__ . '/api/' . $rest . '.php';
        if (is_file($apiFile)) {
            include $apiFile;
            return;
        }
        // Backward compat: support dash paths like auto-login -> auto-login.php
        $apiDash = __DIR__ . '/api/' . str_replace('-', '/', $rest) . '.php';
        if (is_file($apiDash)) {
            include $apiDash;
            return;
        }
        // 404 JSON for unknown API endpoint
        jsonResponse(['success' => false, 'message' => 'API endpoint not found'], 404);
    }

    // Home
    if ($normalized === '' || $normalized === 'home') {
        include __DIR__ . '/pages/public/home.php';
        return;
    }

    // Admin area (map React admin routes to PHP endpoint)
    if (strpos($normalized, 'admin') === 0) {
        $adminPath = trim(substr($normalized, strlen('admin')), '/');

        // Standalone admin endpoints
        if ($adminPath === 'login') {
            include __DIR__ . '/pages/admin/login.php';
            return;
        }
        if ($adminPath === 'verify-key') {
            include __DIR__ . '/pages/admin/verify-key.php';
            return;
        }
        if ($adminPath === 'logout') {
            if (function_exists('adminLogout')) {
                adminLogout();
            }
            header('Location: /admin/login');
            exit;
        }

        // Protect admin area by key verification when available
        if (function_exists('isAdminKeyVerified') && !isAdminKeyVerified()) {
            header('Location: /admin/verify-key');
            exit;
        }

        // Map known legacy PHP admin pages
        switch ($adminPath) {
            case '':
            case 'dashboard':
                // Prefer legacy PHP dashboard if available
                if (file_exists(__DIR__ . '/pages/admin/dashboard.php')) {
                    include __DIR__ . '/pages/admin/dashboard.php';
                    return;
                }
                break;
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
            case 'live-events':
                include __DIR__ . '/pages/admin/live-events.php';
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
        }

        // PHP-first replacements for former SPA admin routes
        // contest-management
        if ($adminPath === 'contest-management/contests') {
            include __DIR__ . '/pages/admin/contests.php';
            return;
        }
        if ($adminPath === 'contest-management/contestants') {
            include __DIR__ . '/pages/admin/contestants.php';
            return;
        }
        // notification-management
        if ($adminPath === 'notification-management/templates') {
            include __DIR__ . '/pages/admin/notification-management/templates.php';
            return;
        }
        if ($adminPath === 'notification-management/history') {
            include __DIR__ . '/pages/admin/notification-management/history.php';
            return;
        }
        if ($adminPath === 'notification-management/sound-settings') {
            include __DIR__ . '/pages/admin/notification-management/sound-settings.php';
            return;
        }
        // settings
        if ($adminPath === 'settings/web-config') {
            include __DIR__ . '/pages/admin/settings/web-config.php';
            return;
        }
        if ($adminPath === 'settings/admin-keys') {
            include __DIR__ . '/pages/admin/settings/admin-keys.php';
            return;
        }
        if ($adminPath === 'settings/auto-login') {
            include __DIR__ . '/pages/admin/settings/auto-login.php';
            return;
        }
        if ($adminPath === 'settings/admin-links') {
            include __DIR__ . '/pages/admin/settings/admin-links.php';
            return;
        }
        // chrome-management
        if ($adminPath === 'chrome-management/control') {
            include __DIR__ . '/pages/admin/chrome-management/control.php';
            return;
        }
        if ($adminPath === 'chrome-management/setup') {
            include __DIR__ . '/pages/admin/chrome-management/setup.php';
            return;
        }
        if ($adminPath === 'chrome-management/profiles') {
            include __DIR__ . '/pages/admin/chrome-management/profiles.php';
            return;
        }
        // user-management
        if ($adminPath === 'user-management/appearance') {
            include __DIR__ . '/pages/admin/user-management/appearance.php';
            return;
        }
        if ($adminPath === 'user-management/record') {
            include __DIR__ . '/pages/admin/user-management/record.php';
            return;
        }
        // admin-management generic
        if (preg_match('#^admin-management/([A-Za-z0-9_\-]+)$#', $adminPath, $m)) {
            $_GET['tab'] = $m[1];
            include __DIR__ . '/pages/admin/admin-management.php';
            return;
        }

        // React admin route groups -> serve compiled admin app
        // Matches: contest-management/*, notification-management/*, user-management/*, admin-management/*,
        // settings/*, chrome-management/*, and any other deep admin paths
        if (
            preg_match('#^(contest-management|notification-management|user-management|admin-management|settings|chrome-management)(/|$)#', $adminPath)
            || $adminPath === ''
            || $adminPath === 'dashboard'
        ) {
            include __DIR__ . '/pages/admin/app.php';
            return;
        }

        // Unknown admin route
        include __DIR__ . '/pages/404.php';
        return;
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
        case 'notifications':
            // User notifications page (from site header)
            if (file_exists(__DIR__ . '/pages/public/notifications.php')) {
                include __DIR__ . '/pages/public/notifications.php';
                return;
            }
            break;
        case 'about':
            if (file_exists(__DIR__ . '/pages/public/about.php')) { include __DIR__ . '/pages/public/about.php'; return; }
            break;
        case 'contact':
            if (file_exists(__DIR__ . '/pages/public/contact.php')) { include __DIR__ . '/pages/public/contact.php'; return; }
            break;
        case 'help':
            if (file_exists(__DIR__ . '/pages/public/help.php')) { include __DIR__ . '/pages/public/help.php'; return; }
            break;
        case 'faq':
            if (file_exists(__DIR__ . '/pages/public/faq.php')) { include __DIR__ . '/pages/public/faq.php'; return; }
            break;
        case 'terms':
            if (file_exists(__DIR__ . '/pages/public/terms.php')) { include __DIR__ . '/pages/public/terms.php'; return; }
            break;
        case 'privacy':
            if (file_exists(__DIR__ . '/pages/public/privacy.php')) { include __DIR__ . '/pages/public/privacy.php'; return; }
            break;
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
    // Alias: React uses /contests/{id}
    if (preg_match('#^contests/(\d+)$#', $normalized, $m)) {
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
