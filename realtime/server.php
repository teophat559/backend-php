<?php
// Minimal Ratchet WebSocket server for realtime status updates
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\App as RatchetApp;
use React\EventLoop\Loop;
use React\EventLoop\Factory as LoopFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/env.php';

@set_time_limit(0);
error_reporting(E_ALL);

class WsHub implements MessageComponentInterface {
	/** @var \SplObjectStorage<ConnectionInterface> */
	private $clients;
	public function __construct() {
		$this->clients = new \SplObjectStorage();
	}
	public function onOpen(ConnectionInterface $conn) {
		$this->clients->attach($conn);
	}
	public function onClose(ConnectionInterface $conn) {
		$this->clients->detach($conn);
	}
	public function onError(ConnectionInterface $conn, \Exception $e) {
		$conn->close();
	}
	public function onMessage(ConnectionInterface $from, $msg) {
		// Echo/broadcast messages (JSON contract preserved by clients)
		foreach ($this->clients as $client) {
			$client->send($msg);
		}
	}
	public function broadcast(array $data): void {
		$payload = json_encode($data);
		foreach ($this->clients as $client) {
			try { $client->send($payload); } catch (\Throwable $e) { /* ignore */ }
		}
	}
}

$host = env('WS_HOST', '127.0.0.1');
$port = (int) env('WS_PORT', 8090);

// Single shared hub instance for both WS and HTTP bridge
// Create/react loop explicitly for periodic tasks compatibility
$loop = class_exists('React\\EventLoop\\Loop') ? Loop::get() : LoopFactory::create();

// Start WebSocket server bound to the loop
$app = new RatchetApp($host, $port, '0.0.0.0', $loop);
$hub = new WsHub();
$app->route('/ws', $hub, ['*']);

// File-based queue: other PHP processes append JSON lines to storage/ws_queue.jsonl
$queueDir = __DIR__ . '/../storage';
if (!is_dir($queueDir)) { @mkdir($queueDir, 0777, true); }
$queueFile = $queueDir . '/ws_queue.jsonl';
if (!file_exists($queueFile)) { @touch($queueFile); }

// Periodically drain queue and broadcast
$loop->addPeriodicTimer(0.5, function () use ($queueFile, $hub) {
	$fh = @fopen($queueFile, 'c+');
	if (!$fh) { return; }
	if (!flock($fh, LOCK_EX)) { fclose($fh); return; }
	$size = filesize($queueFile);
	if ($size > 0) {
		$content = stream_get_contents($fh);
		// Truncate file
		ftruncate($fh, 0);
		fflush($fh);
		flock($fh, LOCK_UN);
		fclose($fh);
		$lines = preg_split('/\r?\n/', trim($content));
		foreach ($lines as $line) {
			if ($line === '') continue;
			$data = json_decode($line, true);
			if (is_array($data)) {
				$hub->broadcast($data);
			}
		}
	} else {
		flock($fh, LOCK_UN);
		fclose($fh);
	}
});

// Run server
$app->run();
