<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
session_start();
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/admin-security.php';
requireAdminKey();

$page_title = 'Quản lý Người dùng - Giao diện';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
  <h1 class="text-2xl font-bold mb-4">Giao diện người dùng</h1>
  <p class="text-gray-400">Thiết lập giao diện hiển thị cho user.</p>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
