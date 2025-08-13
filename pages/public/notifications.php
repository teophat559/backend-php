<?php
$page_title = 'Thông báo';
include __DIR__ . '/../../includes/header.php';

if (!isUserLoggedIn()) {
    setFlashMessage('warning', 'Vui lòng đăng nhập để xem thông báo.');
    redirect(APP_URL . '/login');
}

$notifications = getNotifications($_SESSION['user_id'], 100);
?>

<div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
    <h1 class="text-2xl font-bold mb-4">Thông báo của bạn</h1>

    <?php if (empty($notifications)): ?>
        <p class="text-gray-400">Bạn chưa có thông báo nào.</p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $n): ?>
                <div class="p-4 rounded-md <?php echo $n['read'] ? 'bg-gray-700' : 'bg-gray-700/70 border border-primary-700'; ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-medium text-white"><?php echo htmlspecialchars($n['title']); ?></p>
                            <p class="text-sm text-gray-300 mt-1"><?php echo htmlspecialchars($n['message']); ?></p>
                        </div>
                        <span class="text-xs text-gray-400 ml-4"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
