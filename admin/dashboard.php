<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$db = getDB();

// Aggregate statistics
try {
    $stats = [];

    $r = $db->query('SELECT COUNT(DISTINCT user_id) as cnt FROM book_requests');
    $stats['unique_users'] = $r->fetchColumn();

    $r = $db->query('SELECT COUNT(*) FROM book_requests');
    $stats['total_requests'] = $r->fetchColumn();

    $r = $db->query('SELECT COUNT(*) FROM book_requests WHERE status = "in_progress"');
    $stats['in_progress'] = $r->fetchColumn();

    $r = $db->query('SELECT COUNT(*) FROM book_requests WHERE status = "completed"');
    $stats['completed'] = $r->fetchColumn();

    // Recent requests overview
    $recent = $db->query(
        'SELECT br.id, u.username, br.book_title, br.category, br.status, br.created_at
         FROM book_requests br JOIN users u ON u.id = br.user_id
         ORDER BY br.created_at DESC LIMIT 20'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
    $stats  = ['unique_users' => 0, 'total_requests' => 0, 'in_progress' => 0, 'completed' => 0];
    $recent = [];
}

$catLabels = ['app_development' => 'App Dev', 'mobile_development' => 'Mobile Dev', 'ai' => 'AI'];
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<nav>
  <div class="nav-inner">
    <span class="nav-brand">Admin Panel</span>
    <div class="nav-links">
      <a href="<?= htmlspecialchars(app_url('admin/dashboard.php')) ?>" class="active">Dashboard</a>
      <span style="color:var(--muted);font-size:.875rem;">
        <?= htmlspecialchars($_SESSION['admin_username']) ?>
        <span class="badge badge-admin" style="margin-left:.4rem;">Admin</span>
      </span>
      <a href="<?= htmlspecialchars(app_url('logout.php')) ?>" class="btn-danger-sm">Logout</a>
    </div>
  </div>
</nav>
<div class="main-wrap">
  <div class="page-header">
    <div>
      <div class="page-title">Admin Dashboard</div>
      <div class="page-subtitle">Read-only statistics overview</div>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-num" style="color:var(--accent2);"><?= (int)$stats['unique_users'] ?></div>
      <div class="stat-label">Total Unique Users</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--info);"><?= (int)$stats['total_requests'] ?></div>
      <div class="stat-label">Total Book Requests</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--warning);"><?= (int)$stats['in_progress'] ?></div>
      <div class="stat-label">Requests In Progress</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--success);"><?= (int)$stats['completed'] ?></div>
      <div class="stat-label">Completed Requests</div>
    </div>
  </div>

  <!-- Recent Requests Table -->
  <div style="margin-bottom:1rem;font-weight:600;">Recent Book Requests</div>
  <?php if (empty($recent)): ?>
    <div class="alert alert-info">No book requests in the system yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>User</th><th>Book Title</th><th>Category</th><th>Status</th><th>Submitted</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $i => $req): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= e(displayUsername($req['username'])) ?></td>
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
              <td style="color:var(--muted);font-size:.8rem;"><?= htmlspecialchars($req['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
