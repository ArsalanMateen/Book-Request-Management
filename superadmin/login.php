<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['admin_id']) && $_SESSION['role'] === 'superadmin') {
  app_redirect('superadmin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password =       $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, username, password, role FROM admins WHERE username = ? AND role = 'superadmin'");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['role']           = 'superadmin';
                session_regenerate_id(true);
              app_redirect('superadmin/dashboard.php');
            } else {
                $error = 'Invalid super admin credentials.';
            }
        } catch (PDOException $e) {
            error_log('Superadmin login error: ' . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}

$pageTitle = 'Super Admin Login';
require_once __DIR__ . '/../includes/header.php';
?>
<nav>
  <div class="nav-inner">
    <span class="nav-brand">BookRequest</span>
    <div class="nav-links">
      <a href="<?= htmlspecialchars(app_url('user/login.php')) ?>">User Login</a>
      <a href="<?= htmlspecialchars(app_url('admin/login.php')) ?>">Admin Login</a>
      <a href="<?= htmlspecialchars(app_url('superadmin/login.php')) ?>" class="active">Super Admin</a>
    </div>
  </div>
</nav>
<div class="main-wrap" style="display:flex;align-items:center;justify-content:center;min-height:80vh;">
  <div class="card" style="width:100%;max-width:420px;">
    <div style="text-align:center;margin-bottom:1.75rem;">
      <h1 style="font-size:1.5rem;font-weight:700;">Super Admin</h1>
      <p style="color:var(--muted);font-size:.875rem;margin-top:.25rem;">Full system control</p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Super admin username" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Super admin password" required/>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Super Admin Sign In</button>
    </form>
    <p style="text-align:center;margin-top:1.25rem;font-size:.8rem;color:var(--muted);">
      Default: superadmin / superadmin123
    </p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
