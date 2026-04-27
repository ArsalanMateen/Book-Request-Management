<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'book_request_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

 $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (basename($scriptName) === 'index.php' || basename($scriptName) === 'logout.php') {
    $appBaseUrl = rtrim(dirname($scriptName), '/');
} else {
    $appBaseUrl = rtrim(dirname(dirname($scriptName)), '/');
}

define('APP_BASE_URL', $appBaseUrl === '/' ? '' : $appBaseUrl);

function app_url(string $path = ''): string {
    $base = APP_BASE_URL === '/' ? '' : APP_BASE_URL;
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base;
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function app_redirect(string $path): void {
    header('Location: ' . app_url($path));
    exit;
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            die('Database connection failed. Please try again later.');
        }
    }
    return $pdo;
}
