<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/admin-security.php';

// Require only admin key verification
requireAdminKey();

$page_title = 'Dashboard';
include '../../../includes/header.php';

// Get statistics
$stats = getStatistics();
$recent_activity = getUserActivity(null, 10);
$recent_contests = getContests('active', 5);
$top_contestants = getTopContestants(5);

// Handle quick actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && checkCSRFToken()) {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_contest':
            // Redirect to contest creation
            redirect(APP_URL . '/admin/contests?action=create');
            break;
        case 'create_contestant':
            // Redirect to contestant creation
            redirect(APP_URL . '/admin/contestants?action=create');
            break;
        case 'send_notification':
            // Handle notification sending
            $user_id = $_POST['user_id'] ?? '';
            $title = sanitizeInput($_POST['title'] ?? '');
            $message = sanitizeInput($_POST['message'] ?? '');

            if ($user_id && $title && $message) {
                if (createNotification($user_id, $title, $message)) {
                    setFlashMessage('success', 'Thông báo đã được gửi thành công.');
                } else {
                    setFlashMessage('error', 'Có lỗi xảy ra khi gửi thông báo.');
                }
            }
            break;
    }
}
?>

<!-- Admin Header -->
<div class="bg-gray-800 border-b border-gray-700 mb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white">Dashboard</h1>
                <p class="text-gray-400 mt-1">Chào mừng trở lại, <?php echo getCurrentAdmin()['full_name'] ?: getCurrentAdmin()['username']; ?>!</p>
            </div>
            <div class="flex space-x-4">
                <a href="<?php echo APP_URL; ?>/admin/contests?action=create"
                   class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-plus mr-2"></i> Tạo cuộc thi
                </a>
                <a href="<?php echo APP_URL; ?>/admin/contestants?action=create"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-user-plus mr-2"></i> Thêm thí sinh
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-500/10">
                <i class="fas fa-users text-blue-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Tổng người dùng</p>
                <p class="text-2xl font-bold text-white"><?php echo formatNumber($stats['total_users']); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-green-400 text-sm">
                <i class="fas fa-arrow-up mr-1"></i>
                +<?php echo $stats['today_registrations']; ?> hôm nay
            </span>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-500/10">
                <i class="fas fa-trophy text-green-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Cuộc thi đang diễn ra</p>
                <p class="text-2xl font-bold text-white"><?php echo formatNumber($stats['active_contests']); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-gray-400 text-sm">
                Tổng: <?php echo formatNumber($stats['total_contests']); ?> cuộc thi
            </span>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-500/10">
                <i class="fas fa-star text-yellow-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Tổng lượt bình chọn</p>
                <p class="text-2xl font-bold text-white"><?php echo formatNumber($stats['total_votes']); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-green-400 text-sm">
                <i class="fas fa-arrow-up mr-1"></i>
                +<?php echo $stats['today_votes']; ?> hôm nay
            </span>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-500/10">
                <i class="fas fa-user-friends text-purple-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Tổng thí sinh</p>
                <p class="text-2xl font-bold text-white"><?php echo formatNumber($stats['total_contestants']); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-gray-400 text-sm">
                Trung bình: <?php echo $stats['total_contests'] > 0 ? round($stats['total_contestants'] / $stats['total_contests'], 1) : 0; ?> thí sinh/cuộc thi
            </span>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Recent Activity -->
    <div class="lg:col-span-2">
        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-white">Hoạt động gần đây</h2>
            </div>
            <div class="p-6">
                <?php if (empty($recent_activity)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-info-circle text-gray-500 text-3xl mb-4"></i>
                        <p class="text-gray-400">Chưa có hoạt động nào</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-400 text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-white">
                                        <span class="font-medium"><?php echo htmlspecialchars($activity['username'] ?? 'Khách'); ?></span>
                                        <?php
                                        switch ($activity['action']) {
                                            case 'user_login':
                                                echo 'đã đăng nhập';
                                                break;
                                            case 'user_register':
                                                echo 'đã đăng ký tài khoản mới';
                                                break;
                                            case 'vote_cast':
                                                echo 'đã bình chọn';
                                                break;
                                            case 'admin_login':
                                                echo 'đã đăng nhập với quyền admin';
                                                break;
                                            default:
                                                echo $activity['action'];
                                        }
                                        ?>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <?php echo formatDate($activity['created_at']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 text-center">
                        <a href="<?php echo APP_URL; ?>/admin/activity" class="text-primary-400 hover:text-primary-300 text-sm">
                            Xem tất cả hoạt động <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Recent Contests -->
    <div class="space-y-8">
        <!-- Quick Actions -->
        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-white">Thao tác nhanh</h2>
            </div>
            <div class="p-6 space-y-4">
                <a href="<?php echo APP_URL; ?>/admin/contests?action=create"
                   class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-plus text-primary-400 mr-3"></i>
                    <span class="text-white">Tạo cuộc thi mới</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/contestants?action=create"
                   class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-user-plus text-primary-400 mr-3"></i>
                    <span class="text-white">Thêm thí sinh</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/users"
                   class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-users-cog text-primary-400 mr-3"></i>
                    <span class="text-white">Quản lý người dùng</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/notifications"
                   class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-bell text-primary-400 mr-3"></i>
                    <span class="text-white">Gửi thông báo</span>
                </a>

                                <a href="<?php echo APP_URL; ?>/admin/settings"
                   class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-cog text-primary-400 mr-3"></i>
                    <span class="text-white">Cài đặt hệ thống</span>
                </a>

                                <a href="<?php echo APP_URL; ?>/admin/social-login-management"
                   class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-sign-in-alt text-primary-400 mr-3"></i>
                    <span class="text-white">Quản lý Social Login</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/session-management"
                   class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-history text-primary-400 mr-3"></i>
                    <span class="text-white">Quản lý Phiên Đăng nhập</span>
                </a>
            </div>
        </div>

        <!-- Recent Contests -->
        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-white">Cuộc thi gần đây</h2>
            </div>
            <div class="p-6">
                <?php if (empty($recent_contests)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-trophy text-gray-500 text-2xl mb-2"></i>
                        <p class="text-gray-400 text-sm">Chưa có cuộc thi nào</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_contests as $contest): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                                <div class="flex-1">
                                    <h3 class="text-white font-medium"><?php echo htmlspecialchars($contest['name']); ?></h3>
                                    <p class="text-gray-400 text-sm">
                                        <?php echo $contest['contestant_count']; ?> thí sinh •
                                        <?php echo formatNumber($contest['total_votes']); ?> phiếu
                                    </p>
                                </div>
                                <a href="<?php echo APP_URL; ?>/admin/contests?action=edit&id=<?php echo $contest['id']; ?>"
                                   class="text-primary-400 hover:text-primary-300">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="<?php echo APP_URL; ?>/admin/contests" class="text-primary-400 hover:text-primary-300 text-sm">
                            Xem tất cả cuộc thi <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Contestants -->
<?php if (!empty($top_contestants)): ?>
<div class="mt-8">
    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="p-6 border-b border-gray-700">
            <h2 class="text-xl font-semibold text-white">Thí sinh nổi bật</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($top_contestants as $index => $contestant): ?>
                    <div class="bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <div class="relative">
                                <img src="<?php echo imageUrl($contestant['image_url'], 'contestant'); ?>"
                                     alt="<?php echo htmlspecialchars($contestant['name']); ?>"
                                     class="w-12 h-12 rounded-full object-cover">
                                <?php if ($index < 3): ?>
                                    <div class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold
                                        <?php echo $index === 0 ? 'bg-yellow-400 text-black' : ($index === 1 ? 'bg-gray-300 text-black' : 'bg-yellow-600 text-white'); ?>">
                                        <?php echo $index + 1; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-white font-medium"><?php echo htmlspecialchars($contestant['name']); ?></h3>
                                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($contestant['contest_name']); ?></p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="text-primary-400 font-semibold">
                                <i class="fas fa-star mr-1"></i>
                                <?php echo formatNumber($contestant['total_votes']); ?> phiếu
                            </div>
                            <a href="<?php echo APP_URL; ?>/admin/contestants?action=edit&id=<?php echo $contestant['id']; ?>"
                               class="text-primary-400 hover:text-primary-300 text-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../../../includes/footer.php'; ?>
