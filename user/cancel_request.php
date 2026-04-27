<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireUser();

$db     = getDB();
$userId = $_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        // Only allow cancel if pending AND owned by this user
        $stmt = $db->prepare(
            'DELETE FROM book_requests WHERE id = ? AND user_id = ? AND status = "pending"'
        );
        $stmt->execute([$id, $userId]);
    } catch (PDOException $e) {
        error_log('Cancel error: ' . $e->getMessage());
    }
}

app_redirect('user/dashboard.php');
