<?php
// ============================================================
// dashboard.php — CvSU Library Student Dashboard
// ============================================================
session_start();

// Guard: redirect to login if not authenticated

// ── Session values ────────────────────────────────────────────
$student_name   = htmlspecialchars($_SESSION['student_name']   ?? 'Juan Dela Cruz');
$first_name     = htmlspecialchars(explode(' ', trim($_SESSION['student_name'] ?? 'Juan Dela Cruz'))[0]);
$active_borrows = (int)($_SESSION['active_borrows']  ?? 0);
$has_fines      = (bool)($_SESSION['has_fines']       ?? false);

// ── TODO: replace these with real DB queries ─────────────────
// Example using PDO (requires $pdo from db_connect.php):
//
// require_once '../includes/db_connect.php';
// $stmt = $pdo->prepare("SELECT
//     COUNT(CASE WHEN status='active'   THEN 1 END) AS active,
//     COUNT(CASE WHEN status='returned' THEN 1 END) AS returned,
//     COUNT(CASE WHEN status='pending'  THEN 1 END) AS pending
//   FROM borrows WHERE student_id = ?");
// $stmt->execute([$_SESSION['student_id']]);
// $counts = $stmt->fetch(PDO::FETCH_ASSOC);
//
// $fine_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM fines
//   WHERE student_id=? AND status='unpaid'");
// $fine_stmt->execute([$_SESSION['student_id']]);
// $pending_fines = $fine_stmt->fetchColumn();

// Placeholder values (remove once DB is connected):
$counts        = ['active' => 2, 'returned' => 5, 'pending' => 1];
$pending_fines = 20;

// Currently borrowed books for the dashboard table
// TODO: $stmt = $pdo->prepare("SELECT b.*, bk.title, bk.author FROM borrows b
//   JOIN books bk ON b.book_id = bk.id WHERE b.student_id=? AND b.status='active' LIMIT 5");
// $stmt->execute([$_SESSION['student_id']]);
// $current_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_books = [
    ['title' => 'The Great Gatsby',       'author' => 'F. Scott Fitzgerald', 'due_date' => '2026-05-25', 'overdue' => false],
    ['title' => 'To Kill a Mockingbird',  'author' => 'Harper Lee',          'due_date' => '2026-05-18', 'overdue' => true],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
</head>
<body>

<?php require_once '../includes/sidebar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">Dashboard</span>
    <div class="topbar-spacer"></div>
    
    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>


  <main class="page-content">

    <div class="page-header">
      <h1>Welcome back, <em style="font-style:italic;color:var(--gold)"><?= $first_name ?></em></h1>
      <div class="gold-rule">
        <span></span><i>✦</i><span></span>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
          </svg>
        </div>
        <div class="stat-value"><?= $counts['active'] ?></div>
        <div class="stat-label">Books Borrowed</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="12 8 12 12 14 14"/>
            <path d="M3.05 11a9 9 0 1 0 .5-4"/>
            <polyline points="3 3 3.05 11 11 10.94"/>
          </svg>
        </div>
        <div class="stat-value"><?= $counts['returned'] ?></div>
        <div class="stat-label">Books Returned</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-danger">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <div class="stat-value">
          <?php if ($pending_fines > 0): ?>
            ₱<?= number_format($pending_fines) ?>
          <?php else: ?>
            None
          <?php endif; ?>
        </div>
        <div class="stat-label">Pending Fines</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-sage">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="stat-value"><?= $counts['pending'] ?></div>
        <div class="stat-label">Pending Requests</div>
      </div>
    </div>

    <div class="card" style="margin-top: 8px;">
      <div class="card-body">
        <div class="card-title">Currently Borrowed</div>
        <div class="card-subtitle">Books you have checked out right now</div>

        <?php if (empty($current_books)): ?>
          <p style="color:var(--muted);font-size:0.85rem;padding:16px 0;">You have no books currently borrowed.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Due Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($current_books as $book): ?>
                  <tr>
                    <td><?= htmlspecialchars($book['title']) ?></td>
                    <td><?= htmlspecialchars($book['author']) ?></td>
                    <td><?= date('M j, Y', strtotime($book['due_date'])) ?></td>
                    <td>
                      <?php if ($book['overdue']): ?>
                        <span class="badge badge-rust">Overdue</span>
                      <?php else: ?>
                        <span class="badge badge-sage">On Time</span>
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
<div class="toast" id="toast"></div>

<script>
  /* ── Mobile sidebar toggle (must stay JS — CSS/DOM interaction) ── */
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

  /* ── Toast helper ── */
  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (type ? ' ' + type : '');
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }
</script>

</body>
</html>