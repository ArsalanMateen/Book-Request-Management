<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['user_id'])) {
  app_redirect('user/dashboard.php');
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
            $stmt = $db->prepare('SELECT id, username, email, password FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['role']     = 'user';
                session_regenerate_id(true);
                app_redirect('user/dashboard.php');
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log('User login error: ' . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}

$pageTitle = 'User Login';
require_once __DIR__ . '/../includes/header.php';
?>
<nav>
  <div class="nav-inner">
    <span class="nav-brand">BookRequest</span>
    <div class="nav-links">
      <a href="<?= htmlspecialchars(app_url('user/login.php')) ?>" class="active">Login</a>
      <a href="<?= htmlspecialchars(app_url('user/register.php')) ?>">Register</a>
    </div>
  </div>
</nav>
<div class="main-wrap" style="display:flex;align-items:center;justify-content:center;min-height:80vh;">
  <div class="card" style="width:100%;max-width:420px;">
    <div style="text-align:center;margin-bottom:1.75rem;">
      <h1 style="font-size:1.5rem;font-weight:700;">Welcome Back</h1>
      <p style="color:var(--muted);font-size:.875rem;margin-top:.25rem;">Sign in to your account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Your username" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Your password" required/>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>

    <p style="text-align:center;margin-top:1.25rem;font-size:.875rem;color:var(--muted);">
      No account? <a href="<?= htmlspecialchars(app_url('user/register.php')) ?>">Register free</a>
    </p>
    <hr style="border-color:var(--border);margin:1.25rem 0;"/>
    <p style="text-align:center;font-size:.8rem;color:var(--muted);">
      Admin? <a href="<?= htmlspecialchars(app_url('admin/login.php')) ?>">Admin Login</a> &nbsp;|&nbsp;
      <a href="<?= htmlspecialchars(app_url('superadmin/login.php')) ?>">Super Admin Login</a>
    </p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
