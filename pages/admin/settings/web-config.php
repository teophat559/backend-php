<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
session_start();
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/admin-security.php';
requireAdminKey();

$page_title = 'Cấu hình Web';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
  <h1 class="text-2xl font-bold mb-4">Cấu hình Web</h1>
  <p class="text-gray-400">Form cấu hình web có thể tái sử dụng từ pages/admin/settings.php hoặc tách nhỏ theo nhu cầu.</p>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
