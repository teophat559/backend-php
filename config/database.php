<?php
// ========================================
// DATABASE CONFIGURATION - SECURITY WARNING
// ========================================
// This file contains sensitive database credentials
// Keep this file secure and do not commit to public repositories
// Change default credentials immediately after installation

require_once __DIR__ . '/env.php';

// Database configuration via environment
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'specialprogram2025'));
define('DB_USER', env('DB_USER', 'specialprogram'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_TYPE', env('DB_TYPE', 'mysql')); // mysql or sqlite

// Database connection with enhanced security
try {
    if (DB_TYPE === 'sqlite') {
        // SQLite connection for development/testing
        $dbPath = __DIR__ . '/../storage/' . DB_NAME;
        if (!is_dir(dirname($dbPath))) {
            mkdir(dirname($dbPath), 0755, true);
        }
        $pdo = new PDO(
            "sqlite:$dbPath",
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } else {
        // MySQL connection
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => false, // Disable persistent connections for security
            ]
        );

        // Set session timeout for database connections
        $pdo->exec("SET SESSION wait_timeout = 300");
        $pdo->exec("SET SESSION interactive_timeout = 300");
    }

} catch (PDOException $e) {
    // Log error securely without exposing details
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Error: " . $e->getMessage() . ". Please check your configuration.");
}

// Automatically create tables after successful connection
createTables($pdo);

// Create tables if they don't exist
function createTables($pdo) {
    $isMySQL = DB_TYPE === 'mysql';
    $autoIncrement = $isMySQL ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
    $engine = $isMySQL ? 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY $autoIncrement,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            avatar_url VARCHAR(255),
            status VARCHAR(20) DEFAULT 'active',
            role VARCHAR(20) DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $engine
    ");

    // Create indexes for users table
    if (!$isMySQL) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_username ON users(username)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_email ON users(email)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_status ON users(status)");
    }

    // Contests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contests (
            id INTEGER PRIMARY KEY $autoIncrement,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            banner_url VARCHAR(255),
            start_date DATE,
            end_date DATE,
            status VARCHAR(20) DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $engine
    ");

    if (!$isMySQL) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contest_status ON contests(status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contest_dates ON contests(start_date, end_date)");
    }

    // Contestants table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contestants (
            id INTEGER PRIMARY KEY $autoIncrement,
            contest_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            total_votes INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $engine
    ");

    if (!$isMySQL) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contest_id ON contestants(contest_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_total_votes ON contestants(total_votes)");
    }

    // Votes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS votes (
            id INTEGER PRIMARY KEY $autoIncrement,
            contestant_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $engine
    ");

    if (!$isMySQL) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contestant_id ON votes(contestant_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_id ON votes(user_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON votes(created_at)");
    }

    // Settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY $autoIncrement,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $engine
    ");

    if (!$isMySQL) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_setting_key ON settings(setting_key)");
    }
}

// Database utility functions
function sanitizeDatabaseInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateDatabaseConnection($pdo) {
    try {
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function backupDatabase($pdo, $backup_path) {
    $command = sprintf(
        'mysqldump -h %s -u %s -p%s %s > %s',
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        $backup_path
    );

    return exec($command);
}

// ========================================
// END OF DATABASE CONFIGURATION
// ========================================
?>
