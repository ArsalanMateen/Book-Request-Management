<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();
require_once __DIR__ . '/../config/db.php';

$stats  = [];
$recent = [];

try {
    $db = getDB();

    $stats = $db->query(
        "SELECT
            COUNT(DISTINCT user_id)         AS unique_users,
            COUNT(*)                         AS total_requests,
            SUM(status = 'pending')          AS pending,
            SUM(status = 'in_progress')      AS in_progress,
            SUM(status = 'completed')        AS completed
         FROM book_requests"
    )->fetch();

    $recent = $db->query(
        "SELECT br.id, u.username, br.book_title, br.category, br.status, br.updated_at
         FROM book_requests br
         JOIN users u ON u.id = br.user_id
         ORDER BY br.updated_at DESC LIMIT 8"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Super dashboard error: ' . $e->getMessage());
}

$pageTitle = 'Super Admin Dashboard';
$navRole   = 'superadmin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="main-wrap">
  <div class="page-header">
    <div>
      <div class="page-title">Control Center</div>
      <div class="page-subtitle">Live operational overview and fast management actions</div>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-num"><?= (int)($stats['unique_users'] ?? 0) ?></div>
      <div class="stat-label">Unique Users</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= (int)($stats['total_requests'] ?? 0) ?></div>
      <div class="stat-label">Total Requests</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--accent2);"><?= (int)($stats['pending'] ?? 0) ?></div>
      <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:#3b82f6;"><?= (int)($stats['in_progress'] ?? 0) ?></div>
      <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--success);"><?= (int)($stats['completed'] ?? 0) ?></div>
      <div class="stat-label">Completed</div>
    </div>
  </div>

  <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.2rem;">
    <a href="<?= htmlspecialchars(app_url('superadmin/manage_requests.php')) ?>" class="btn btn-primary">Manage Requests</a>
    <a href="<?= htmlspecialchars(app_url('superadmin/manage_users.php')) ?>"    class="btn btn-warning">Manage Users</a>
    <a href="<?= htmlspecialchars(app_url('superadmin/manage_admins.php')) ?>"   class="btn btn-danger">Manage Admins</a>
  </div>

  <div class="card">
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:1rem;color:var(--muted);">
      Recent Requests
    </h2>
    <?php if (empty($recent)): ?>
      <p style="color:var(--muted);text-align:center;padding:1.5rem 0;">No requests yet.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>User</th><th>Book Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
              <td><?= e(displayUsername($r['username'])) ?></td>
              <td><?= e($r['book_title']) ?></td>
              <td><?= e(categoryLabel($r['category'])) ?></td>
              <td><?= statusBadge($r['status']) ?></td>
              <td style="color:var(--muted);font-size:.8rem;">
                <?= e(date('M d, Y H:i', strtotime($r['updated_at']))) ?>
              </td>
              <td>
                <a href="<?= htmlspecialchars(app_url('superadmin/manage_requests.php')) ?>" class="btn btn-sm btn-primary">Edit</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
