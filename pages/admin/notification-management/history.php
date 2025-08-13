<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
session_start();
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/admin-security.php';
requireAdminKey();

$page_title = 'Lịch sử thông báo';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
  <h1 class="text-2xl font-bold mb-4">Lịch sử gửi thông báo</h1>
  <p class="text-gray-400 mb-4">Trang PHP thay thế cho màn hình SPA lịch sử thông báo.</p>
  <div class="p-4 bg-gray-700 rounded">Bảng lịch sử sẽ hiển thị tại đây.</div>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
