<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../config/db.php';

// Already logged in as admin?
if (!empty($_SESSION['admin_id']) && in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'])) {
  app_redirect('admin/dashboard.php');
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
            $stmt = $db->prepare('SELECT id, username, password, role FROM admins WHERE username = ?');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['role']           = $admin['role'];
                session_regenerate_id(true);

                // Superadmin goes to their own panel
                if ($admin['role'] === 'superadmin') {
                  app_redirect('superadmin/dashboard.php');
                } else {
                  app_redirect('admin/dashboard.php');
                }
            } else {
                $error = 'Invalid admin credentials.';
            }
        } catch (PDOException $e) {
            error_log('Admin login error: ' . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}

$pageTitle = 'Admin Login';
require_once __DIR__ . '/../includes/header.php';
?>
<nav>
  <div class="nav-inner">
    <span class="nav-brand">BookRequest</span>
    <div class="nav-links">
      <a href="<?= htmlspecialchars(app_url('user/login.php')) ?>">User Login</a>
      <a href="<?= htmlspecialchars(app_url('admin/login.php')) ?>" class="active">Admin Login</a>
      <a href="<?= htmlspecialchars(app_url('superadmin/login.php')) ?>">Super Admin</a>
    </div>
  </div>
</nav>
<div class="main-wrap" style="display:flex;align-items:center;justify-content:center;min-height:80vh;">
  <div class="card" style="width:100%;max-width:420px;">
    <div style="text-align:center;margin-bottom:1.75rem;">
      <h1 style="font-size:1.5rem;font-weight:700;">Admin Login</h1>
      <p style="color:var(--muted);font-size:.875rem;margin-top:.25rem;">Restricted access - admins only</p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Admin username" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Admin password" required/>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Admin Sign In</button>
    </form>
    <p style="text-align:center;margin-top:1.25rem;font-size:.8rem;color:var(--muted);">
      Default: admin / admin123
    </p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
