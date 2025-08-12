<?php
$page_title = '404 - Không tìm thấy trang';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-6xl text-yellow-400 mb-4"></i>
            <h1 class="text-6xl font-bold text-white mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-gray-300 mb-4">Không tìm thấy trang</h2>
            <p class="text-gray-400 mb-8">
                Trang bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển.
            </p>
        </div>

        <div class="space-y-4">
            <a href="<?php echo APP_URL; ?>" class="block w-full bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-home mr-2"></i> Về trang chủ
            </a>

            <a href="<?php echo APP_URL; ?>/contests" class="block w-full bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-trophy mr-2"></i> Xem cuộc thi
            </a>

            <a href="<?php echo APP_URL; ?>/rankings" class="block w-full bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-chart-bar mr-2"></i> Bảng xếp hạng
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
