<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
session_start();
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/admin-security.php';
requireAdminKey();

$page_title = 'Tự động hóa Chrome - Điều khiển';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
  <h1 class="text-2xl font-bold mb-4">Bảng điều khiển Chrome</h1>
  <p class="text-gray-400">Điều khiển tác vụ tự động hóa Chrome.</p>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
