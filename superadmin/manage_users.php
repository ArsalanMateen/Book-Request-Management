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
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    try {
        if ($action === 'reset_password' && $userId > 0) {
            // Generate a secure random password and hash it
            $newPass = bin2hex(random_bytes(6));          // 12-char hex string
            $hash    = password_hash($newPass, PASSWORD_BCRYPT);
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
            $message = "Password reset. New temporary password: <strong>" . e($newPass) . "</strong>";

        } elseif ($action === 'delete_user' && $userId > 0) {
            // ON DELETE CASCADE removes their requests automatically
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            $message = 'User account deleted.';
        }
    } catch (PDOException $e) {
        error_log('Manage users error: ' . $e->getMessage());
        $error = 'Operation failed. Please try again.';
    }
}

try {
    $users = $db->query(
        "SELECT u.id, u.username, u.email, u.created_at,
                COUNT(br.id) AS total_requests
         FROM users u
         LEFT JOIN book_requests br ON br.user_id = u.id
         GROUP BY u.id
         ORDER BY u.created_at DESC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch users error: ' . $e->getMessage());
    $users = [];
}

$pageTitle = 'Manage Users';
$navRole   = 'superadmin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="flex-between">
    <h1 class="page-title">Manage <span>Users</span></h1>
    <span style="color:var(--muted);font-size:.875rem;"><?= count($users) ?> registered</span>
  </div>

  <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="table-wrap">
      <?php if (empty($users)): ?>
        <p style="color:var(--muted);text-align:center;padding:2rem 0;">No registered users.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th><th>Username</th><th>Email</th>
              <th>Requests</th><th>Joined</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e(displayUsername($u['username'])) ?></strong></td>
              <td style="color:var(--muted);"><?= e($u['email']) ?></td>
              <td style="text-align:center;"><?= (int)$u['total_requests'] ?></td>
              <td style="color:var(--muted);font-size:.8rem;">
                <?= e(date('M d, Y', strtotime($u['created_at']))) ?>
              </td>
              <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
                <!-- Reset password -->
                <form method="post"
                      onsubmit="return confirm('Reset password for <?= e(displayUsername($u['username'])) ?>?')">
                  <input type="hidden" name="action"  value="reset_password"/>
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"/>
                  <button class="btn btn-warning btn-sm" type="submit">Reset Password</button>
                </form>
                <!-- Delete user -->
                <form method="post"
                      onsubmit="return confirm('Permanently delete <?= e(displayUsername($u['username'])) ?> and all their data?')">
                  <input type="hidden" name="action"  value="delete_user"/>
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"/>
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
