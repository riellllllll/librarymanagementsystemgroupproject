<?php
// ============================================================
// borrow_history.php — CvSU Library Borrow History
// ============================================================
session_start();


$has_fines = (bool)($_SESSION['has_fines'] ?? false);

// ── TODO: Replace with real DB query ─────────────────────────
// require_once '../includes/db_connect.php';
// $stmt = $pdo->prepare("
//   SELECT b.id, b.borrow_date, b.due_date, b.return_date, b.status,
//          bk.title, bk.author,
//          f.amount   AS fine_amount,
//          f.status   AS fine_status
//   FROM borrows b
//   JOIN books bk ON b.book_id = bk.id
//   LEFT JOIN fines f ON f.borrow_id = b.id
//   WHERE b.student_id = ?
//   ORDER BY b.borrow_date DESC
// ");
// $stmt->execute([$_SESSION['student_id']]);
// $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Placeholder data (remove when DB connected):
$history = [
    ['id'=>1,'title'=>'The Great Gatsby',      'author'=>'F. Scott Fitzgerald','borrow_date'=>'2026-05-01','due_date'=>'2026-05-25','return_date'=>null,        'status'=>'active',  'fine_amount'=>null, 'fine_status'=>null],
    ['id'=>2,'title'=>'To Kill a Mockingbird', 'author'=>'Harper Lee',         'borrow_date'=>'2026-05-04','due_date'=>'2026-05-18','return_date'=>null,        'status'=>'overdue', 'fine_amount'=>null, 'fine_status'=>null],
    ['id'=>3,'title'=>'1984',                  'author'=>'George Orwell',      'borrow_date'=>'2026-04-20','due_date'=>'2026-05-04','return_date'=>'2026-05-02','status'=>'returned','fine_amount'=>null, 'fine_status'=>null],
    ['id'=>4,'title'=>'Brave New World',       'author'=>'Aldous Huxley',      'borrow_date'=>'2026-04-01','due_date'=>'2026-04-15','return_date'=>'2026-04-22','status'=>'overdue', 'fine_amount'=>20,   'fine_status'=>'unpaid'],
    ['id'=>5,'title'=>'The Alchemist',         'author'=>'Paulo Coelho',       'borrow_date'=>'2026-03-14','due_date'=>'2026-03-28','return_date'=>'2026-03-28','status'=>'returned','fine_amount'=>null, 'fine_status'=>null],
    ['id'=>6,'title'=>'Of Mice and Men',       'author'=>'John Steinbeck',     'borrow_date'=>'2026-03-02','due_date'=>'2026-03-16','return_date'=>'2026-03-16','status'=>'returned','fine_amount'=>null, 'fine_status'=>null],
    ['id'=>7,'title'=>'Animal Farm',           'author'=>'George Orwell',      'borrow_date'=>'2026-02-10','due_date'=>'2026-02-24','return_date'=>'2026-02-24','status'=>'returned','fine_amount'=>10,   'fine_status'=>'paid'],
];

// ── Compute summary stats via PHP ─────────────────────────────
$total_count    = count($history);
$returned_count = count(array_filter($history, fn($e) => $e['status'] === 'returned'));
$active_count   = count(array_filter($history, fn($e) => $e['status'] === 'active'));
$overdue_count  = count(array_filter($history, fn($e) => $e['status'] === 'overdue'));
$fined_count    = count(array_filter($history, fn($e) => !empty($e['fine_amount'])));

// ── Group entries by month (from borrow_date) ─────────────────
$grouped = [];
foreach ($history as $entry) {
    // Group by the month of the borrow date
    $month_key = date('F Y', strtotime($entry['borrow_date']));
    $grouped[$month_key][] = $entry;
}

// ── Helper: days late for a returned overdue entry ────────────
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
    <a href="view_fines.php" class="topbar-icon-btn" title="Fines &amp; Notifications">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <?php if ($has_fines): ?><span class="topbar-notif-dot"></span><?php endif; ?>
    </a>
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