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

//  Handle actions 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']   ?? '';
    $adminId = (int)($_POST['admin_id'] ?? 0);

    try {
        if ($action === 'add_admin') {
            $username = trim($_POST['new_username'] ?? '');
            $password =       $_POST['new_password'] ?? '';

            if (!$username || !$password) {
                $error = 'Username and password are required.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                $chk = $db->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
                $chk->execute([$username]);
                if ($chk->fetch()) {
                    $error = 'Admin username already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $db->prepare(
                        "INSERT INTO admins (username, password, role) VALUES (?, ?, 'admin')"
                    )->execute([$username, $hash]);
                    $message = "Admin '{$username}' added successfully.";
                }
            }

        } elseif ($action === 'delete_admin' && $adminId > 0) {
            // Prevent deleting the super admin
            $chk = $db->prepare("SELECT role FROM admins WHERE id = ? LIMIT 1");
            $chk->execute([$adminId]);
            $row = $chk->fetch();
            if (!$row) {
                $error = 'Admin not found.';
            } elseif ($row['role'] === 'superadmin') {
                $error = 'Cannot delete the Super Admin account.';
            } else {
                $db->prepare("DELETE FROM admins WHERE id = ? AND role = 'admin'")->execute([$adminId]);
                $message = 'Admin deleted successfully.';
            }
        }
    } catch (PDOException $e) {
        error_log('Manage admins error: ' . $e->getMessage());
        $error = 'Operation failed. Please try again.';
    }
}

//  Fetch all admins (excluding super admin from delete option) 
try {
    $admins = $db->query(
        "SELECT id, username, role, created_at FROM admins ORDER BY role DESC, created_at ASC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch admins error: ' . $e->getMessage());
    $admins = [];
}

$pageTitle = 'Manage Admins';
$navRole   = 'superadmin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <h1 class="page-title">Manage <span>Admins</span></h1>

  <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start;flex-wrap:wrap;">

    <!--  Admin List  -->
    <div class="card">
      <h2 style="font-size:1rem;font-weight:600;margin-bottom:1rem;color:var(--muted);">
        All Admins (<?= count($admins) ?>)
      </h2>
      <div class="table-wrap">
        <?php if (empty($admins)): ?>
          <p style="color:var(--muted);text-align:center;padding:1.5rem;">No admins found.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr><th>#</th><th>Username</th><th>Role</th><th>Added</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $i => $a): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= e($a['username']) ?></strong></td>
                <td>
                  <?php if ($a['role'] === 'superadmin'): ?>
                    <span style="background:rgba(239,68,68,.2);color:#f87171;padding:2px 10px;border-radius:12px;font-size:.75rem;font-weight:700;">
                      Super Admin
                    </span>
                  <?php else: ?>
                    <span style="background:rgba(245,158,11,.15);color:#fbbf24;padding:2px 10px;border-radius:12px;font-size:.75rem;font-weight:700;">
                      Admin
                    </span>
                  <?php endif; ?>
                </td>
                <td style="color:var(--muted);font-size:.8rem;">
                  <?= e(date('M d, Y', strtotime($a['created_at']))) ?>
                </td>
                <td>
                  <?php if ($a['role'] !== 'superadmin'): ?>
                    <form method="post"
                          onsubmit="return confirm('Delete admin <?= e($a['username']) ?>?')">
                      <input type="hidden" name="action"   value="delete_admin"/>
                      <input type="hidden" name="admin_id" value="<?= (int)$a['id'] ?>"/>
                      <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                    </form>
                  <?php else: ?>
                    <span style="color:var(--muted);font-size:.8rem;">Protected</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!--  Add Admin Form  -->
    <div class="card">
      <h2 style="font-size:1rem;font-weight:600;margin-bottom:1.25rem;color:var(--muted);">
        Add New Admin
      </h2>
      <form method="post">
        <input type="hidden" name="action" value="add_admin"/>
        <div class="form-group">
          <label for="new_username">Username</label>
          <input type="text" id="new_username" name="new_username"
                 value="<?= e($_POST['new_username'] ?? '') ?>"
                 placeholder="admin_username" required/>
        </div>
        <div class="form-group">
          <label for="new_password">Password</label>
          <input type="password" id="new_password" name="new_password"
                 placeholder="Min. 6 characters" required/>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
          Add Admin
        </button>
      </form>
    </div>

  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
