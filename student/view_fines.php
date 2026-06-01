<?php
// ============================================================
// view_fines.php — CvSU Library Student Fines (DB-powered)
// ============================================================
session_start();
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../classes/Fine.php';

$fineObj = new Fine($conn);

// ── Handle POST: submit payment for admin approval ──
$flash_success = '';
$flash_error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'submit_payment') {
        $fid    = (int)($_POST['fine_id'] ?? 0);
        $method = trim($_POST['method'] ?? 'counter');
        $book   = trim($_POST['book_title'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $result = $fineObj->requestPayment($student_id, $fid ?: null, $method);
        if ($result === true) {
            $_SESSION['payment_receipt'] = [
                'book'    => $book ?: 'Selected Fine',
                'amount'  => $amount,
                'method'  => $method,
                'is_all'  => false,
                'ref'     => 'LIB-' . date('Ymd') . '-' . str_pad((string)mt_rand(0, 99999), 5, '0', STR_PAD_LEFT),
                'when'    => date('M j, Y g:i A'),
            ];
        } else {
            $_SESSION['flash_error'] = is_string($result) ? $result : 'Failed to submit payment.';
        }
        header('Location: view_fines.php');
        exit;
    }
    if ($action === 'submit_payment_all') {
        $method = trim($_POST['method'] ?? 'counter');
        $amount = (float)($_POST['amount'] ?? 0);
        $result = $fineObj->requestPayment($student_id, null, $method);
        if ($result === true) {
            $_SESSION['payment_receipt'] = [
                'book'    => 'All Outstanding Fines',
                'amount'  => $amount,
                'method'  => $method,
                'is_all'  => true,
                'ref'     => 'LIB-' . date('Ymd') . '-' . str_pad((string)mt_rand(0, 99999), 5, '0', STR_PAD_LEFT),
                'when'    => date('M j, Y g:i A'),
            ];
        } else {
            $_SESSION['flash_error'] = is_string($result) ? $result : 'Failed to submit payments.';
        }
        header('Location: view_fines.php');
        exit;
    }
}

$flash_success   = $_SESSION['flash_success']   ?? '';
$flash_error     = $_SESSION['flash_error']     ?? '';
$payment_receipt = $_SESSION['payment_receipt'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['payment_receipt']);

$has_fines = (bool)($_SESSION['has_fines'] ?? false);

// ── Load from DB & map to UI shape ──
$db_fines = $fineObj->getByStudent($student_id);
$fines = [];
foreach ($db_fines as $f) {
    $days_late = 0;
    if (!empty($f['due_date'])) {
        $end = $f['return_date'] ?? date('Y-m-d');
        $days_late = max(0, (int)((strtotime($end) - strtotime($f['due_date'])) / 86400));
    }
    // UI status: 'unpaid' | 'pending' (awaiting admin) | 'paid'
    $uistatus = match($f['paid_status']) {
        'paid'              => 'paid',
        'payment_requested' => 'pending',
        default             => 'unpaid',
    };
    $fines[] = [
        'id'          => (int)$f['id'],
        'book_title'  => $f['book_title'],
        'author'      => $f['author'] ?? '',
        'due_date'    => $f['due_date'],
        'return_date' => $f['return_date'],
        'days_late'   => $days_late,
        'amount'      => (float)$f['amount'],
        'status'      => $uistatus,
    ];
}

// ── Compute totals ──
$unpaid_fines  = array_filter($fines, fn($f) => $f['status'] === 'unpaid');
$pending_fines = array_filter($fines, fn($f) => $f['status'] === 'pending');
$paid_fines    = array_filter($fines, fn($f) => $f['status'] === 'paid');
$total_unpaid  = array_sum(array_column(array_values($unpaid_fines),  'amount'));
$total_pending = array_sum(array_column(array_values($pending_fines), 'amount'));
$total_paid    = array_sum(array_column(array_values($paid_fines),    'amount'));
$total_ever    = $total_unpaid + $total_pending + $total_paid;
$unpaid_count  = count($unpaid_fines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Fines — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/view_fines.css">
</head>
<body>



<!-- ============================================================
     MAIN WRAPPER
     ============================================================ -->
<?php
// ── Use the shared sidebar instead of duplicate HTML ─────────
// This replaces the hardcoded <aside> block that was here before.
// sidebar.php reads $_SESSION automatically.
require_once '../includes/sidebar.php';
?>

<div class="main-wrapper">

  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">My Fines</span>
    <div class="topbar-spacer"></div>
    <?php require_once '../includes/student_notifications.php'; ?>
    
    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>

  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>My <em style="font-style:italic;color:var(--gold)">Fines</em></h1>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
      <p>Overview of all library fines on your account.</p>
    </div>

    <?php if ($flash_error): ?>
      <div class="alert alert-rust" style="margin-bottom:1rem;">
        <?= htmlspecialchars($flash_error) ?>
      </div>
    <?php endif; ?>

    <!-- ── Fine Hero Card (all values PHP-rendered) ── -->
    <div class="fine-hero">
      <div class="fine-hero-inner">
        <div class="fine-hero-main">
          <div class="fine-hero-label">Total Unpaid Fines</div>

          <?php if ($total_unpaid > 0): ?>
            <!-- Unpaid balance exists -->
            <div class="fine-hero-amount">₱<?= number_format($total_unpaid, 2) ?></div>
            <div class="fine-hero-desc">
              You have
              <strong><?= $unpaid_count ?> unpaid fine<?= $unpaid_count !== 1 ? 's' : '' ?></strong>
              on your account. Please settle before your next borrow request.
            </div>
            <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
              <button class="btn-primary" onclick="openPayModal('all')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                Pay All Fines
              </button>
              <a href="return_book.php" class="btn-outline">Return a Book</a>
            </div>

          <?php else: ?>
            <!-- All clear -->
            <div class="fine-hero-amount" style="color:var(--sage)">₱0.00</div>
            <div class="all-clear-banner" style="margin-top:12px;">
              <div class="acb-icon">🎉</div>
              <div class="acb-text">
                <h4>All fines cleared!</h4>
                <p>Your account is in good standing. Keep returning books on time.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Mini stats — all PHP variables -->
        <div class="fine-hero-side">
          <div class="fine-mini-stat">
            <span class="ms-val" <?= $total_unpaid > 0 ? 'style="color:var(--rust)"' : '' ?>>
              ₱<?= number_format($total_unpaid) ?>
            </span>
            <span class="ms-lbl">Unpaid</span>
          </div>
          <div class="fine-mini-stat">
            <span class="ms-val" style="color:var(--sage)">₱<?= number_format($total_paid) ?></span>
            <span class="ms-lbl">Paid</span>
          </div>
          <div class="fine-mini-stat">
            <span class="ms-val"><?php
              $current_total = $total_unpaid + $total_pending;
              echo '₱' . number_format($current_total);
            ?></span>
            <span class="ms-lbl">Total</span>
          </div>
          <div class="fine-mini-stat">
            <span class="ms-val">₱<?= number_format($total_ever) ?></span>
            <span class="ms-lbl">Total Ever</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Fines Table (PHP loop) ── -->
    <div class="card">
      <div class="card-body">
        <div class="card-title">Fine Details</div>
        <div class="card-subtitle">Click "Pay" to settle an individual fine</div>

        <?php if (empty($fines)): ?>
          <p style="color:var(--muted);font-size:0.85rem;padding:16px 0;">
            You have no fine records. Keep returning books on time! 📚
          </p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Book Title</th>
                  <th>Due Date</th>
                  <th>Returned</th>
                  <th>Days Late</th>
                  <th>Fine</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($fines as $i => $f): ?>
                  <tr class="<?= $f['status'] === 'unpaid' ? 'fine-row-unpaid' : 'fine-row-paid' ?>"
                      id="fine-row-<?= (int)$f['id'] ?>">

                    <td style="color:var(--muted);font-size:0.78rem;">
                      <?= str_pad($i + 1, 3, '0', STR_PAD_LEFT) ?>
                    </td>

                    <td>
                      <div style="font-weight:600;color:var(--ink);">
                        <?= htmlspecialchars($f['book_title']) ?>
                      </div>
                      <div style="font-size:0.72rem;color:var(--muted);">
                        <?= htmlspecialchars($f['author']) ?>
                      </div>
                    </td>

                    <td <?= $f['status'] === 'unpaid' ? 'style="color:var(--rust);font-weight:500;"' : '' ?>>
                      <?= date('M j, Y', strtotime($f['due_date'])) ?>
                    </td>

                    <td>
                      <?= $f['return_date']
                          ? date('M j, Y', strtotime($f['return_date']))
                          : '<span style="color:var(--muted);">Pending</span>' ?>
                    </td>

                    <td>
                      <?php if ($f['status'] === 'unpaid'): ?>
                        <span class="badge badge-rust"><?= (int)$f['days_late'] ?>+ day<?= $f['days_late'] !== 1 ? 's' : '' ?></span>
                      <?php else: ?>
                        <span class="badge badge-muted"><?= (int)$f['days_late'] ?> day<?= $f['days_late'] !== 1 ? 's' : '' ?></span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <span class="fine-cell-amount <?= $f['status'] ?>">
                        ₱<?= number_format($f['amount']) ?>
                      </span>
                    </td>

                    <td>
                      <?php if ($f['status'] === 'unpaid'): ?>
                        <span class="badge badge-rust">Unpaid</span>
                      <?php elseif ($f['status'] === 'pending'): ?>
                        <span class="badge badge-gold">Awaiting Approval</span>
                      <?php else: ?>
                        <span class="badge badge-sage">Paid</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($f['status'] === 'unpaid'): ?>
                        <button class="btn-danger pay-fine-btn"
                                style="padding:6px 14px;font-size:0.76rem;"
                                data-fine-id="<?= (int)$f['id'] ?>"
                                data-book-title="<?= htmlspecialchars($f['book_title'], ENT_QUOTES) ?>"
                                data-amount="<?= (int)$f['amount'] ?>">
                          Pay
                        </button>
                      <?php elseif ($f['status'] === 'pending'): ?>
                        <span style="font-size:0.74rem;color:var(--gold);font-weight:500;">Pending admin</span>
                      <?php else: ?>
                        <span style="font-size:0.75rem;color:var(--muted);">—</span>
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

    <!-- Fine Policy Info -->
    <div class="alert alert-gold" style="margin-top:16px;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <span>
        <strong>Fine Policy:</strong> Overdue books incur a fine of ₱5 per day.
        Unpaid fines must be settled at the library counter before making new borrow requests.
      </span>
    </div>

  </main>
</div>


<!-- ── Payment Confirmation Panel ── -->
<div id="payConfirmPanel" style="display:none;position:fixed;inset:0;z-index:1100;background:rgba(15,22,35,0.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--card-bg,#fff);border-radius:18px;max-width:480px;width:100%;box-shadow:0 24px 64px rgba(15,22,35,0.22);overflow:hidden;animation:modalIn 0.28s cubic-bezier(.22,1,.36,1);">
    <div style="height:4px;background:linear-gradient(90deg,var(--sage,#2e7d5e),#4aab82,var(--sage,#2e7d5e));"></div>
    <div style="padding:28px 28px 24px;">
      <div style="text-align:center;margin-bottom:20px;">
        <div style="width:56px;height:56px;background:rgba(46,125,94,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.6rem;">✅</div>
        <div style="font-size:1.15rem;font-weight:700;color:var(--ink,#0f1623);margin-bottom:4px;">Payment Submitted</div>
        <div style="font-size:0.82rem;color:var(--muted,#6b7a99);" id="pcpDesc">Your fine payment has been recorded.</div>
      </div>
      <div style="background:var(--input-bg,#f4f6fb);border:1px solid var(--border,#dde3ef);border-radius:12px;padding:16px 18px;margin-bottom:18px;">
        <div style="font-size:0.62rem;letter-spacing:0.16em;text-transform:uppercase;color:#aab4cc;margin-bottom:12px;">Payment Details</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.8rem;color:var(--muted,#6b7a99);">Book</span>
            <span style="font-size:0.85rem;font-weight:600;color:var(--ink,#0f1623);" id="pcpBook">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.8rem;color:var(--muted,#6b7a99);">Amount Paid</span>
            <span style="font-size:1.05rem;font-weight:800;color:var(--sage,#2e7d5e);" id="pcpAmount">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.8rem;color:var(--muted,#6b7a99);">Payment Method</span>
            <span style="font-size:0.85rem;font-weight:600;color:var(--ink,#0f1623);" id="pcpMethod">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.8rem;color:var(--muted,#6b7a99);">Reference No.</span>
            <span style="font-size:0.85rem;font-weight:600;color:var(--ink,#0f1623);font-family:monospace;letter-spacing:0.06em;" id="pcpRef">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.8rem;color:var(--muted,#6b7a99);">Date &amp; Time</span>
            <span style="font-size:0.82rem;color:var(--ink,#0f1623);" id="pcpDate">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.8rem;color:var(--muted,#6b7a99);">Status</span>
            <span class="badge badge-sage" id="pcpStatus">Pending Confirmation</span>
          </div>
        </div>
      </div>
      <div id="pcpNotice" style="font-size:0.76rem;color:var(--muted,#6b7a99);background:rgba(201,151,58,0.07);border:1px solid rgba(201,151,58,0.2);border-radius:8px;padding:10px 13px;margin-bottom:18px;line-height:1.5;"></div>
      <div style="display:flex;gap:10px;">
        <button class="btn-outline" style="flex:1;" onclick="closePayConfirm()">Close</button>
        <button class="btn-primary" style="flex:1;" onclick="window.print()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
          </svg>
          Print Receipt
        </button>
      </div>
    </div>
  </div>
</div>


<!-- ── Pay Fine Modal ── -->
<div class="modal-backdrop" id="payModal">
  <div class="modal">
    <div class="modal-top"></div>
    <button class="modal-close" id="payModalClose" aria-label="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
    <div class="modal-body">
      <div class="modal-title">Settle Fine</div>
      <div class="modal-desc" id="payModalDesc">Pay your overdue fine at the library counter or select a method below.</div>

      <div class="pay-amount-display">
        <div class="pa-label">Amount Due</div>
        <div class="pa-val" id="payModalAmount">₱0</div>
      </div>

      <div class="field">
        <label>Payment Method</label>
        <div class="pay-methods">
          <div class="pay-method-tile selected" data-method="counter">
            <span class="pm-icon">💵</span>
            <span>Cash</span>
          </div>
          <div class="pay-method-tile" data-method="gcash">
            <span class="pm-icon">📱</span>
            <span>GCash</span>
          </div>
          <div class="pay-method-tile" data-method="maya">
            <span class="pm-icon">💳</span>
            <span>Maya</span>
          </div>
          <div class="pay-method-tile" data-method="bank">
            <span class="pm-icon">🏦</span>
            <span>Bank Transfer</span>
          </div>
        </div>
      </div>

      <div class="alert alert-gold" style="margin-bottom:18px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span>Online payment will generate a reference number. Present it to a librarian for confirmation.</span>
      </div>

      <div style="display:flex;gap:10px;">
        <button class="btn-outline" style="flex:1;" id="payModalCancelBtn">Cancel</button>
        <button class="btn-primary" style="flex:1;" id="payNowBtn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
          </svg>
          Proceed to Pay
        </button>
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

  /* ── Payment method selection ── */
  document.querySelectorAll('.pay-method-tile').forEach(tile => {
    tile.addEventListener('click', function() {
      document.querySelectorAll('.pay-method-tile').forEach(t => t.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  /* ── Open Pay Modal ──
     Called from PHP-rendered buttons:
       openPayModal('all')                       — pay all
       openPayModal(id, 'Book Title', amount)    — pay one fine   */
  let payTarget   = null;
  let payBookName = '';

  function openPayModal(target, bookTitle, amount) {
    payTarget   = target;
    payBookName = bookTitle || 'All Outstanding Fines';

    const isAll     = target === 'all';
    const amountVal = isAll
      ? '₱<?= number_format($total_unpaid) ?>'
      : '₱' + (amount || 0);

    const desc = isAll
      ? 'Settling all <?= $unpaid_count ?> outstanding fine<?= $unpaid_count !== 1 ? "s" : "" ?> on your account.'
      : 'Settling fine for "' + (bookTitle || '') + '".';

    document.getElementById('payModalAmount').textContent = amountVal;
    document.getElementById('payModalDesc').textContent   = desc;
    document.getElementById('payModal').classList.add('open');
  }

  /* ── Wire up the per-row Pay buttons using data attributes
     (safer than inline onclick — handles apostrophes & special chars) ── */
  document.querySelectorAll('.pay-fine-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id     = parseInt(btn.dataset.fineId, 10);
      const title  = btn.dataset.bookTitle || '';
      const amount = parseInt(btn.dataset.amount, 10) || 0;
      openPayModal(id, title, amount);
    });
  });

  /* ── Close modal ── */
  ['payModalClose', 'payModalCancelBtn'].forEach(id => {
    document.getElementById(id).addEventListener('click', () =>
      document.getElementById('payModal').classList.remove('open')
    );
  });
  document.getElementById('payModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });

  /* ── Proceed to Pay (REAL: posts to server) ── */
  document.getElementById('payNowBtn').addEventListener('click', () => {
    const method = document.querySelector('.pay-method-tile.selected')?.dataset.method || 'counter';
    const isAll  = payTarget === 'all';

    // Build a real form and submit to the server
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'view_fines.php';

    const addInput = (name, value) => {
      const i = document.createElement('input');
      i.type = 'hidden';
      i.name = name;
      i.value = value;
      form.appendChild(i);
    };

    if (isAll) {
      addInput('action', 'submit_payment_all');
    } else {
      addInput('action',  'submit_payment');
      addInput('fine_id', payTarget);
    }
    addInput('method', method);

    document.body.appendChild(form);
    form.submit();
  });

  function closePayConfirm() {
    document.getElementById('payConfirmPanel').style.display = 'none';
  }
  document.getElementById('payConfirmPanel').addEventListener('click', function(e) {
    if (e.target === this) closePayConfirm();
  });

  /* ── Toast ── */
  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (type ? ' ' + type : '');
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
  }

  /* ── Show receipt panel after a successful payment submission ── */
  (function showReceiptOnLoad() {
    const receipt = <?= json_encode($payment_receipt) ?>;
    if (!receipt) return;

    const methodLabels = {
      counter: '💵 Cash',
      gcash:   '📱 GCash',
      maya:    '💳 Maya',
      bank:    '🏦 Bank Transfer'
    };
    const statusByMethod = {
      counter: 'Pending Payment',
      gcash:   'Pending Confirmation',
      maya:    'Pending Confirmation',
      bank:    'Pending Verification'
    };
    const noticeByMethod = {
      counter: 'Please proceed to the library counter during office hours (Mon–Fri, 8:00 AM – 5:00 PM) to pay in cash. Bring your library card.',
      gcash:   'Present your reference number to a librarian for confirmation. Your status will update within 1 business day.',
      maya:    'Present your reference number to a librarian for confirmation. Your status will update within 1 business day.',
      bank:    'Transfer to: BDO – CvSU Library Account No. 0012-3456-7890. Use your Student ID as reference. Show proof of transfer to a librarian.'
    };

    document.getElementById('pcpBook').textContent   = receipt.book;
    document.getElementById('pcpAmount').textContent = '₱' + Number(receipt.amount || 0).toLocaleString();
    document.getElementById('pcpMethod').textContent = methodLabels[receipt.method] || receipt.method;
    document.getElementById('pcpRef').textContent    = receipt.ref;
    document.getElementById('pcpDate').textContent   = receipt.when;
    document.getElementById('pcpStatus').textContent = statusByMethod[receipt.method] || 'Pending';
    document.getElementById('pcpNotice').textContent = noticeByMethod[receipt.method] || '';
    document.getElementById('pcpDesc').textContent   = receipt.is_all
      ? 'All outstanding fines have been submitted for processing.'
      : 'Fine for "' + receipt.book + '" has been submitted for processing.';

    document.getElementById('payConfirmPanel').style.display = 'flex';
  })();
</script>
</body>
</html>