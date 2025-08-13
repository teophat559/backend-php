<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
session_start();
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/admin-security.php';
requireAdminKey();

$page_title = 'Cấu hình Auto Login';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-6">
  <h1 class="text-2xl font-bold">Cấu hình & Kiểm tra Auto Login</h1>
  <p class="text-gray-400">Sử dụng MoreLogin để mở Chrome tự động và thực hiện quy trình đăng nhập. Bạn có thể thử Dry-run hoặc khởi chạy thật để nhận wsEndpoint.</p>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="space-y-4">
      <div>
        <label class="block text-sm text-gray-300 mb-1">Nền tảng</label>
        <select id="platform" class="w-full bg-gray-700 text-white rounded px-3 py-2 border border-gray-600">
          <option value="facebook">Facebook</option>
          <option value="google">Google</option>
          <option value="outlook">Outlook/Microsoft</option>
          <option value="yahoo">Yahoo</option>
          <option value="instagram">Instagram</option>
          <option value="zalo">Zalo</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-gray-300 mb-1">Tài khoản</label>
        <input id="account" type="text" class="w-full bg-gray-700 text-white rounded px-3 py-2 border border-gray-600" placeholder="email hoặc username">
      </div>
      <div>
        <label class="block text-sm text-gray-300 mb-1">Mật khẩu</label>
        <input id="password" type="password" class="w-full bg-gray-700 text-white rounded px-3 py-2 border border-gray-600" placeholder="mật khẩu">
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-gray-300 mb-1">MoreLogin Profile Name</label>
          <input id="profileName" type="text" class="w-full bg-gray-700 text-white rounded px-3 py-2 border border-gray-600" placeholder="Tên profile (tùy chọn)">
        </div>
        <div>
          <label class="block text-sm text-gray-300 mb-1">MoreLogin Profile ID</label>
          <input id="profileId" type="text" class="w-full bg-gray-700 text-white rounded px-3 py-2 border border-gray-600" placeholder="UUID (ưu tiên nếu có)">
        </div>
      </div>
      <div>
        <label class="inline-flex items-center space-x-2">
          <input id="dryRun" type="checkbox" class="form-checkbox h-4 w-4 text-primary-500 bg-gray-700 border-gray-600 rounded" checked>
          <span class="text-gray-300 text-sm">Dry-run (không mở Chrome)</span>
        </label>
      </div>
      <div class="flex items-center gap-3">
        <button id="btnRun" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded">Chạy kiểm tra</button>
        <button id="btnStart" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">Khởi chạy thật</button>
      </div>
    </div>

    <div>
      <label class="block text-sm text-gray-300 mb-1">Kết quả</label>
      <pre id="result" class="h-64 overflow-auto bg-black/40 text-green-300 text-sm p-3 rounded border border-gray-700">Chưa có kết quả.</pre>
      <div class="mt-3 text-sm text-gray-400">
        Ghi chú: để khởi chạy thật, cần cài và chạy MoreLogin local. Thiết lập biến môi trường trong server: MORELOGIN_BASE_URL, MORELOGIN_API_ID, MORELOGIN_API_KEY.
      </div>
      <div id="attachBox" class="mt-4 hidden">
        <div class="text-sm text-gray-300 mb-1">Lệnh gắn Puppeteer (tùy chọn):</div>
        <pre id="attachCmd" class="bg-gray-900 text-gray-100 text-xs p-2 rounded border border-gray-700"></pre>
      </div>
    </div>
  </div>
</div>

<script>
const el = (id) => document.getElementById(id);
function show(res) {
  try { el('result').textContent = JSON.stringify(res, null, 2); } catch { el('result').textContent = String(res); }
  // If wsEndpoint present, show attach command
  try {
    const ws = res?.wsEndpoint;
    const box = el('attachBox');
        if (ws) {
          const cmd = `node scripts/attach-puppeteer.mjs --ws "${ws}" --platform ${el('platform').value}`;
      el('attachCmd').textContent = cmd;
      box.classList.remove('hidden');
    } else {
      box.classList.add('hidden');
    }
  } catch {}
}

async function callAutoLogin(dryRun) {
  const payload = {
    account: el('account').value.trim(),
    password: el('password').value,
    platform: el('platform').value,
    dryRun: !!dryRun,
  };
  const profileId = el('profileId').value.trim();
  const profileName = el('profileName').value.trim();
  if (profileId) payload.moreLoginId = profileId;
  if (profileName) payload.chrome = { profileName };

  const btn = dryRun ? el('btnRun') : el('btnStart');
  const original = btn.textContent;
  btn.disabled = true; btn.textContent = 'Đang xử lý...';
  try {
    const { pathname } = window.location;
    const m = pathname.match(/^(.*?)(?:\/admin|\/api)\//);
    const basePrefix = m ? m[1] : '';
    const res = await fetch(basePrefix + '/api/auto-login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(payload)
    });
    const json = await res.json().catch(() => ({ raw: res.status + ' ' + res.statusText }));
    show(json);
  } catch (e) {
    show({ error: String(e) });
  } finally {
    btn.disabled = false; btn.textContent = original;
  }
}

el('btnRun').addEventListener('click', () => callAutoLogin(true));
el('btnStart').addEventListener('click', () => callAutoLogin(false));
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
