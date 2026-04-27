<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireUser();

$db = getDB();
$userId = $_SESSION['user_id'];

// Fetch all requests for this user, ordered newest first
try {
    $stmt = $db->prepare(
        'SELECT id, book_title, category, status, notified, updated_at
         FROM book_requests WHERE user_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$userId]);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Dashboard fetch error: ' . $e->getMessage());
    $requests = [];
}

// Collect notifications: rows where status changed and user not yet notified
$notifications = [];
foreach ($requests as $req) {
    if ($req['notified'] == 0 && $req['status'] !== 'pending') {
        $label = $req['status'] === 'in_progress' ? 'In Progress' : 'Completed';
        $notifications[] = "Your request for \"" . htmlspecialchars($req['book_title']) . "\" is now {$label}.";
        // Mark as notified
        try {
            $upd = $db->prepare('UPDATE book_requests SET notified = 1 WHERE id = ?');
            $upd->execute([$req['id']]);
        } catch (PDOException $e) {
            error_log('Notify update error: ' . $e->getMessage());
        }
    }
}

// Category labels
$catLabels = ['app_development' => 'App Development', 'mobile_development' => 'Mobile Development', 'ai' => 'AI'];

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<nav>
  <div class="nav-inner">
    <span class="nav-brand">BookRequest</span>
    <div class="nav-links">
      <a href="<?= htmlspecialchars(app_url('user/dashboard.php')) ?>" class="active">My Requests</a>
      <a href="<?= htmlspecialchars(app_url('user/request_book.php')) ?>" class="btn-nav">+ New Request</a>
      <a href="<?= htmlspecialchars(app_url('logout.php')) ?>" class="btn-danger-sm">Logout</a>
    </div>
  </div>
</nav>
<div class="main-wrap">
  <div class="page-header">
    <div>
      <div class="page-title">Welcome, <?= e(displayUsername($_SESSION['username'])) ?></div>
      <div class="page-subtitle">Here are all your book requests</div>
    </div>
    <a href="<?= htmlspecialchars(app_url('user/request_book.php')) ?>" class="btn btn-primary">+ Request a Book</a>
  </div>

  <?php foreach ($notifications as $note): ?>
    <div class="alert alert-info"><?= $note ?></div>
  <?php endforeach; ?>

  <?php if (empty($requests)): ?>
    <div class="card" style="text-align:center;padding:3rem;">
      <h2 style="font-size:1.2rem;margin-bottom:.5rem;">No requests yet</h2>
      <p style="color:var(--muted);margin-bottom:1.5rem;">Start by requesting your first book.</p>
      <a href="<?= htmlspecialchars(app_url('user/request_book.php')) ?>" class="btn btn-primary">Request a Book</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Book Title</th>
            <th>Category</th>
            <th>Status</th>
            <th>Last Updated</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $i => $req): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($req['book_title']) ?></td>
              <td><?= htmlspecialchars($catLabels[$req['category']] ?? $req['category']) ?></td>
              <td>
                <?php if ($req['status'] === 'pending'): ?>
                  <span class="badge badge-pending">Pending</span>
                <?php elseif ($req['status'] === 'in_progress'): ?>
                  <span class="badge badge-progress">In Progress</span>
                <?php else: ?>
                  <span class="badge badge-completed">Completed</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--muted);font-size:.8rem;"><?= htmlspecialchars($req['updated_at']) ?></td>
              <td>
                <?php if ($req['status'] === 'pending'): ?>
                  <a href="<?= htmlspecialchars(app_url('user/cancel_request.php?id=' . (int)$req['id'])) ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Cancel this request?')">Cancel</a>
                <?php else: ?>
                  <span style="color:var(--muted);font-size:.8rem;">N/A</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
