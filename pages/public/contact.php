<?php
$page_title = 'Liên hệ';
include __DIR__ . '/../../includes/header.php';
?>
<div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
  <h1 class="text-2xl font-bold mb-4">Liên hệ</h1>
  <form method="post" class="space-y-4">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>" />
    <input class="w-full bg-gray-700 border border-gray-600 text-white rounded px-3 py-2" placeholder="Họ tên" />
    <input class="w-full bg-gray-700 border border-gray-600 text-white rounded px-3 py-2" placeholder="Email" />
    <textarea class="w-full bg-gray-700 border border-gray-600 text-white rounded px-3 py-2" placeholder="Nội dung"></textarea>
    <button class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded">Gửi</button>
  </form>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
