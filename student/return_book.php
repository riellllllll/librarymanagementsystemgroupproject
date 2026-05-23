<?php
// ============================================================
// return_book.php — CvSU Library Return a Book
// ============================================================
session_start();



// ── Handle return form submission ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'])) {
    $borrow_id = intval($_POST['borrow_id']);
    $today     = date('Y-m-d');

    // TODO: DB operations
    // require_once '../includes/db_connect.php';
    //
    // 1. Verify this borrow belongs to this student and is still active
    // $stmt = $pdo->prepare("SELECT b.*, bk.title FROM borrows b JOIN books bk ON b.book_id=bk.id
    //   WHERE b.id=? AND b.student_id=? AND b.status IN ('active','overdue')");
    // $stmt->execute([$borrow_id, $_SESSION['student_id']]);
    // $borrow = $stmt->fetch(PDO::FETCH_ASSOC);
    //
    // if (!$borrow) { $_SESSION['flash_error'] = 'Invalid borrow record.'; header('Location: return_book.php'); exit; }
    //
    // 2. Calculate fine
    // $due  = new DateTime($borrow['due_date']);
    // $ret  = new DateTime($today);
    // $days_late = max(0, $ret->diff($due)->invert ? 0 : $ret->diff($due)->days);
    // $fine_per_day = 5;
    // $fine_amount  = $days_late * $fine_per_day;
    //
    // 3. Mark book as returned
    // $upd = $pdo->prepare("UPDATE borrows SET return_date=?, status='returned' WHERE id=?");
    // $upd->execute([$today, $borrow_id]);
    //
    // 4. Create fine record if overdue
    // if ($fine_amount > 0) {
    //     $ins = $pdo->prepare("INSERT INTO fines (student_id, borrow_id, amount, status) VALUES (?,?,?,'unpaid')");
    //     $ins->execute([$_SESSION['student_id'], $borrow_id, $fine_amount]);
    //     $_SESSION['has_fines'] = true;
    // }
    //
    // 5. Update active_borrows count in session
    // $_SESSION['active_borrows'] = max(0, ($_SESSION['active_borrows'] ?? 1) - 1);

    // Redirect back with success (PRG pattern prevents re-submission on refresh)
    $_SESSION['flash_success'] = 'Book returned successfully!';
    header('Location: return_book.php?returned=1&id=' . $borrow_id);
    exit;
}

// ── Flash messages ────────────────────────────────────────────
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$has_fines = (bool)($_SESSION['has_fines'] ?? false);

// ── Load this student's currently borrowed books ──────────────
// TODO: DB query
// $stmt = $pdo->prepare("SELECT b.id, b.borrow_date, b.due_date, bk.title, bk.author,
//   CASE WHEN b.due_date < CURDATE() THEN 1 ELSE 0 END AS is_overdue,
//   DATEDIFF(CURDATE(), b.due_date) AS days_late
//   FROM borrows b JOIN books bk ON b.book_id=bk.id
//   WHERE b.student_id=? AND b.status IN ('active','overdue')
//   ORDER BY b.due_date ASC");
// $stmt->execute([$_SESSION['student_id']]);
// $borrowed_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Placeholder data (remove when DB connected):
$borrowed_books = [
    [
        'id'          => 1,
        'title'       => 'The Great Gatsby',
        'author'      => 'F. Scott Fitzgerald',
        'borrow_date' => '2026-05-10',
        'due_date'    => '2026-05-25',
        'is_overdue'  => false,
        'days_late'   => 0,
        'fine_per_day'=> 5,
    ],
    [
        'id'          => 2,
        'title'       => 'To Kill a Mockingbird',
        'author'      => 'Harper Lee',
        'borrow_date' => '2026-05-03',
        'due_date'    => '2026-05-18',
        'is_overdue'  => true,
        'days_late'   => 2,
        'fine_per_day'=> 5,
    ],
];

$overdue_books = array_filter($borrowed_books, fn($b) => $b['is_overdue']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Return a Book — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/return_book.css">
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
    <span class="topbar-title">Return a Book</span>
    <div class="topbar-spacer"></div>
   
    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>


  <main class="page-content">

    <div class="page-header">
      <h1>Return a <em style="font-style:italic;color:var(--gold)">Book</em></h1>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
      <p>Select the book you wish to return below.</p>
    </div>

    <?php foreach ($overdue_books as $ob): ?>
      <div class="alert alert-rust" style="margin-bottom:12px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span>
          <strong>Overdue notice:</strong>
          "<?= htmlspecialchars($ob['title']) ?>" was due on <?= date('M j, Y', strtotime($ob['due_date'])) ?>
          (<?= $ob['days_late'] ?> day<?= $ob['days_late'] !== 1 ? 's' : '' ?> overdue).
          A fine of ₱<?= $ob['fine_per_day'] ?>/day will be applied upon return.
        </span>
      </div>
    <?php endforeach; ?>

    <?php if (empty($borrowed_books)): ?>
      <div class="card">
        <div class="card-body">
          <div class="no-borrow-hint">
            <div class="nb-icon">📚</div>
            <h3>No books to return</h3>
            <p>You don't have any books currently checked out.</p>
          </div>
        </div>
      </div>
    <?php else: ?>

      <div style="display:grid; grid-template-columns: 1fr 360px; gap: 20px; align-items: start;">

        <div class="card">
          <div class="card-body">
            <div class="card-title">Your Borrowed Books</div>
            <div class="card-subtitle">Choose a book to return — click to select</div>

            <div class="return-list" id="returnList">
              <?php foreach ($borrowed_books as $book): ?>
                <?php
                  $overdue_cls = $book['is_overdue'] ? ' overdue' : '';
                  $fine_total  = $book['days_late'] * $book['fine_per_day'];
                ?>
                <div class="return-book-item<?= $overdue_cls ?>"
                     id="item-<?= $book['id'] ?>"
                     data-id="<?= $book['id'] ?>"
                     data-title="<?= htmlspecialchars($book['title']) ?>"
                     data-author="<?= htmlspecialchars($book['author']) ?>"
                     data-due="<?= date('M j, Y', strtotime($book['due_date'])) ?>"
                     data-borrowed="<?= date('M j, Y', strtotime($book['borrow_date'])) ?>"
                     data-overdue="<?= $book['is_overdue'] ? 'true' : 'false' ?>"
                     data-fine="<?= $book['fine_per_day'] ?>"
                     data-days-late="<?= $book['days_late'] ?>">
                  <div class="return-radio"></div>
                  <div class="return-spine">📖</div>
                  <div class="return-info">
                    <div class="return-title"><?= htmlspecialchars($book['title']) ?></div>
                    <div class="return-author"><?= htmlspecialchars($book['author']) ?></div>
                    <div class="return-meta">
                      <span>Borrowed: <strong><?= date('M j, Y', strtotime($book['borrow_date'])) ?></strong></span>
                      <span>Due:
                        <strong <?= $book['is_overdue'] ? 'style="color:var(--rust)"' : '' ?>>
                          <?= date('M j, Y', strtotime($book['due_date'])) ?>
                        </strong>
                      </span>
                    </div>
                    <?php if ($book['is_overdue']): ?>
                      <div class="overdue-chip" style="margin-top:6px;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        Overdue — <?= $book['days_late'] ?> day<?= $book['days_late'] !== 1 ? 's' : '' ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="return-right">
                    <?php if ($book['is_overdue']): ?>
                      <span class="badge badge-rust">Overdue</span>
                      <span style="font-size:0.75rem;color:var(--rust);margin-top:4px;display:block;">+₱<?= $fine_total ?> fine</span>
                    <?php else: ?>
                      <span class="badge badge-sage">On Time</span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div>
          <div class="confirm-panel" id="confirmPanel">
            <div class="confirm-panel-header">
              <h3>Confirm Return</h3>
              <p>Review the details before confirming.</p>
            </div>
            <div class="confirm-body">
              <div class="confirm-row">
                <span class="cr-label">Book</span>
                <span class="cr-val" id="cp-title">—</span>
              </div>
              <div class="confirm-row">
                <span class="cr-label">Author</span>
                <span class="cr-val" id="cp-author">—</span>
              </div>
              <div class="confirm-row">
                <span class="cr-label">Borrowed</span>
                <span class="cr-val" id="cp-borrowed">—</span>
              </div>
              <div class="confirm-row">
                <span class="cr-label">Due Date</span>
                <span class="cr-val" id="cp-due">—</span>
              </div>
              <div class="confirm-row">
                <span class="cr-label">Return Date</span>
                <span class="cr-val" style="color:var(--gold-dk)">
                  Today — <?= date('M j, Y') ?>
                </span>
              </div>
              <div class="confirm-row" id="cp-fine-row" style="display:none;">
                <span class="cr-label">Fine (per day)</span>
                <span class="cr-val bad" id="cp-fine">—</span>
              </div>
            </div>

            <form method="POST" action="return_book.php" id="returnForm">
              <input type="hidden" name="borrow_id" id="returnBorrowId" value="">
            </form>

            <div class="confirm-actions">
              <button type="button" class="btn-outline" id="btnCancel" style="flex:1;">Cancel</button>
              <button type="button" class="btn-primary" id="btnConfirm" style="flex:1;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
                </svg>
                Confirm Return
              </button>
            </div>
          </div>

          <div class="no-borrow-hint" id="selectHint">
            <div class="nb-icon">👈</div>
            <h3>Select a book</h3>
            <p>Click on one of your borrowed books to see return details here.</p>
          </div>
        </div>

      </div>

    <?php endif; ?>

  </main>
</div>


<div class="modal-backdrop" id="doubleCheckModal">
  <div class="modal" style="max-width: 400px;">
    <div class="modal-body" style="text-align:center; padding: 24px;">
      <div style="font-size:2.8rem;margin-bottom:12px;">⚠️</div>
      <div class="modal-title" style="text-align:center; margin-bottom: 8px;">Are you sure?</div>
      <div class="modal-desc" style="text-align:center; margin-bottom: 20px; color: var(--muted);">
        You are about to process the return for <br><strong id="dcBookTitle" style="color:var(--ink)">""</strong>.<br>Would you like to proceed?
      </div>
      <div style="display:flex;gap:10px;margin-top:16px;">
        <button type="button" class="btn-outline" id="btnDoubleCheckCancel" style="flex:1;">Go Back</button>
        <button type="button" class="btn-primary" id="btnDoubleCheckConfirm" style="flex:1; background-color: var(--gold);">Yes, Confirm</button>
      </div>
    </div>
  </div>
</div>


<div class="modal-backdrop" id="successModal">
  <div class="modal">
    <div class="modal-top"></div>
    <button class="modal-close" id="modalClose" aria-label="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
    <div class="modal-body">
      <div style="text-align:center;margin-bottom:18px;">
        <div style="font-size:2.8rem;margin-bottom:10px;">✅</div>
        <div class="modal-title" style="text-align:center;">Book Returned!</div>
        <div class="modal-desc" style="text-align:center;">Your return has been recorded. Here's your receipt.</div>
      </div>
      <div class="receipt-lines" id="receiptLines"></div>
      <div style="display:flex;gap:10px;margin-top:16px;">
        <a href="borrow_history.php" class="btn-outline" style="flex:1;text-align:center;">View History</a>
        <button class="btn-primary" style="flex:1;" id="modalCloseBtn">Done</button>
      </div>
    </div>
  </div>
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

  /* ── Book selection — must stay JS (click UI, no page reload needed) ── */
  let selectedItem = null;

  document.querySelectorAll('.return-book-item').forEach(item => {
    item.addEventListener('click', function() {
      document.querySelectorAll('.return-book-item').forEach(i => i.classList.remove('selected'));
      this.classList.add('selected');
      selectedItem = this;
      updateConfirmPanel(this);
    });
  });

  function updateConfirmPanel(item) {
    const isOverdue = item.dataset.overdue === 'true';
    const fine      = parseInt(item.dataset.fine);
    const daysLate  = parseInt(item.dataset.daysLate || '0');

    document.getElementById('cp-title').textContent    = item.dataset.title;
    document.getElementById('cp-author').textContent   = item.dataset.author;
    document.getElementById('cp-borrowed').textContent = item.dataset.borrowed;

    const dueEl = document.getElementById('cp-due');
    dueEl.textContent = item.dataset.due;
    dueEl.className   = isOverdue ? 'cr-val bad' : 'cr-val';

    const fineRow = document.getElementById('cp-fine-row');
    if (isOverdue && fine > 0) {
      fineRow.style.display = 'flex';
      document.getElementById('cp-fine').textContent = '₱' + fine + '/day (₱' + (fine * daysLate) + ' total so far)';
    } else {
      fineRow.style.display = 'none';
    }

    // Set the hidden borrow ID so the PHP form knows which book to return
    document.getElementById('returnBorrowId').value = item.dataset.id;

    document.getElementById('confirmPanel').classList.add('visible');
    document.getElementById('selectHint').style.display = 'none';
  }

  /* ── Cancel side panel selection ── */
  document.getElementById('btnCancel').addEventListener('click', () => {
    document.querySelectorAll('.return-book-item').forEach(i => i.classList.remove('selected'));
    selectedItem = null;
    document.getElementById('returnBorrowId').value = '';
    document.getElementById('confirmPanel').classList.remove('visible');
    document.getElementById('selectHint').style.display = 'block';
  });

  /* ── Step 1: Open the Double-Check Modal to prevent accidental clicks ── */
  document.getElementById('btnConfirm').addEventListener('click', () => {
    if (!selectedItem) return;
    
    // Insert the dynamic title into the safety modal text
    document.getElementById('dcBookTitle').textContent = `"${selectedItem.dataset.title}"`;
    // Open the safety warning modal
    document.getElementById('doubleCheckModal').classList.add('open');
  });

  /* ── Step 2a: Dismiss safety check modal if "Go Back" is clicked ── */
  document.getElementById('btnDoubleCheckCancel').addEventListener('click', () => {
    document.getElementById('doubleCheckModal').classList.remove('open');
  });

  /* ── Step 2b: Action verified! Process receipt modal and change UI state ── */
  document.getElementById('btnDoubleCheckConfirm').addEventListener('click', () => {
    // Close the safety modal
    document.getElementById('doubleCheckModal').classList.remove('open');
    
    if (!selectedItem) return;
    const isOverdue = selectedItem.dataset.overdue === 'true';
    const fine      = parseInt(selectedItem.dataset.fine);
    const daysLate  = parseInt(selectedItem.dataset.daysLate || '0');
    const today     = new Date().toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });

    // Build receipt HTML for modal display
    let lines = `
      <div class="receipt-line"><span class="rl-label">Book</span><span>${selectedItem.dataset.title}</span></div>
      <div class="receipt-line"><span class="rl-label">Author</span><span>${selectedItem.dataset.author}</span></div>
      <div class="receipt-line"><span class="rl-label">Return Date</span><span>${today}</span></div>
      <div class="receipt-line"><span class="rl-label">Status</span>
        <span>${isOverdue ? '<span style="color:var(--rust)">Overdue</span>' : '<span style="color:var(--sage)">On Time</span>'}</span>
      </div>
    `;
    if (isOverdue) {
      lines += `<div class="receipt-line"><span class="rl-label">Fine (per day)</span><span style="color:var(--rust)">₱${fine}</span></div>`;
    }

    const totalDiv = document.createElement('div');
    totalDiv.className = 'receipt-total' + (isOverdue ? '' : ' no-fine');
    totalDiv.innerHTML = `<span>Total Fine Due</span><span>${isOverdue ? '₱' + (fine * daysLate) + ' (to be settled)' : 'No fine — returned on time!'}</span>`;

    const receiptEl = document.getElementById('receiptLines');
    receiptEl.innerHTML = lines;
    receiptEl.appendChild(totalDiv);

    // Show receipt modal
    document.getElementById('successModal').classList.add('open');

    // Dim the returned item in the list
    selectedItem.style.opacity = '0.4';
    selectedItem.style.pointerEvents = 'none';
    document.getElementById('confirmPanel').classList.remove('visible');
    document.getElementById('selectHint').style.display = 'block';
    selectedItem = null;
  });

  /* ── Modal close (Done button submits the PHP return form) ── */
  document.getElementById('modalCloseBtn').addEventListener('click', () => {
    document.getElementById('successModal').classList.remove('open');
    // Submit the PHP POST form to record the return in DB
    document.getElementById('returnForm').submit();
  });

  document.getElementById('modalClose').addEventListener('click', () => {
    document.getElementById('successModal').classList.remove('open');
    document.getElementById('returnForm').submit();
  });

  document.getElementById('successModal').addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.remove('open');
      document.getElementById('returnForm').submit();
    }
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

  /* ── Show flash from PHP redirect ── */
  <?php if ($flash_success): ?>
    window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($flash_success) ?>, 'success'));
  <?php elseif ($flash_error): ?>
    window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($flash_error) ?>, 'error'));
  <?php endif; ?>
</script>
</body>
</html>