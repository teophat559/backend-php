<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
session_start();
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/admin-security.php';
requireAdminKey();

$page_title = 'Cài đặt âm thanh thông báo';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
  <h1 class="text-2xl font-bold mb-4">Cài đặt âm thanh</h1>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>" />
    <div class="flex items-center mb-4">
      <input type="checkbox" id="enable_user_sound" class="mr-2" checked />
      <label for="enable_user_sound">Bật âm thanh cho sự kiện người dùng</label>
    </div>
    <button class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded">Lưu</button>
  </form>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
