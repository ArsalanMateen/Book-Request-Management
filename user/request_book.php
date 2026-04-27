<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireUser();

$db = getDB();
$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email    = $_SESSION['email'];

$error   = '';
$success = '';

// Handle form submit (book request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_title'])) {
    $category  = $_POST['category']   ?? '';
    $bookTitle = trim($_POST['book_title'] ?? '');

    $allowed = ['app_development', 'mobile_development', 'ai'];
    if (!in_array($category, $allowed) || !$bookTitle) {
        $error = 'Please select a category and a book.';
    } else {
        try {
            $ins = $db->prepare(
                'INSERT INTO book_requests (user_id, book_title, category, status) VALUES (?, ?, ?, "pending")'
            );
            $ins->execute([$userId, $bookTitle, $category]);
            $success = 'Your request has been submitted successfully!';
        } catch (PDOException $e) {
            error_log('Request insert error: ' . $e->getMessage());
            $error = 'Failed to submit request. Please try again.';
        }
    }
}

// Load books for a given category from DB (already fetched via API)
$selectedCat = htmlspecialchars($_GET['category'] ?? $_POST['category'] ?? '');
$books = [];
if ($selectedCat) {
    try {
        $stmt = $db->prepare('SELECT title, author FROM books WHERE category = ? ORDER BY title ASC');
        $stmt->execute([$selectedCat]);
        $books = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Books fetch error: ' . $e->getMessage());
    }
}

$pageTitle = 'Request a Book';
require_once __DIR__ . '/../includes/header.php';
?>
<nav>
  <div class="nav-inner">
    <span class="nav-brand">BookRequest</span>
    <div class="nav-links">
      <a href="<?= htmlspecialchars(app_url('user/dashboard.php')) ?>">My Requests</a>
      <a href="<?= htmlspecialchars(app_url('user/request_book.php')) ?>" class="active btn-nav">+ New Request</a>
      <a href="<?= htmlspecialchars(app_url('logout.php')) ?>" class="btn-danger-sm">Logout</a>
    </div>
  </div>
</nav>
<div class="main-wrap" style="max-width:720px;">
  <div class="page-header">
    <div>
      <div class="page-title">Request a Book</div>
      <div class="page-subtitle">Select a category to load books from Google Books API</div>
    </div>
    <a href="<?= htmlspecialchars(app_url('user/dashboard.php')) ?>" class="btn btn-secondary">Back</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="<?= htmlspecialchars(app_url('user/dashboard.php')) ?>">View Dashboard</a></div>
  <?php endif; ?>

  <div class="card">
    <!-- Step 1: Select category, JS fetches books from API -->
    <div class="form-group">
      <label for="categorySelect">1. Select Category</label>
      <select id="categorySelect">
        <option value="">-- Choose a category --</option>
        <option value="app_development"    <?= $selectedCat === 'app_development'    ? 'selected' : '' ?>>App Development</option>
        <option value="mobile_development" <?= $selectedCat === 'mobile_development' ? 'selected' : '' ?>>Mobile Development</option>
        <option value="ai"                 <?= $selectedCat === 'ai'                 ? 'selected' : '' ?>>Artificial Intelligence</option>
      </select>
    </div>

    <div id="apiStatus" style="display:none;" class="alert alert-info">Fetching books from Google Books API...</div>
    <div id="apiError"  style="display:none;" class="alert alert-warning"></div>
    <div id="rateLimitMsg" style="display:none;" class="alert alert-danger"></div>

    <!-- Step 2: Request form (shown after books are loaded) -->
    <form method="POST" action="" id="requestForm" style="<?= $selectedCat ? '' : 'display:none;' ?>">
      <input type="hidden" name="category" id="hiddenCategory" value="<?= $selectedCat ?>"/>

      <div class="form-group">
        <label for="usernameField">Username</label>
        <input type="text" id="usernameField" value="<?= e(displayUsername($username)) ?>" readonly/>
      </div>
      <div class="form-group">
        <label for="emailField">Email</label>
        <input type="email" id="emailField" value="<?= htmlspecialchars($email) ?>" readonly/>
      </div>
      <div class="form-group">
        <label for="categoryDisplay">Category</label>
        <input type="text" id="categoryDisplay" readonly/>
      </div>
      <div class="form-group">
        <label for="book_title">2. Select Book</label>
        <select name="book_title" id="bookSelect" required>
          <option value="">-- Choose a book --</option>
          <?php foreach ($books as $b): ?>
            <option value="<?= htmlspecialchars($b['title']) ?>"><?= htmlspecialchars($b['title']) ?> - <?= htmlspecialchars($b['author']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
    </form>
  </div>
</div>

<script>
const catLabels = {
  app_development: 'App Development',
  mobile_development: 'Mobile Development',
  ai: 'Artificial Intelligence'
};

const select = document.getElementById('categorySelect');
const form   = document.getElementById('requestForm');
const status = document.getElementById('apiStatus');
const errBox = document.getElementById('apiError');
const rateBox= document.getElementById('rateLimitMsg');
const bookSel= document.getElementById('bookSelect');
const hidCat = document.getElementById('hiddenCategory');
const catDisp= document.getElementById('categoryDisplay');

/**
 * On category change: POST to fetch_books.php via FormData,
 * which calls Google API and stores books in DB,
 * then repopulates the book dropdown.
 */
select.addEventListener('change', function () {
  const cat = this.value;
  if (!cat) { form.style.display = 'none'; return; }

  // Show loading
  status.style.display = 'block';
  errBox.style.display = 'none';
  rateBox.style.display = 'none';
  form.style.display = 'none';

  const fd = new FormData();
  fd.append('category', cat);

  fetch(<?= json_encode(app_url('api/fetch_books.php')) ?>, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      status.style.display = 'none';
      if (data.error) {
        if (data.rate_limit) {
          rateBox.style.display = 'block';
          rateBox.textContent = data.error;
        } else {
          errBox.style.display = 'block';
          errBox.textContent = data.error;
        }
        return;
      }
      // Populate book dropdown
      bookSel.innerHTML = '<option value="">-- Choose a book --</option>';
      (data.books || []).forEach(b => {
        const opt = document.createElement('option');
        opt.value = b.title;
        opt.textContent = b.title + (b.author ? ' - ' + b.author : '');
        bookSel.appendChild(opt);
      });
      hidCat.value = cat;
      catDisp.value = catLabels[cat] || cat;
      form.style.display = 'block';
    })
    .catch(() => {
      status.style.display = 'none';
      errBox.style.display = 'block';
      errBox.textContent = 'Could not connect to the server. Please try again.';
    });
});

// Init if category already selected (e.g. after form error)
if (select.value) {
  catDisp.value = catLabels[select.value] || select.value;
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
