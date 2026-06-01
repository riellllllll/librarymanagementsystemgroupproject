<?php
// ============================================================
// borrow_history.php — CvSU Library Borrow History (DB-powered)
// ============================================================
session_start();
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../classes/BorrowRecord.php';

$borrow = new BorrowRecord($conn);
$borrow->updateOverdueStatuses();

$has_fines = (bool)($_SESSION['has_fines'] ?? false);

// ── Load from DB & map to UI shape ──
$db_rows = $borrow->getByStudent($student_id);
$history = [];
foreach ($db_rows as $r) {
    $history[] = [
        'id'          => (int)$r['id'],
        'title'       => $r['book_title'],
        'author'      => $r['author'],
        'borrow_date' => $r['borrow_date'],
        'due_date'    => $r['due_date'],
        'return_date' => $r['return_date'],
        // UI uses: 'active' | 'overdue' | 'returned'
        'status'      => in_array($r['status'], ['pending_return']) ? 'active' : $r['status'],
        'fine_amount' => !empty($r['fine_amount']) ? (float)$r['fine_amount'] : null,
        'fine_status' => $r['paid_status'] ?? null,
    ];
}

// ── Compute summary stats ──
$total_count    = count($history);
$returned_count = count(array_filter($history, fn($e) => $e['status'] === 'returned'));
$active_count   = count(array_filter($history, fn($e) => $e['status'] === 'active'));
$overdue_count  = count(array_filter($history, fn($e) => $e['status'] === 'overdue'));
$fined_count    = count(array_filter($history, fn($e) => !empty($e['fine_amount'])));

// ── Group by month ──
$grouped = [];
foreach ($history as $entry) {
    $month_key = date('F Y', strtotime($entry['borrow_date']));
    $grouped[$month_key][] = $entry;
}

// ── Helper ──
function days_late(string $due, ?string $returned): int {
    if (!$returned) return 0;
    $diff = (new DateTime($returned))->diff(new DateTime($due));
    return $diff->invert ? $diff->days : 0;  // invert=1 means returned > due
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Borrow History — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/borrow_history.css">
</head>
<body>
  
<!-- ============================================================
     SIDEBAR
     ============================================================ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="6"  y="8"  width="8"  height="32" rx="1.5" fill="#c9973a"/>
        <rect x="16" y="10" width="6"  height="30" rx="1.5" fill="#e8c26a"/>
        <rect x="24" y="6"  width="10" height="36" rx="1.5" fill="#c9973a"/>
        <rect x="36" y="9"  width="6"  height="31" rx="1.5" fill="#a07830"/>
        <rect x="5"  y="38" width="38" height="2.5" rx="1.25" fill="#7a6030"/>
      </svg>
    </div>
    <div>
      <h2>Cv<em>SU</em></h2>
      <div class="sidebar-subtitle">Library System</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="dashboard.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <div class="nav-section-label">Books</div>
    <a href="view_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Browse Books
    </a>
    <a href="search_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Search Books
    </a>
    <a href="request_borrow.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 5v14M5 12l7-7 7 7"/>
      </svg>
      Request Borrow
    </a>

    <div class="nav-section-label">My Library</div>
   
    <a href="borrow_history.php" class="nav-link active">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/>
        <polyline points="3 3 3.05 11 11 10.94"/>
      </svg>
      Borrow History
    </a>
    <a href="return_book.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
      </svg>
      Return a Book
    </a>
    <a href="view_fines.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      My Fines
    </a>

    <div class="nav-section-label">Account</div>
    <a href="profile.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      My Profile
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">JD</div>
      <div class="user-info">
        <div class="user-name">Juan Dela Cruz</div>
        <div class="user-role">Student</div>
      </div>
    </div>
    <a href="../includes/logout.php" class="btn-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Log Out
    </a>
  </div>
</aside>


<!-- ============================================================
     MAIN WRAPPER
     ============================================================ -->

<?php require_once '../includes/sidebar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">Borrow History</span>
    <div class="topbar-spacer"></div>
    <?php require_once '../includes/student_notifications.php'; ?>

    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>


  <main class="page-content">

    <div class="page-header">
      <h1>Borrow <em style="font-style:italic;color:var(--gold)">History</em></h1>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
      <p>A complete record of all your borrowing activity.</p>
    </div>

    <!-- Summary Strip — values computed by PHP above -->
    <div class="summary-strip">
      <div class="summary-item">
        <span class="s-val"><?= $total_count ?></span>
        <span class="s-lbl">Total Borrowed</span>
      </div>
      <div class="summary-item">
        <span class="s-val"><?= $returned_count ?></span>
        <span class="s-lbl">Returned</span>
      </div>
      <div class="summary-item">
        <span class="s-val"><?= $active_count ?></span>
        <span class="s-lbl">Active</span>
      </div>
      <div class="summary-item">
        <span class="s-val" <?= $fined_count > 0 ? 'style="color:var(--rust)"' : '' ?>><?= $fined_count ?></span>
        <span class="s-lbl">With Fines</span>
      </div>
    </div>

    <!-- Filter Bar — counts are PHP-rendered, tab switching stays JS -->
    <div class="filter-bar">
      <button class="filter-tab active" data-filter="all">
        All <span class="tab-count"><?= $total_count ?></span>
      </button>
      <button class="filter-tab" data-filter="active">
        Active <span class="tab-count"><?= $active_count ?></span>
      </button>
      <button class="filter-tab" data-filter="returned">
        Returned <span class="tab-count"><?= $returned_count ?></span>
      </button>
      <button class="filter-tab" data-filter="overdue">
        Overdue / Fined <span class="tab-count"><?= $overdue_count ?></span>
      </button>
      <div class="filter-spacer"></div>
    </div>


    <!-- Timeline — built via PHP loop, filtered client-side by JS -->
    <div class="history-timeline" id="historyTimeline">

      <?php if (empty($history)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <h3>No borrow records yet</h3>
          <p>Your borrowing history will appear here once you check out a book.</p>
        </div>
      <?php else: ?>

        <?php foreach ($grouped as $month => $entries): ?>
          <div class="history-group" data-month="<?= htmlspecialchars($month) ?>">
            <div class="history-group-label"><?= htmlspecialchars($month) ?></div>

            <?php foreach ($entries as $e): ?>
              <?php
                $status   = $e['status'];
                $late     = days_late($e['due_date'], $e['return_date']);
                $dot_cls  = match($status) {
                    'returned' => 'dot-returned',
                    'overdue'  => 'dot-overdue',
                    default    => 'dot-active',
                };
                // Badge markup
                $badge = match($status) {
                    'returned' => '<span class="badge badge-sage">Returned</span>',
                    'overdue'  => '<span class="badge badge-rust">Overdue</span>',
                    default    => '<span class="badge badge-gold">Active</span>',
                };
                $book_emoji = ['📗','📘','📙','📕'][array_rand(['📗','📘','📙','📕'])];
              ?>
              <div class="history-entry"
                   data-status="<?= htmlspecialchars($status) ?>"
                   data-title="<?= htmlspecialchars($e['title']) ?>"
                   data-author="<?= htmlspecialchars($e['author']) ?>">
                <div class="entry-dot <?= $dot_cls ?>"><span></span></div>
                <div class="entry-card">
                  <div class="entry-book-spine"><?= $book_emoji ?></div>
                  <div class="entry-main">
                    <div class="entry-title"><?= htmlspecialchars($e['title']) ?></div>
                    <div class="entry-author"><?= htmlspecialchars($e['author']) ?></div>
                    <div class="entry-dates">
                      <span>Borrowed: <strong><?= date('M j, Y', strtotime($e['borrow_date'])) ?></strong></span>
                      <?php if ($e['return_date']): ?>
                        <span>Returned: <strong><?= date('M j, Y', strtotime($e['return_date'])) ?></strong></span>
                      <?php else: ?>
                        <span>Due: <strong><?= date('M j, Y', strtotime($e['due_date'])) ?></strong></span>
                      <?php endif; ?>
                      <?php if ($late > 0): ?>
                        <span style="color:var(--rust)"><?= $late ?> day<?= $late !== 1 ? 's' : '' ?> late</span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($e['fine_amount'])): ?>
                      <div class="fine-chip <?= $e['fine_status'] === 'paid' ? 'paid' : '' ?>">
                        ₱<?= number_format($e['fine_amount']) ?> fine — <?= $e['fine_status'] === 'paid' ? 'paid' : 'unpaid' ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="entry-meta"><?= $badge ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <!-- Empty state shown by JS when filters produce no results -->
        <div class="empty-state" id="emptyState" style="display:none;">
          <div class="empty-icon">📭</div>
          <h3>No records found</h3>
          <p>Try adjusting your filters or search query.</p>
        </div>

      <?php endif; ?>
    </div>
    <!-- end timeline -->

  </main>
</div>

<div class="toast" id="toast"></div>

<script>
  /* ── Mobile sidebar toggle ── */
  function checkMobile() {
    const t = document.getElementById('menuToggle');
    t.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    if (window.innerWidth > 768) document.getElementById('sidebar').classList.remove('open');
  }
  checkMobile();
  window.addEventListener('resize', checkMobile);
  document.getElementById('menuToggle').addEventListener('click', () =>
    document.getElementById('sidebar').classList.toggle('open')
  );

  /* ── Filter Tabs ── (must stay JS — instant client-side filtering) */
  const tabs = document.querySelectorAll('.filter-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      tabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      applyFilters();
    });
  });

  function applyFilters() {
    const activeFilter = document.querySelector('.filter-tab.active')?.dataset.filter || 'all';
    const entries = document.querySelectorAll('.history-entry');
    let visibleCount = 0;

    entries.forEach(entry => {
      const status = entry.dataset.status;
      const matchFilter = activeFilter === 'all' || status === activeFilter;
      const show = matchFilter;
      entry.style.display = show ? 'flex' : 'none';
      if (show) visibleCount++;
    });

    // Hide month groups that have no visible entries
    document.querySelectorAll('.history-group').forEach(group => {
      const hasVisible = [...group.querySelectorAll('.history-entry')].some(e => e.style.display !== 'none');
      group.style.display = hasVisible ? '' : 'none';
    });

    document.getElementById('emptyState').style.display = visibleCount === 0 ? 'block' : 'none';
  }

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