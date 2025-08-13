<?php
$page_title = 'Tải lên (Dev)';
include __DIR__ . '/../../includes/header.php';

if ((getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'prod')) === 'prod') {
    setFlashMessage('warning', 'Trang chỉ dành cho môi trường phát triển.');
    redirect(APP_URL . '/');
}
?>

<div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
  <h1 class="text-2xl font-bold mb-4">Tải lên tệp</h1>
  <p class="text-gray-400 mb-6">Trang mô phỏng tính năng Upload từ bản React (chỉ hiển thị demo).</p>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>" />
    <input type="file" name="file" class="bg-gray-700 border border-gray-600 text-white rounded px-3 py-2" />
    <button class="ml-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded">Tải lên</button>
  </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
