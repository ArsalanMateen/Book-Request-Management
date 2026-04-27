<?php
/**
 * fetch_books.php
 * Receives FormData POST with 'category', calls Google Books API,
 * inserts new books into DB, returns JSON list of books.
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Must be a logged-in user
if (!isUser()) {
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$db     = getDB();
$userId = $_SESSION['user_id'];

// Map category slug to API query
$categoryMap = [
    'app_development'    => 'web+development',
    'mobile_development' => 'mobile+development',
    'ai'                 => 'artificial+intelligence',
];

$category = trim($_POST['category'] ?? '');
if (!array_key_exists($category, $categoryMap)) {
    echo json_encode(['error' => 'Invalid category.']);
    exit;
}

// Rate limiting: max 5 API calls per user per 24 hours ---
try {
    $rateStmt = $db->prepare(
        'SELECT COUNT(*) as cnt FROM api_calls
         WHERE user_id = ? AND called_at > NOW() - INTERVAL 24 HOUR'
    );
    $rateStmt->execute([$userId]);
    $rateRow = $rateStmt->fetch();

    if ((int)$rateRow['cnt'] >= 5) {
        echo json_encode([
            'error'      => 'API rate limit reached. You can only fetch books 5 times per 24 hours.',
            'rate_limit' => true,
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log('Rate limit check error: ' . $e->getMessage());
}

//  Call Google Books API 
$query    = $categoryMap[$category];
$apiUrl   = "https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=20";

$context = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    // API unreachable - return books already in DB as fallback
    try {
        $fallback = $db->prepare('SELECT title, author FROM books WHERE category = ? ORDER BY title ASC');
        $fallback->execute([$category]);
        $books = $fallback->fetchAll();
        echo json_encode(['books' => $books, 'source' => 'cache']);
    } catch (PDOException $e) {
        error_log('Fallback fetch error: ' . $e->getMessage());
        echo json_encode(['error' => 'Could not reach Google Books API and no cached data found.']);
    }
    exit;
}

$data = json_decode($response, true);
if (!isset($data['items'])) {
    echo json_encode(['error' => 'No books found for this category.']);
    exit;
}

//  Log this API call 
try {
    $logStmt = $db->prepare('INSERT INTO api_calls (user_id) VALUES (?)');
    $logStmt->execute([$userId]);
} catch (PDOException $e) {
    error_log('API call log error: ' . $e->getMessage());
}

//  Insert books into DB (ignore duplicates) 
$insertedBooks = [];
try {
    $ins = $db->prepare(
        'INSERT IGNORE INTO books (title, author, category) VALUES (?, ?, ?)'
    );
    foreach ($data['items'] as $item) {
        $info   = $item['volumeInfo'] ?? [];
        $title  = trim($info['title'] ?? '');
        $authors = $info['authors'] ?? ['Unknown'];
        $author  = implode(', ', $authors);

        if (!$title) continue;

        $ins->execute([$title, $author, $category]);
        $insertedBooks[] = ['title' => $title, 'author' => $author];
    }
} catch (PDOException $e) {
    error_log('Book insert error: ' . $e->getMessage());
}

//  Return all books for this category from DB 
try {
    $sel = $db->prepare('SELECT title, author FROM books WHERE category = ? ORDER BY title ASC');
    $sel->execute([$category]);
    $books = $sel->fetchAll();
} catch (PDOException $e) {
    error_log('Books select error: ' . $e->getMessage());
    $books = $insertedBooks;
}

echo json_encode(['books' => $books, 'source' => 'api']);
