<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin-security.php';

// Require only admin key verification
requireAdminKey();
require_once '../../includes/session-management.php';

$page_title = 'Quản lý Phiên Đăng nhập';
include '../../includes/header.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && checkCSRFToken()) {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'cleanup':
            $result = cleanupOldSessions();
            setFlashMessage('success', "Đã xóa {$result['sessions_deleted']} phiên cũ và {$result['logs_deleted']} log");
            break;

        case 'export':
            $filters = [
                'platform' => $_POST['platform'] ?? '',
                'status' => $_POST['status'] ?? '',
                'date_from' => $_POST['date_from'] ?? '',
                'date_to' => $_POST['date_to'] ?? ''
            ];
            $format = $_POST['format'] ?? 'json';
            $data = exportSessionData($filters, $format);

            header('Content-Type: ' . ($format === 'csv' ? 'text/csv' : 'application/json'));
            header('Content-Disposition: attachment; filename="sessions_' . date('Y-m-d_H-i-s') . '.' . $format . '"');
            echo $data;
            exit;

        case 'block_ip':
            $ip = $_POST['ip_address'] ?? '';
            $reason = $_POST['reason'] ?? '';
            if ($ip && blockIP($ip, $reason)) {
                setFlashMessage('success', "Đã chặn IP: $ip");
            } else {
                setFlashMessage('error', 'Có lỗi xảy ra khi chặn IP');
            }
            break;

        case 'unblock_ip':
            $ip = $_POST['ip_address'] ?? '';
            if ($ip) {
                $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?");
                if ($stmt->execute([$ip])) {
                    setFlashMessage('success', "Đã bỏ chặn IP: $ip");
                } else {
                    setFlashMessage('error', 'Có lỗi xảy ra khi bỏ chặn IP');
                }
            }
            break;
    }
}

// Get filters
$page = max(1, intval($_GET['page'] ?? 1));
$filters = [
    'platform' => $_GET['platform'] ?? '',
    'status' => $_GET['status'] ?? '',
    'username' => $_GET['username'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Get sessions
$sessions_data = getLoginSessions($page, $filters);
$sessions = $sessions_data['sessions'];
$total = $sessions_data['total'];
$total_pages = $sessions_data['total_pages'];

// Get statistics
$stats = getSessionStatistics();

// Get active sessions for real-time monitoring
$active_sessions = getActiveSessions();
$recent_logs = getRecentLogs(20);
?>

<!-- Admin Header -->
<div class="bg-gray-800 border-b border-gray-700 mb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white">Quản lý Phiên Đăng nhập</h1>
                <p class="text-gray-400 mt-1">Theo dõi và quản lý tất cả phiên đăng nhập social media</p>
            </div>
            <div class="flex space-x-4">
                <button onclick="showExportModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-download mr-2"></i> Xuất dữ liệu
                </button>
                <button onclick="showBlockIPModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-ban mr-2"></i> Chặn IP
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-500/10">
                <i class="fas fa-users text-blue-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Tổng phiên</p>
                <p class="text-2xl font-bold text-white"><?php echo $stats['total_sessions']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-500/10">
                <i class="fas fa-check text-green-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Tỷ lệ thành công</p>
                <p class="text-2xl font-bold text-white"><?php echo $stats['success_rate']; ?>%</p>
            </div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-500/10">
                <i class="fas fa-clock text-yellow-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Hôm nay</p>
                <p class="text-2xl font-bold text-white"><?php echo $stats['today_sessions']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-500/10">
                <i class="fas fa-mobile-alt text-purple-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Thiết bị di động</p>
                <p class="text-2xl font-bold text-white">
                    <?php
                    $mobile_count = 0;
                    foreach ($stats['by_device'] as $device) {
                        if ($device['device_type'] === 'mobile') {
                            $mobile_count = $device['count'];
                            break;
                        }
                    }
                    echo $mobile_count;
                    ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-500/10">
                <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Đang xử lý</p>
                <p class="text-2xl font-bold text-white"><?php echo count($active_sessions); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="mb-6">
    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Nền tảng</label>
                <select name="platform" class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
                    <option value="">Tất cả</option>
                    <option value="facebook" <?php echo $filters['platform'] === 'facebook' ? 'selected' : ''; ?>>Facebook</option>
                    <option value="gmail" <?php echo $filters['platform'] === 'gmail' ? 'selected' : ''; ?>>Gmail</option>
                    <option value="instagram" <?php echo $filters['platform'] === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                    <option value="zalo" <?php echo $filters['platform'] === 'zalo' ? 'selected' : ''; ?>>Zalo</option>
                    <option value="yahoo" <?php echo $filters['platform'] === 'yahoo' ? 'selected' : ''; ?>>Yahoo</option>
                    <option value="microsoft" <?php echo $filters['platform'] === 'microsoft' ? 'selected' : ''; ?>>Microsoft</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Trạng thái</label>
                <select name="status" class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
                    <option value="">Tất cả</option>
                    <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                    <option value="processing" <?php echo $filters['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="success" <?php echo $filters['status'] === 'success' ? 'selected' : ''; ?>>Thành công</option>
                    <option value="failed" <?php echo $filters['status'] === 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                    <option value="blocked" <?php echo $filters['status'] === 'blocked' ? 'selected' : ''; ?>>Bị chặn</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Tài khoản</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($filters['username']); ?>"
                       placeholder="Tìm theo tài khoản"
                       class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Từ ngày</label>
                <input type="date" name="date_from" value="<?php echo $filters['date_from']; ?>"
                       class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Đến ngày</label>
                <input type="date" name="date_to" value="<?php echo $filters['date_to']; ?>"
                       class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-filter mr-2"></i> Lọc
                </button>
                <a href="<?php echo APP_URL; ?>/admin/session-management" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-times mr-2"></i> Xóa
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Sessions Table -->
<div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-white">Danh sách phiên đăng nhập</h2>
                <p class="text-gray-400 text-sm mt-1">Tổng cộng: <?php echo $total; ?> phiên</p>
            </div>
            <form method="POST" class="inline-block">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="cleanup">
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium" onclick="return confirm('Xóa tất cả phiên cũ (>30 ngày)?')">
                    <i class="fas fa-trash mr-2"></i> Dọn dẹp
                </button>
            </form>
        </div>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-gray-500 text-4xl mb-4"></i>
            <p class="text-gray-400">Không có phiên đăng nhập nào</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Session ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Nền tảng
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Tài khoản
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            IP & Thiết bị
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Trạng thái
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Thời gian
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    <?php foreach ($sessions as $session): ?>
                        <tr class="hover:bg-gray-700 transition-colors" data-session-id="<?php echo htmlspecialchars($session['session_id']); ?>" data-status="<?php echo htmlspecialchars(strtolower($session['status'])); ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <code class="text-xs"><?php echo substr($session['session_id'], 0, 20) . '...'; ?></code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fab fa-<?php echo $session['platform']; ?> text-2xl mr-3
                                        <?php
                                        echo $session['platform'] === 'facebook' ? 'text-blue-400' :
                                            ($session['platform'] === 'gmail' ? 'text-red-400' :
                                            ($session['platform'] === 'instagram' ? 'text-pink-400' :
                                            ($session['platform'] === 'zalo' ? 'text-blue-400' :
                                            ($session['platform'] === 'yahoo' ? 'text-purple-400' : 'text-blue-400'))));
                                        ?>"></i>
                                    <span class="text-white font-medium"><?php echo ucfirst($session['platform']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-white"><?php echo htmlspecialchars($session['username']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300"><?php echo htmlspecialchars($session['user_ip']); ?></div>
                                <div class="text-xs text-gray-400">
                                    <?php echo $session['device_type']; ?> • <?php echo $session['browser']; ?> • <?php echo $session['os']; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span data-role="status-badge" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php
                                    $st = strtolower($session['status']);
                                    echo $st === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                        ($st === 'processing' ? 'bg-blue-100 text-blue-800' :
                                        ($st === 'success' ? 'bg-green-100 text-green-800' :
                                        ($st === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')));
                                ?>">
                                    <?php
                                    echo $st === 'pending' ? 'Chờ xử lý' :
                                        ($st === 'processing' ? 'Đang xử lý' :
                                        ($st === 'success' ? 'Thành công' :
                                        ($st === 'failed' ? 'Thất bại' : ucfirst($st))));
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo formatDate($session['created_at']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a href="<?php echo APP_URL; ?>/admin/session-details?id=<?php echo $session['session_id']; ?>" class="text-primary-400 hover:text-primary-300" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php
                                      $st = strtolower($session['status']);
                                      $canApprove = in_array($st, ['pending','device_approval_required']);
                                      $canOtp = in_array($st, ['otp_required']);
                                      $isVerifying = ($st === 'otp_verifying');
                                      $terminal = in_array($st, ['approved','otp_approved','rejected','expired','success','failed','blocked','reset']);
                                    ?>
                                    <button class="px-3 py-1 rounded bg-green-600 hover:bg-green-700 text-white disabled:opacity-50"
                                            title="Phê duyệt thiết bị"
                                            onclick="adminSessionAction(this,'approve','<?php echo $session['session_id']; ?>')"
                                            <?php echo $canApprove ? '' : 'disabled'; ?>>
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50"
                                            title="Duyệt OTP hợp lệ"
                                            onclick="adminSessionAction(this,'otp_ok','<?php echo $session['session_id']; ?>')"
                                            <?php echo ($canOtp && !$isVerifying) ? '' : 'disabled'; ?>>
                                        <i class="fas fa-shield-alt"></i>
                                    </button>
                                    <button class="px-3 py-1 rounded bg-yellow-600 hover:bg-yellow-700 text-white disabled:opacity-50"
                                            title="Đánh dấu OTP sai"
                                            onclick="adminSessionAction(this,'otp_fail','<?php echo $session['session_id']; ?>')"
                                            <?php echo ($canOtp && !$isVerifying) ? '' : 'disabled'; ?>>
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                    <button class="px-3 py-1 rounded bg-red-600 hover:bg-red-700 text-white disabled:opacity-50"
                                            title="Reset phiên"
                                            onclick="adminSessionAction(this,'reset','<?php echo $session['session_id']; ?>')"
                                            <?php echo $terminal && !isAdminKeyVerified() ? 'disabled' : ''; ?>>
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-700">
                <?php echo generatePagination($page, $total_pages, APP_URL . '/admin/session-management?' . http_build_query($filters)); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Real-time Monitoring -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
    <!-- Active Sessions -->
    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-lg font-semibold text-white">Phiên đang hoạt động</h3>
            <p class="text-gray-400 text-sm">Cập nhật real-time</p>
        </div>
        <div class="p-6">
            <?php if (empty($active_sessions)): ?>
                <p class="text-gray-400 text-center">Không có phiên nào đang hoạt động</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($active_sessions as $session): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <div>
                                <div class="text-sm text-white"><?php echo htmlspecialchars($session['username']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo ucfirst($session['platform']); ?> • <?php echo $session['user_ip']; ?></div>
                            </div>
                            <div class="text-xs text-yellow-400">
                                <?php echo formatDate($session['created_at'], 'H:i:s'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-lg font-semibold text-white">Log gần đây</h3>
            <p class="text-gray-400 text-sm">20 log mới nhất</p>
        </div>
        <div class="p-6">
            <?php if (empty($recent_logs)): ?>
                <p class="text-gray-400 text-center">Không có log nào</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recent_logs as $log): ?>
                        <div class="flex items-start space-x-3 p-3 bg-gray-700 rounded-lg">
                            <div class="flex-shrink-0">
                                <i class="fas fa-circle text-xs
                                    <?php
                                    echo $log['status'] === 'success' ? 'text-green-400' :
                                        ($log['status'] === 'error' ? 'text-red-400' :
                                        ($log['status'] === 'warning' ? 'text-yellow-400' : 'text-blue-400'));
                                    ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-white"><?php echo htmlspecialchars($log['action']); ?></div>
                                <div class="text-xs text-gray-400">
                                    <?php echo htmlspecialchars($log['username']); ?> • <?php echo ucfirst($log['platform']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo formatDate($log['created_at'], 'H:i:s'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Xuất dữ liệu</h3>
                <button onclick="hideExportModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" action="<?php echo APP_URL; ?>/admin/session-management">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="export">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Định dạng</label>
                        <select name="format" class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nền tảng</label>
                        <select name="platform" class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
                            <option value="">Tất cả</option>
                            <option value="facebook">Facebook</option>
                            <option value="gmail">Gmail</option>
                            <option value="instagram">Instagram</option>
                            <option value="zalo">Zalo</option>
                            <option value="yahoo">Yahoo</option>
                            <option value="microsoft">Microsoft</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Trạng thái</label>
                        <select name="status" class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
                            <option value="">Tất cả</option>
                            <option value="success">Thành công</option>
                            <option value="failed">Thất bại</option>
                            <option value="pending">Chờ xử lý</option>
                            <option value="processing">Đang xử lý</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Từ ngày</label>
                            <input type="date" name="date_from" class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Đến ngày</label>
                            <input type="date" name="date_to" class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="hideExportModal()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                        Hủy
                    </button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-download mr-2"></i> Xuất
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Block IP Modal -->
<div id="blockIPModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Chặn IP</h3>
                <button onclick="hideBlockIPModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="block_ip">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Địa chỉ IP</label>
                        <input type="text" name="ip_address" id="blockIPAddress" required
                               placeholder="Nhập địa chỉ IP"
                               class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Lý do</label>
                        <textarea name="reason" rows="3" placeholder="Lý do chặn IP"
                                  class="bg-gray-700 border border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent w-full"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="hideBlockIPModal()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                        Hủy
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-ban mr-2"></i> Chặn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showExportModal() {
    document.getElementById('exportModal').classList.remove('hidden');
}

function hideExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}

function showBlockIPModal() {
    document.getElementById('blockIPModal').classList.remove('hidden');
}

function hideBlockIPModal() {
    document.getElementById('blockIPModal').classList.add('hidden');
}

function blockIP(ip) {
    document.getElementById('blockIPAddress').value = ip;
    showBlockIPModal();
}

function refreshAdminSessions(){
    // Refresh the table page with current filters via admin API then patch tbody
    var params = new URLSearchParams(window.location.search);
    var page = params.get('page') || '1';
    var status = params.get('status') || '';
    var platform = params.get('platform') || '';
    var search = params.get('username') || '';
    fetch('<?php echo APP_URL; ?>/api/admin/sessions?page='+encodeURIComponent(page)+'&status='+encodeURIComponent(status)+'&platform='+encodeURIComponent(platform)+'&search='+encodeURIComponent(search), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r=>r.json())
    .then(data=>{
        if (!data || !data.success) return;
        var rows = data.data && data.data.sessions ? data.data.sessions : [];
        var tbody = document.querySelector('table tbody');
        if (!tbody) return;
        var html = rows.map(function(s){
            var platformIcon = s.platform;
            var platformColor = s.platform === 'facebook' ? 'text-blue-400' : (s.platform === 'gmail' ? 'text-red-400' : (s.platform === 'instagram' ? 'text-pink-400' : (s.platform === 'zalo' ? 'text-blue-400' : (s.platform === 'yahoo' ? 'text-purple-400' : 'text-blue-400'))));
            var st = (s.status || '').toLowerCase();
            var statusBadgeClass = st === 'pending' ? 'bg-yellow-100 text-yellow-800' : (st === 'processing' ? 'bg-blue-100 text-blue-800' : (st === 'success' ? 'bg-green-100 text-green-800' : (st === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')));
            var statusLabel = st === 'pending' ? 'Chờ xử lý' : (st === 'processing' ? 'Đang xử lý' : (st === 'success' ? 'Thành công' : (st === 'failed' ? 'Thất bại' : (st ? st.charAt(0).toUpperCase()+st.slice(1) : ''))));
            var canApprove = ['pending','device_approval_required'].includes(st);
            var canOtp = ['otp_required'].includes(st);
            var isVerifying = (st === 'otp_verifying');
            var terminal = ['approved','otp_approved','rejected','expired','success','failed','blocked','reset'].includes(st);
            return '<tr class="hover:bg-gray-700 transition-colors" data-session-id="'+(s.session_id)+'" data-status="'+st+'">'
                +'<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><code class="text-xs">'+(String(s.session_id).slice(0,20))+'...</code></td>'
                +'<td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><i class="fab fa-'+platformIcon+' text-2xl mr-3 '+platformColor+'"></i><span class="text-white font-medium">'+(s.platform ? s.platform.charAt(0).toUpperCase()+s.platform.slice(1) : '')+'</span></div></td>'
                +'<td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-white">'+(s.username || '')+'</div></td>'
                +'<td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-300">'+(s.user_ip || '')+'</div><div class="text-xs text-gray-400">'+(s.device_type || '')+' • '+(s.browser || '')+' • '+(s.os || '')+'</div></td>'
                +'<td class="px-6 py-4 whitespace-nowrap"><span data-role="status-badge" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full '+statusBadgeClass+'">'+statusLabel+'</span></td>'
                +'<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">'+(s.created_at || '')+'</td>'
                +'<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">'
                +  '<div class="flex items-center space-x-3">'
                +    '<a href="<?php echo APP_URL; ?>/admin/session-details?id='+(encodeURIComponent(s.session_id))+'" class="text-primary-400 hover:text-primary-300" title="Xem chi tiết"><i class="fas fa-eye"></i></a>'
                +    '<button class="px-3 py-1 rounded bg-green-600 hover:bg-green-700 text-white" title="Phê duyệt thiết bị" '+(canApprove? '' : 'disabled')+' onclick="adminSessionAction(this,\'approve\',\''+(s.session_id)+'\')"><i class="fas fa-check"></i></button>'
                +    '<button class="px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white" title="Duyệt OTP hợp lệ" '+((canOtp && !isVerifying)? '' : 'disabled')+' onclick="adminSessionAction(this,\'otp_ok\',\''+(s.session_id)+'\')"><i class="fas fa-shield-alt"></i></button>'
                +    '<button class="px-3 py-1 rounded bg-yellow-600 hover:bg-yellow-700 text-white" title="Đánh dấu OTP sai" '+((canOtp && !isVerifying)? '' : 'disabled')+' onclick="adminSessionAction(this,\'otp_fail\',\''+(s.session_id)+'\')"><i class="fas fa-exclamation-triangle"></i></button>'
                +    '<button class="px-3 py-1 rounded bg-red-600 hover:bg-red-700 text-white" title="Reset phiên" '+(terminal? 'disabled' : '')+' onclick="adminSessionAction(this,\'reset\',\''+(s.session_id)+'\')"><i class="fas fa-undo"></i></button>'
                +  '</div>'
                +'</td>'
                +'</tr>';
        }).join('');
        tbody.innerHTML = html;
    })
    .catch(()=>{});
}

function refreshAdminRealtimePanels(){
    // Optional lightweight refresh for active sessions and logs via mini endpoints if available.
    // Fallback: reuse full refreshAdminSessions (table contains core data)
    refreshAdminSessions();
}

// Auto-refresh every 30 seconds without full page reload
setInterval(function() {
    if (!document.hidden) {
        try { refreshAdminRealtimePanels(); } catch(e) {}
    }
}, 30000);

// Close modals when clicking outside
document.getElementById('exportModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideExportModal();
    }
});

document.getElementById('blockIPModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideBlockIPModal();
    }
});

async function adminSessionAction(btn, action, sessionId){
    if (!btn || !action || !sessionId) return;
    // Confirmation and reasons where required
    let reason = '';
    if (action === 'reset') {
        if (!confirm('Bạn có chắc chắn muốn reset phiên này? Người dùng sẽ phải đăng nhập lại.')) return;
        reason = prompt('Vui lòng nhập lý do reset phiên:');
        if (!reason || reason.trim().length < 3) { alert('Cần nhập lý do hợp lệ.'); return; }
    }
    if (action === 'otp_fail') {
        const choices = ['Mã không khớp','Hết hạn','Sai định dạng','Khác'];
        const picked = prompt('Lý do OTP sai (gợi ý: '+choices.join(', ')+')');
        reason = (picked || '').trim();
        if (!reason || reason.length < 3) { alert('Cần nhập lý do hợp lệ.'); return; }
    }

    // Loading state
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const res = await fetch('<?php echo APP_URL; ?>/api/admin/session-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: '<?php echo $_SESSION['csrf_token']; ?>',
                session_id: sessionId,
                action: action,
                reason: reason
            })
        });
        const data = await res.json();
        if (data && data.success) {
            // Minimal row update
            try { await updateSessionRow(sessionId); } catch(e) {}
        } else {
            alert(data && data.message ? data.message : 'Thao tác thất bại');
        }
    } catch(e){
        alert('Lỗi mạng, vui lòng thử lại.');
    } finally {
        btn.innerHTML = orig;
        btn.disabled = false;
    }
}

async function updateSessionRow(sessionId){
    const res = await fetch('<?php echo APP_URL; ?>/api/admin/session?id='+encodeURIComponent(sessionId), { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    const j = await res.json();
    if (!j || !j.success) return refreshAdminSessions();
    const s = j.data;
    const tr = document.querySelector('tr[data-session-id="'+CSS.escape(String(sessionId))+'"]');
    if (!tr) return refreshAdminSessions();
    // Update status badge
    const st = (s.status || '').toLowerCase();
    const badge = tr.querySelector('[data-role="status-badge"]');
    if (badge){
        const cls = st === 'pending' ? 'bg-yellow-100 text-yellow-800' : (st === 'processing' ? 'bg-blue-100 text-blue-800' : (st === 'success' ? 'bg-green-100 text-green-800' : (st === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')));
        badge.className = 'inline-flex px-2 py-1 text-xs font-semibold rounded-full '+cls;
        badge.textContent = st === 'pending' ? 'Chờ xử lý' : (st === 'processing' ? 'Đang xử lý' : (st === 'success' ? 'Thành công' : (st === 'failed' ? 'Thất bại' : (st ? st.charAt(0).toUpperCase()+st.slice(1) : ''))));
    }
    tr.setAttribute('data-status', st);
    // Toggle buttons
    const canApprove = ['pending','device_approval_required'].includes(st);
    const canOtp = ['otp_required'].includes(st);
    const isVerifying = (st === 'otp_verifying');
    const terminal = ['approved','otp_approved','rejected','expired','success','failed','blocked','reset'].includes(st);
    const buttons = tr.querySelectorAll('td:last-child button');
    if (buttons && buttons.length >= 4){
        buttons[0].disabled = !canApprove;
        buttons[1].disabled = !(canOtp && !isVerifying);
        buttons[2].disabled = !(canOtp && !isVerifying);
        buttons[3].disabled = terminal; // adjust if policy allows force reset
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
