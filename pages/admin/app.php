<?php
// Admin React App host page: embeds the built admin SPA while keeping PHP session, CSRF, and auth in place.
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
session_start();
require_once __DIR__ . '/../../includes/admin-security.php';
requireAdminKey();

?><!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <script src="https://kit.fontawesome.com/3c8c010643.js" crossorigin="anonymous"></script>
    <script>
      // Expose CSRF token to the SPA
      window.__CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
    </script>
    <link rel="stylesheet" href="/admin/assets/index-984fc7ce.css">
    <script type="module" crossorigin src="/admin/assets/index-d9023fb7.js"></script>
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
