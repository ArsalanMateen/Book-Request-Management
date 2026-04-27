<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../config/db.php';

// Already logged in? Go to dashboard
if (!empty($_SESSION['user_id'])) {
  app_redirect('user/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = trim(htmlspecialchars($_POST['username'] ?? ''));
    $email    = trim(htmlspecialchars($_POST['email']    ?? ''));
    $password =       $_POST['password'] ?? '';

    // Basic validation
    if (!$username || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $db = getDB();
            // Check uniqueness
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already taken.';
            } else {
                // Hash password & insert
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins  = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
                $ins->execute([$username, $email, $hash]);
                $success = 'Account created! You can now log in.';
                session_regenerate_id(true);
            }
        } catch (PDOException $e) {
            error_log('Register error: ' . $e->getMessage());
            $error = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>
<nav>
  <div class="nav-inner">
    <span class="nav-brand">BookRequest</span>
    <div class="nav-links">
      <a href="<?= htmlspecialchars(app_url('user/login.php')) ?>">Login</a>
      <a href="<?= htmlspecialchars(app_url('user/register.php')) ?>" class="active">Register</a>
    </div>
  </div>
</nav>
<div class="main-wrap" style="display:flex;align-items:center;justify-content:center;min-height:80vh;">
  <div class="card" style="width:100%;max-width:460px;">
    <div style="text-align:center;margin-bottom:1.75rem;">
      <h1 style="font-size:1.5rem;font-weight:700;">Create Account</h1>
      <p style="color:var(--muted);font-size:.875rem;margin-top:.25rem;">Join and start requesting books</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="<?= htmlspecialchars(app_url('user/login.php')) ?>">Sign in</a></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="yourname" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@email.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Min. 6 characters" required/>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>
    <p style="text-align:center;margin-top:1.25rem;font-size:.875rem;color:var(--muted);">
      Already have an account? <a href="<?= htmlspecialchars(app_url('user/login.php')) ?>">Sign in</a>
    </p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
