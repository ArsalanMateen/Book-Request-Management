<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();
require_once __DIR__ . '/../config/db.php';

$db      = getDB();
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);

    try {
        if ($action === 'update_status' && $requestId > 0) {
            $newStatus = $_POST['status'] ?? '';
            $allowed   = ['pending', 'in_progress', 'completed'];
            if (!in_array($newStatus, $allowed, true)) throw new Exception('Invalid status.');

            $db->prepare(
                'UPDATE book_requests SET status = ?, notified = 0 WHERE id = ?'
            )->execute([$newStatus, $requestId]);
            $message = 'Request status updated successfully.';

        } elseif ($action === 'delete' && $requestId > 0) {
            $db->prepare('DELETE FROM book_requests WHERE id = ?')->execute([$requestId]);
            $message = 'Request deleted.';
        }
    } catch (Exception $e) {
        error_log('Manage requests error: ' . $e->getMessage());
        $error = 'Operation failed. Please try again.';
    }
}

try {
    $requests = $db->query(
        "SELECT br.id, u.username, u.email, br.book_title, br.category, br.status, br.created_at, br.updated_at
         FROM book_requests br
         JOIN users u ON u.id = br.user_id
         ORDER BY br.updated_at DESC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch requests error: ' . $e->getMessage());
    $requests = [];
}

$pageTitle = 'Manage Requests';
$navRole   = 'superadmin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="flex-between">
    <h1 class="page-title">Manage <span>Requests</span></h1>
    <span style="color:var(--muted);font-size:.875rem;"><?= count($requests) ?> total</span>
  </div>

  <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="table-wrap">
      <?php if (empty($requests)): ?>
        <p style="color:var(--muted);text-align:center;padding:2rem 0;">No book requests found.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th><th>User</th><th>Email</th><th>Book Title</th>
              <th>Category</th><th>Status</th><th>Submitted</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $i => $r): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= e(displayUsername($r['username'])) ?></td>
              <td style="color:var(--muted);font-size:.8rem;"><?= e($r['email']) ?></td>
              <td><?= e($r['book_title']) ?></td>
              <td><?= e(categoryLabel($r['category'])) ?></td>
              <td><?= statusBadge($r['status']) ?></td>
              <td style="color:var(--muted);font-size:.8rem;">
                <?= e(date('M d, Y', strtotime($r['created_at']))) ?>
              </td>
              <td>
                <!-- Status update form -->
                <form method="post" style="display:inline-flex;gap:.4rem;align-items:center;">
                  <input type="hidden" name="action"     value="update_status"/>
                  <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>"/>
                  <select name="status" class="select-compact">
                    <?php foreach (['pending','in_progress','completed'] as $s): ?>
                      <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>>
                        <?= e(str_replace('_', ' ', ucfirst($s))) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-success btn-sm" type="submit">Save</button>
                </form>
                <!-- Delete form -->
                <form method="post" style="display:inline;"
                      onsubmit="return confirm('Delete this request permanently?')">
                  <input type="hidden" name="action"     value="delete"/>
                  <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>"/>
                  <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
