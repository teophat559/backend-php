<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
require_once __DIR__ . '/../../includes/admin-security.php';

requireAdminKey();

$page_title = 'Live Events';
include '../../includes/header.php';
?>

<div class="bg-gray-800 border-b border-gray-700 mb-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white">Live Events</h1>
        <p class="text-gray-400 mt-1">Theo dõi sự kiện realtime: bình chọn, đăng nhập</p>
      </div>
    </div>
  </div>
  </div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-gray-800 rounded-lg border border-gray-700 p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold text-white">Event Stream</h2>
        <button id="clearBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded">Xóa</button>
      </div>
      <div id="events" class="h-[480px] overflow-auto bg-gray-900 rounded p-3 text-sm"></div>
    </div>
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-4">
      <h2 class="text-xl font-semibold text-white mb-3">Thống kê</h2>
      <ul class="space-y-2 text-gray-300 text-sm">
        <li>Votes: <span id="statVotes">0</span></li>
        <li>Auth Approved: <span id="statApproved">0</span></li>
        <li>Auth Success: <span id="statSuccess">0</span></li>
        <li>Tổng: <span id="statTotal">0</span></li>
      </ul>
    </div>
  </div>
</div>

<script>
(function(){
  var url = window.__makeWsUrl ? window.__makeWsUrl('/ws') : null;
  if (!url) return;
  var ws = new WebSocket(url);
  var box = document.getElementById('events');
  var vote = 0, appr = 0, succ = 0, total = 0;
  function addLine(obj){
    total++;
    var el = document.createElement('div');
    el.className = 'mb-2 text-gray-200';
    el.textContent = new Date().toLocaleTimeString() + ' - ' + JSON.stringify(obj);
    box.appendChild(el);
    box.scrollTop = box.scrollHeight;
    document.getElementById('statVotes').textContent = vote;
    document.getElementById('statApproved').textContent = appr;
    document.getElementById('statSuccess').textContent = succ;
    document.getElementById('statTotal').textContent = total;
  }
  ws.onmessage = function(ev){
    try{
      var msg = JSON.parse(ev.data);
      if (!msg || !msg.type) return;
      if (msg.type === 'vote:created') vote++;
      if (msg.type === 'auth:approved') appr++;
      if (msg.type === 'auth:success') succ++;
      addLine(msg);
    }catch(e){}
  };
  document.getElementById('clearBtn').onclick = function(){ box.innerHTML=''; };
})();
</script>

<?php include '../../includes/footer.php'; ?>
