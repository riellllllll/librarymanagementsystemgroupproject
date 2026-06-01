<?php
// request_borrow.php
session_start();
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/BookRequest.php';

$active_page = 'request';

$all_books = [
  ['id'=>1, 'title'=>'The Great Gatsby',         'author'=>'F. Scott Fitzgerald','category'=>'Fiction',     'copies'=>3,'available'=>2,'color'=>'color-a'],
  ['id'=>2, 'title'=>'To Kill a Mockingbird',    'author'=>'Harper Lee',          'category'=>'Fiction',     'copies'=>4,'available'=>1,'color'=>'color-b'],
  ['id'=>3, 'title'=>'A Brief History of Time',  'author'=>'Stephen Hawking',     'category'=>'Science',     'copies'=>2,'available'=>2,'color'=>'color-c'],
  ['id'=>4, 'title'=>'Sapiens',                  'author'=>'Yuval Noah Harari',   'category'=>'History',     'copies'=>3,'available'=>0,'color'=>'color-d'],
  ['id'=>5, 'title'=>'Clean Code',               'author'=>'Robert C. Martin',    'category'=>'Technology',  'copies'=>5,'available'=>4,'color'=>'color-e'],
  ['id'=>6, 'title'=>'1984',                     'author'=>'George Orwell',       'category'=>'Fiction',     'copies'=>3,'available'=>2,'color'=>'color-a'],
  ['id'=>7, 'title'=>'The Selfish Gene',         'author'=>'Richard Dawkins',     'category'=>'Science',     'copies'=>2,'available'=>1,'color'=>'color-b'],
  ['id'=>8, 'title'=>'Calculus Made Easy',       'author'=>'Silvanus P. Thompson','category'=>'Mathematics', 'copies'=>4,'available'=>3,'color'=>'color-c'],
  ['id'=>9, 'title'=>'Design Patterns',          'author'=>'GoF',                 'category'=>'Technology',  'copies'=>3,'available'=>3,'color'=>'color-d'],
  ['id'=>10,'title'=>'Noli Me Tangere',          'author'=>'Jose Rizal',          'category'=>'Literature',  'copies'=>6,'available'=>5,'color'=>'color-e'],
  ['id'=>11,'title'=>'El Filibusterismo',        'author'=>'Jose Rizal',          'category'=>'Literature',  'copies'=>5,'available'=>4,'color'=>'color-a'],
  ['id'=>12,'title'=>'Guns, Germs, and Steel',   'author'=>'Jared Diamond',       'category'=>'History',     'copies'=>2,'available'=>2,'color'=>'color-b'],
  ['id'=>13,'title'=>'The Pragmatic Programmer', 'author'=>'Andrew Hunt',         'category'=>'Technology',  'copies'=>3,'available'=>2,'color'=>'color-c'],
  ['id'=>14,'title'=>'Pride and Prejudice',      'author'=>'Jane Austen',         'category'=>'Literature',  'copies'=>4,'available'=>3,'color'=>'color-d'],
  ['id'=>15,'title'=>'Cosmos',                   'author'=>'Carl Sagan',          'category'=>'Science',     'copies'=>3,'available'=>1,'color'=>'color-e'],
  ['id'=>16,'title'=>'The Art of War',           'author'=>'Sun Tzu',             'category'=>'History',     'copies'=>4,'available'=>4,'color'=>'color-a'],
];

$book_id = (int)($_GET['book_id'] ?? 0);
$selected_book = null;
foreach ($all_books as $b) {
  if ($b['id'] === $book_id) { $selected_book = $b; break; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'create';

  // ── Cancel a pending request ──
  if ($action === 'cancel') {
    $cancel_id = (int)($_POST['request_id'] ?? 0);
    $result = $reqObj->cancel($cancel_id, $student_id);
    if ($result === true) {
      $_SESSION['flash_cancelled'] = true;
    } else {
      $_SESSION['flash_error'] = is_string($result) ? $result : 'Failed to cancel request.';
    }
  }
  // ── Create a new request ──
  else {
    $posted_book_id = (int)($_POST['book_id'] ?? 0);
    $result = $reqObj->create(
      $student_id,
      $posted_book_id,
      trim($_POST['borrow_date'] ?? '') ?: null,
      trim($_POST['return_date'] ?? '') ?: null
    );
    if ($result === true) {
      $_SESSION['flash_submitted'] = true;
    } else {
      $_SESSION['flash_error'] = is_string($result) ? $result : 'Failed to submit request.';
    }
  }

  // Post-Redirect-Get: prevent browser from resubmitting on refresh / back button
  header('Location: request_borrow.php');
  exit;
}

// Pull one-shot flash messages out of the session
$submitted = !empty($_SESSION['flash_submitted']);
$cancelled = !empty($_SESSION['flash_cancelled']);
$error     = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_submitted'], $_SESSION['flash_cancelled'], $_SESSION['flash_error']);

// ── Load student's own borrow requests (most recent first) ──
$my_requests = $reqObj->getByStudent($student_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Borrow — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    /* Original width — just add a bit more vertical padding for height */
    .page-content .request-card .card-body { padding: 40px 28px !important; }

    /* Two-line option layout with copies on right — !important to win specificity */
    .ts-dropdown .option .rb-option-row {
      display: flex !important;
      flex-direction: row !important;
      justify-content: space-between !important;
      align-items: center !important;
      gap: 14px !important;
      width: 100% !important;
    }
    .ts-dropdown .option .rb-option-text {
      flex: 1 1 auto !important;
      min-width: 0 !important;
    }
    .ts-dropdown .option .rb-option-text .ts-option-title {
      font-weight: 600;
      font-size: 0.92rem;
      color: var(--ink);
      margin-bottom: 2px;
    }
    .ts-dropdown .option .rb-option-text .ts-option-meta {
      font-size: 0.78rem;
      color: var(--muted);
    }
    .ts-dropdown .option .rb-option-copies {
      flex: 0 0 auto !important;
      display: inline-block !important;
      font-size: 0.72rem !important;
      font-weight: 600 !important;
      color: #16a34a !important;
      background: rgba(22, 163, 74, 0.12) !important;
      padding: 3px 10px !important;
      border-radius: 999px !important;
      white-space: nowrap !important;
    }
    .ts-dropdown .option:hover .rb-option-copies,
    .ts-dropdown .option.active .rb-option-copies {
      background: rgba(22, 163, 74, 0.18) !important;
    }
    .ts-dropdown .option.selected .rb-option-copies {
      background: rgba(255, 255, 255, 0.25) !important;
      color: #fff !important;
    }
  </style>
</head>
<body>

<?php require_once "../includes/sidebar.php"; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">

  <!-- TOP BAR -->
  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">Request Borrow</span>
    <div class="topbar-spacer"></div>
    <?php require_once '../includes/student_notifications.php'; ?>


    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>

  <!-- PAGE CONTENT -->
  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>Request to Borrow</h1>
      <p>Fill out the form below to request a book</p>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
    </div>

    <div class="form-container">
    <?php if ($submitted): ?>
    <div class="alert alert-sage" style="margin-bottom: 20px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Your borrow request has been submitted successfully! You will be notified once it is approved.
    </div>
    <?php endif; ?>

    <?php if ($cancelled): ?>
    <div class="alert alert-sage" style="margin-bottom: 20px;">
      Borrow request cancelled successfully.
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-rust" style="margin-bottom: 20px;">
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="request-card">
      <div class="card-body">
        <?php if ($selected_book): ?>
        <div class="book-preview">
          <div class="book-cover-mini">&#128214;</div>
          <div class="book-details">
            <h4><?= htmlspecialchars($selected_book['title']) ?></h4>
            <p>by <?= htmlspecialchars($selected_book['author']) ?> &middot; <?= htmlspecialchars($selected_book['category']) ?></p>
            <span class="badge badge-success"><?= $selected_book['available'] ?> of <?= $selected_book['copies'] ?> available</span>
          </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="request_borrow.php<?= $selected_book ? '?book_id='.$selected_book['id'] : '' ?>">
          <?php if (!$selected_book): ?>
          <div class="form-group">
            <label class="form-label">Select Book</label>
            <select name="book_id" id="book-select" class="form-control" required>
              <option value="">Choose a book...</option>
              <?php foreach ($all_books as $b): if ($b['available'] > 0): ?>
                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> | <?= htmlspecialchars($b['author']) ?> | <?= htmlspecialchars($b['category']) ?> | <?= (int)$b['available'] ?>/<?= (int)$b['copies'] ?> copies</option>
              <?php endif; endforeach; ?>
            </select>
          </div>
          <?php else: ?>
          <input type="hidden" name="book_id" value="<?= $selected_book['id'] ?>">
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Borrow Date</label>
              <input type="date"
                     name="borrow_date"
                     id="borrowDateInput"
                     class="form-control"
                     value="<?= date('Y-m-d') ?>"
                     min="<?= date('Y-m-d') ?>"
                     max="<?= date('Y-m-d', strtotime('+7 days')) ?>"
                     required>
              <small style="font-size:0.72rem;color:var(--muted);margin-top:4px;display:block;">You can borrow within the next 7 days.</small>
            </div>
            <div class="form-group">
              <label class="form-label">Return Date</label>
              <input type="date"
                     name="return_date"
                     id="returnDateInput"
                     class="form-control"
                     value="<?= date('Y-m-d', strtotime('+7 days')) ?>"
                     min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                     max="<?= date('Y-m-d', strtotime('+14 days')) ?>"
                     required>
              <small style="font-size:0.72rem;color:var(--muted);margin-top:4px;display:block;">Up to 7 days after the borrow date.</small>
            </div>
          </div>

          <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
              </svg>
              Submit Request
            </button>
            <a href="view_books.php" class="btn btn-outline">Cancel</a>
          </div>
        </form>
      </div>
    </div>
    </div>
    <!-- ↑ form-container closes here so the table below spans full width -->

    <!-- ── My Borrow Requests (full width) ── -->
    <div class="card" style="margin-top:2.5rem;">
      <div class="card-body">
        <div class="card-title">My Borrow Requests</div>
        <p class="card-subtitle" style="margin-bottom:14px;">Track the status of every borrow request you've submitted.</p>

        <?php if (empty($my_requests)): ?>
          <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No requests yet</h3>
            <p>Submit a borrow request above and it will appear here.</p>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table style="width:100%;">
              <thead>
                <tr>
                  <th>Book</th>
                  <th>Author</th>
                  <th>Date Requested</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($my_requests as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['book_title']) ?></td>
                    <td><?= htmlspecialchars($r['author'] ?? '') ?></td>
                    <td style="white-space:nowrap;"><?= date('M j, Y g:i A', strtotime($r['request_date'])) ?></td>
                    <td>
                      <?php
                        $s = $r['status'];
                        $cls = match($s) {
                          'approved' => 'badge-sage',
                          'rejected' => 'badge-rust',
                          default    => 'badge-gold',
                        };
                        $lbl = strtoupper($s);
                      ?>
                      <span class="badge <?= $cls ?>"><?= $lbl ?></span>
                    </td>
                    <td>
                      <?php if ($r['status'] === 'pending'): ?>
                        <form method="POST" action="request_borrow.php" style="margin:0;"
                          onsubmit="return confirm('Cancel this borrow request?');">
                          <input type="hidden" name="action"     value="cancel">
                          <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                        </form>
                      <?php else: ?>
                        <span style="color:var(--muted);font-size:0.78rem;">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </main>

</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var bookSelect = document.getElementById('book-select');
    if (bookSelect) {
      new TomSelect(bookSelect, {
        create: false,
        sortField: {
          field: "text",
          direction: "asc"
        },
        placeholder: "Search or choose a book...",
        searchField: ['text'],
        maxOptions: null,
        dropdownClass: 'ts-dropdown',
        optionClass: 'ts-option',
        render: {
          option: function(data, escape) {
            var parts = data.text.split(' | ');
            var title = parts[0] || '';
            var author = parts[1] || '';
            var category = parts[2] || '';
            var copies = parts[3] || '';
            return '<div class="rb-option-row" style="display:flex;flex-direction:row;justify-content:space-between;align-items:center;gap:14px;width:100%;">' +
                     '<div class="rb-option-text" style="flex:1 1 auto;min-width:0;">' +
                       '<div class="ts-option-title" style="font-weight:600;font-size:0.92rem;margin-bottom:2px;">' + escape(title) + '</div>' +
                       '<div class="ts-option-meta" style="font-size:0.78rem;color:#8a8a8a;">' + escape(author) + (author && category ? ' &middot; ' : '') + escape(category) + '</div>' +
                     '</div>' +
                     (copies ? '<span class="rb-option-copies" style="flex:0 0 auto;display:inline-block;font-size:0.72rem;font-weight:600;color:#16a34a;background:rgba(22,163,74,0.12);padding:3px 10px;border-radius:999px;white-space:nowrap;">' + escape(copies) + '</span>' : '') +
                   '</div>';
          },
          item: function(data, escape) {
            var parts = data.text.split(' | ');
            return '<div>' + escape(parts[0] || data.text) + '</div>';
          },
          no_results: function(data, escape) {
            return '<div class="ts-no-results">No books found matching "' + escape(data.input) + '"</div>';
          }
        }
      });
    }
  });

  function checkMobile() {
    const toggle = document.getElementById('menuToggle');
    if (window.innerWidth <= 768) {
      toggle.style.display = 'flex';
    } else {
      toggle.style.display = 'none';
      document.getElementById('sidebar').classList.remove('open');
    }
  }
  checkMobile();
  window.addEventListener('resize', checkMobile);
  document.getElementById('menuToggle').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('open');
  });
  const currentPage = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-link').forEach(link => {
    link.classList.remove('active');
    const href = link.getAttribute('href');
    if (href === currentPage) {
      link.classList.add('active');
    }
  });
  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (type ? ' ' + type : '');
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  /* ── Dynamic borrow/return date constraints
     - Borrow date: today → today + 7 days
     - Return date: must be after borrow date, max = borrow date + 7 days  ── */
  (function linkBorrowReturnDates() {
    const borrowInput = document.getElementById('borrowDateInput');
    const returnInput = document.getElementById('returnDateInput');
    if (!borrowInput || !returnInput) return;

    function addDays(dateStr, days) {
      const d = new Date(dateStr + 'T00:00:00');
      d.setDate(d.getDate() + days);
      return d.toISOString().slice(0, 10);
    }

    function syncReturnConstraints() {
      const borrowVal = borrowInput.value;
      if (!borrowVal) return;
      const minReturn = addDays(borrowVal, 1);
      const maxReturn = addDays(borrowVal, 7);
      returnInput.min = minReturn;
      returnInput.max = maxReturn;
      // If return date now falls outside the new range, snap to default (+7)
      if (returnInput.value < minReturn || returnInput.value > maxReturn) {
        returnInput.value = maxReturn;
      }
    }

    borrowInput.addEventListener('change', syncReturnConstraints);
    syncReturnConstraints(); // run once on page load
  })();
</script>

</body>
</html>