<?php
// view_fines.php — Admin: Outstanding Fines per Student
session_start();
require 'library_data.php';

if (!isset($_SESSION['archived_books'])) {
  $_SESSION['archived_books'] = [];
}

$pending_count = count(array_filter($_SESSION['borrow_requests'], function ($req) {
  return $req['status'] === 'pending';
}));

// ── Mock data (replace with real DB queries) ──────────────────
// Student IDs are now 9-digit: first 4 = enrolment year
$fines_data = [
  '202482628' => [
    'student_name' => 'Emma Watson',
    'year_level'   => '3rd Year',
    'course'       => 'BS Computer Science',
    'email'        => 'emma.watson@example.com',
    'fines'        => [
      ['book_id' => '01', 'book_title' => 'The Great Gatsby',      'issue_date' => '2026-04-10', 'due_date' => '2026-04-25', 'return_date' => null,         'days_overdue' => 32, 'fine_amount' => 160.00, 'status' => 'pending'],
      ['book_id' => '02', 'book_title' => 'Sapiens',               'issue_date' => '2026-05-01', 'due_date' => '2026-05-16', 'return_date' => null,         'days_overdue' => 11, 'fine_amount' =>  55.00, 'status' => 'pending'],
      ['book_id' => '03', 'book_title' => 'Clean Code',            'issue_date' => '2026-03-05', 'due_date' => '2026-03-20', 'return_date' => '2026-04-01', 'days_overdue' => 12, 'fine_amount' =>  60.00, 'status' => 'paid'],
    ],
  ],
  '202331045' => [
    'student_name' => 'James Carter',
    'year_level'   => '2nd Year',
    'course'       => 'BS Information Technology',
    'email'        => 'james.carter@example.com',
    'fines'        => [
      ['book_id' => '04', 'book_title' => 'Deep Work',     'issue_date' => '2026-04-18', 'due_date' => '2026-05-03', 'return_date' => null, 'days_overdue' => 24, 'fine_amount' => 120.00, 'status' => 'pending'],
      ['book_id' => '05', 'book_title' => 'Atomic Habits', 'issue_date' => '2026-05-10', 'due_date' => '2026-05-25', 'return_date' => null, 'days_overdue' =>  2, 'fine_amount' =>  10.00, 'status' => 'pending'],
    ],
  ],
  '202219873' => [
    'student_name' => 'Lina Zhang',
    'year_level'   => '4th Year',
    'course'       => 'BS Accountancy',
    'email'        => 'lina.zhang@example.com',
    'fines'        => [
      ['book_id' => '06', 'book_title' => 'Dune', 'issue_date' => '2026-03-01', 'due_date' => '2026-03-16', 'return_date' => null, 'days_overdue' => 72, 'fine_amount' => 360.00, 'status' => 'pending'],
    ],
  ],
  '202574391' => [
    'student_name' => 'Oliver Chen',
    'year_level'   => '1st Year',
    'course'       => 'BS Business Administration',
    'email'        => 'oliver.chen@example.com',
    'fines'        => [
      ['book_id' => '07', 'book_title' => 'The Hobbit',             'issue_date' => '2026-04-01', 'due_date' => '2026-04-16', 'return_date' => '2026-04-22', 'days_overdue' => 6, 'fine_amount' => 30.00, 'status' => 'paid'],
      ['book_id' => '08', 'book_title' => 'To Kill a Mockingbird',  'issue_date' => '2026-05-12', 'due_date' => '2026-05-27', 'return_date' => null,         'days_overdue' => 0, 'fine_amount' =>  0.00, 'status' => 'pending'],
    ],
  ],
];

// ── Status filter tabs ─────────────────────────────────────────
$status_options = [
  'all'     => 'All Fines',
  'pending' => 'Pending',
  'paid'    => 'Paid',
];

$status_filter       = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$selected_student_id = isset($_GET['student_id'])    ? trim($_GET['student_id']) : '';

// ── Smart search: exact ID → detail view; partial name/ID → filtered table ──
$search_query = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';
$student_data = null;
$search_matches = []; // IDs of students matching a partial search

if ($search_query !== '') {
  // 1. Exact ID match → go straight to detail view
  if (isset($fines_data[$search_query])) {
    $selected_student_id = $search_query;
    $student_data        = $fines_data[$search_query];
  } else {
    // 2. Partial match on name (case-insensitive) OR partial ID
    foreach ($fines_data as $sid => $sdata) {
      $nameMatch = stripos($sdata['student_name'], $search_query) !== false;
      $idMatch   = stripos($sid, $search_query) !== false;
      if ($nameMatch || $idMatch) {
        $search_matches[] = $sid;
      }
    }
    // If only one match found, jump straight to that student's detail view
    if (count($search_matches) === 1) {
      $selected_student_id = $search_matches[0];
      $student_data        = $fines_data[$selected_student_id];
      $search_matches      = [];
    }
  }
}

// ── Validate student ID: must be 9 digits, first 4 = year ─────
// (IDs in $fines_data already follow this convention)

// ── Handle fine payment (POST) ─────────────────────────────────
$payment_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';

  // Keep GET params so we stay on the same student after POST
  if ($post_student_id && isset($fines_data[$post_student_id])) {
    if (isset($_POST['pay_fine'])) {
      $pay_book_id = $_POST['book_id'];
      // Mark the specific fine as paid
      foreach ($fines_data[$post_student_id]['fines'] as &$fine) {
        if ($fine['book_id'] === $pay_book_id && $fine['status'] === 'pending') {
          $fine['status'] = 'paid';
          break;
        }
      }
      unset($fine);
      $amount          = number_format((float)$_POST['amount'], 2);
      $payment_message = "Payment of ₱{$amount} received for \"" . htmlspecialchars($_POST['book_title']) . "\" — marked as Paid.";
    }

    if (isset($_POST['pay_all'])) {
      // Mark ALL pending fines as paid
      foreach ($fines_data[$post_student_id]['fines'] as &$fine) {
        if ($fine['status'] === 'pending') {
          $fine['status'] = 'paid';
        }
      }
      unset($fine);
      $total_amount    = number_format((float)$_POST['total_amount'], 2);
      $payment_message = "Payment of ₱{$total_amount} received — all pending fines cleared.";
    }

    // Refresh student data after payment
    $selected_student_id = $post_student_id;
    $student_data        = $fines_data[$post_student_id];
    $search_query        = $post_student_id;
  }
}

// ── Calculate stats (recalculate AFTER any payment mutations) ──
$total_fines   = 0;
$pending_fines = 0;
$paid_fines    = 0;
$overdue_books = 0;

// Determine which students to sum over
$stats_pool = [];
if ($student_data) {
  $stats_pool = [$selected_student_id => $student_data];
} elseif (!empty($search_matches)) {
  foreach ($search_matches as $sid) $stats_pool[$sid] = $fines_data[$sid];
} else {
  $stats_pool = $fines_data;
}

foreach ($stats_pool as $sdata) {
  foreach ($sdata['fines'] as $fine) {
    $total_fines += $fine['fine_amount'];
    if ($fine['status'] === 'pending') {
      $pending_fines += $fine['fine_amount'];
      if ($fine['days_overdue'] > 0) $overdue_books++;
    } else {
      $paid_fines += $fine['fine_amount'];
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Fines — Admin Panel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
  <link rel="stylesheet" href="../assets/adminFines.css">
</head>
<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <!-- TOP BAR — no search here anymore -->
  <header class="topbar">
    <span class="topbar-title">View Fines</span>
    <div class="topbar-spacer"></div>

    <!-- Notifications bell -->
    <a href="student_req.php" class="topbar-icon-btn" title="Student Borrow Requests">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <?php if ($pending_count > 0): ?>
        <span class="topbar-notif-dot"></span>
      <?php endif; ?>
    </a>

    <a href="admin_profile.php" class="topbar-icon-btn" title="Admin Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>

  <!-- PAGE CONTENT -->
  <main class="page-content">

    <div class="page-header">
      <h1>View Fines</h1>
      <p>View and manage outstanding fines per student</p>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
    </div>

    <!-- Status filter tabs -->
    <div class="fines-filter-tabs">
      <a href="?status_filter=all&student_id=<?= urlencode($selected_student_id) ?>"
         class="fines-tab <?= $status_filter === 'all' ? 'active-all' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        All Fines
      </a>
      <a href="?status_filter=pending&student_id=<?= urlencode($selected_student_id) ?>"
         class="fines-tab <?= $status_filter === 'pending' ? 'active-pending' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Pending
      </a>
      <a href="?status_filter=paid&student_id=<?= urlencode($selected_student_id) ?>"
         class="fines-tab <?= $status_filter === 'paid' ? 'active-paid' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
        Paid
      </a>
    </div>

    <!-- Main fines card -->
    <div class="fines-container">

      <?php if ($payment_message): ?>
        <div class="alert-fines">✓ <?= htmlspecialchars($payment_message) ?></div>
      <?php endif; ?>

      <!-- ── Search bar (inside card, above stats) ─────────────── -->
      <form class="fines-search-bar" method="GET" action="view_fines.php">
        <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          type="text"
          name="student_id"
          placeholder="Search by name or Student ID…"
          value="<?= htmlspecialchars($search_query) ?>"
          autocomplete="off"
          maxlength="50"
        >
        <?php if ($search_query): ?>
          <a href="view_fines.php?status_filter=<?= urlencode($status_filter) ?>" class="fines-search-clear" title="Clear search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </a>
        <?php endif; ?>
      </form>

      <!-- Stats row -->
      <div class="stats-fines-grid">
        <div class="stat-fines-card">
          <div class="stat-fines-label">Total Fines</div>
          <div class="stat-fines-number">₱<?= number_format($total_fines, 2) ?></div>
          <div class="stat-fines-sub">Accrued overall</div>
        </div>
        <div class="stat-fines-card">
          <div class="stat-fines-label">Pending Fines</div>
          <div class="stat-fines-number">₱<?= number_format($pending_fines, 2) ?></div>
          <div class="stat-fines-sub">Unpaid</div>
        </div>
        <div class="stat-fines-card">
          <div class="stat-fines-label">Paid Fines</div>
          <div class="stat-fines-number">₱<?= number_format($paid_fines, 2) ?></div>
          <div class="stat-fines-sub">Total cleared</div>
        </div>
        <div class="stat-fines-card">
          <div class="stat-fines-label">Overdue Books</div>
          <div class="stat-fines-number"><?= $overdue_books ?></div>
          <div class="stat-fines-sub">Contributing to fines</div>
        </div>
      </div>

      <?php if ($selected_student_id && $student_data): ?>

        <!-- Student info banner -->
        <div class="student-fines-info">
          <div class="student-fines-header">
            <div class="student-fines-name-block">
              <div class="student-fines-avatar">
                <?= strtoupper(substr($student_data['student_name'], 0, 1)) ?>
              </div>
              <div>
                <div class="student-fines-name"><?= htmlspecialchars($student_data['student_name']) ?></div>
                <div class="student-fines-sub"><?= htmlspecialchars($student_data['course']) ?></div>
              </div>
            </div>
          </div>
          <div class="student-fines-details">
            <span class="student-fines-chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
              <strong>ID:</strong> <?= htmlspecialchars($selected_student_id) ?>
            </span>
            <span class="student-fines-chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
              <strong>Year:</strong> <?= htmlspecialchars($student_data['year_level']) ?>
            </span>
            <span class="student-fines-chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <strong>Email:</strong> <?= htmlspecialchars($student_data['email']) ?>
            </span>
            <span class="student-fines-chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <strong>Enrolled:</strong> <?= htmlspecialchars(substr($selected_student_id, 0, 4)) ?>
            </span>
          </div>
        </div>

        <div class="section-fines-title">
          <span>Pending &amp; Outstanding Fines</span>
        </div>

        <!-- Fines table -->
        <div class="table-fines-wrapper">
          <table class="table-fines">
            <thead>
              <tr>
                <th>Book ID</th>
                <th>Book Title</th>
                <th>Due Date</th>
                <th>Days Overdue</th>
                <th>Fine Amount</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($student_data['fines'] as $fine):
                if ($status_filter === 'pending' && $fine['status'] !== 'pending') continue;
                if ($status_filter === 'paid'    && $fine['status'] !== 'paid')    continue;
              ?>
              <tr>
                <td><?= htmlspecialchars($fine['book_id']) ?></td>
                <td><?= htmlspecialchars($fine['book_title']) ?></td>
                <td><?= date('M j, Y', strtotime($fine['due_date'])) ?></td>
                <td><?= $fine['days_overdue'] > 0 ? $fine['days_overdue'] . ' day' . ($fine['days_overdue'] !== 1 ? 's' : '') : '—' ?></td>
                <td class="fine-amount">₱<?= number_format($fine['fine_amount'], 2) ?></td>
                <td>
                  <span class="status-badge <?= $fine['status'] === 'pending' ? 'status-pending' : 'status-paid' ?>">
                    <?= ucfirst($fine['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($fine['status'] === 'pending' && $fine['fine_amount'] > 0): ?>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="student_id"  value="<?= htmlspecialchars($selected_student_id) ?>">
                      <input type="hidden" name="book_id"     value="<?= htmlspecialchars($fine['book_id']) ?>">
                      <input type="hidden" name="book_title"  value="<?= htmlspecialchars($fine['book_title']) ?>">
                      <input type="hidden" name="amount"      value="<?= $fine['fine_amount'] ?>">
                      <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
                      <button type="submit" name="pay_fine" class="btn-fines btn-fines-success">Pay</button>
                    </form>
                  <?php else: ?>
                    <span class="btn-fines-paid-check">✓</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pay-all summary bar -->
        <?php if ($pending_fines > 0): ?>
        <div class="total-summary-fines">
          <span class="summary-label">Total Pending Fine (Unpaid)</span>
          <span class="summary-amount">₱<?= number_format($pending_fines, 2) ?></span>
          <form method="POST">
            <input type="hidden" name="student_id"    value="<?= htmlspecialchars($selected_student_id) ?>">
            <input type="hidden" name="total_amount"  value="<?= $pending_fines ?>">
            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
            <button type="submit" name="pay_all" class="btn-pay-all">Pay All</button>
          </form>
        </div>
        <?php endif; ?>

      <?php elseif ($search_query && !empty($search_matches)): ?>

        <!-- ── Filtered results ─────────────────────────────────── -->
        <div class="section-fines-title">
          <span>
            Results for <em style="color:#c89b3c;">"<?= htmlspecialchars($search_query) ?>"</em>
            — <?= count($search_matches) ?> student<?= count($search_matches) !== 1 ? 's' : '' ?> found
          </span>
        </div>

        <div class="table-fines-wrapper">
          <table class="table-fines">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Year</th>
                <th>Pending Fines</th>
                <th>Paid Fines</th>
                <th>Overdue Books</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($search_matches as $sid):
                $sdata = $fines_data[$sid];
                $s_pending = 0; $s_paid = 0; $s_overdue = 0;
                foreach ($sdata['fines'] as $fine) {
                  if ($fine['status'] === 'pending') {
                    $s_pending += $fine['fine_amount'];
                    if ($fine['days_overdue'] > 0) $s_overdue++;
                  } else { $s_paid += $fine['fine_amount']; }
                }
                if ($status_filter === 'pending' && $s_pending == 0) continue;
                if ($status_filter === 'paid'    && $s_paid    == 0) continue;
              ?>
              <tr>
                <td><code style="font-size:11px;background:#f3f4f6;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($sid) ?></code></td>
                <td style="text-align:left;padding-left:14px;">
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div class="student-fines-avatar" style="width:28px;height:28px;font-size:11px;flex:0 0 28px;">
                      <?= strtoupper(substr($sdata['student_name'], 0, 1)) ?>
                    </div>
                    <span style="font-weight:600;font-size:12px;"><?= htmlspecialchars($sdata['student_name']) ?></span>
                  </div>
                </td>
                <td style="text-align:left;padding-left:14px;font-size:11px;color:#6b7a99;"><?= htmlspecialchars($sdata['course']) ?></td>
                <td><?= htmlspecialchars($sdata['year_level']) ?></td>
                <td class="fine-amount"><?= $s_pending > 0 ? '₱' . number_format($s_pending, 2) : '<span style="color:#28a745;font-weight:600;">—</span>' ?></td>
                <td style="color:#28a745;font-weight:700;font-size:12px;"><?= $s_paid > 0 ? '₱' . number_format($s_paid, 2) : '—' ?></td>
                <td>
                  <?php if ($s_overdue > 0): ?>
                    <span class="status-badge status-pending"><?= $s_overdue ?> book<?= $s_overdue !== 1 ? 's' : '' ?></span>
                  <?php else: ?>
                    <span class="status-badge status-paid">None</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="?status_filter=<?= urlencode($status_filter) ?>&student_id=<?= urlencode($sid) ?>"
                     class="btn-fines btn-fines-success" style="text-decoration:none;padding:5px 10px;">
                    View
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($search_query && !$student_data): ?>
        <div class="no-data-fines">
          No student found matching <strong>"<?= htmlspecialchars($search_query) ?>"</strong>
          <p>Try a name (e.g. <strong>Emma</strong>), partial name (e.g. <strong>Wat</strong>), or a Student ID (e.g. <strong>202482628</strong>)</p>
        </div>

      <?php else: ?>

        <!-- ── All-students fines overview ─────────────────────── -->
        <div class="section-fines-title">
          <span>All Students with Fines</span>
        </div>

        <div class="table-fines-wrapper">
          <table class="table-fines">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Year</th>
                <th>Pending Fines</th>
                <th>Paid Fines</th>
                <th>Overdue Books</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($fines_data as $sid => $sdata):
                $s_pending = 0; $s_paid = 0; $s_overdue = 0;
                foreach ($sdata['fines'] as $fine) {
                  if ($fine['status'] === 'pending') {
                    $s_pending += $fine['fine_amount'];
                    if ($fine['days_overdue'] > 0) $s_overdue++;
                  } else {
                    $s_paid += $fine['fine_amount'];
                  }
                }
                // Apply status filter to list view
                if ($status_filter === 'pending' && $s_pending == 0) continue;
                if ($status_filter === 'paid'    && $s_paid    == 0) continue;
              ?>
              <tr>
                <td><code style="font-size:11px;background:#f3f4f6;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($sid) ?></code></td>
                <td style="text-align:left;padding-left:14px;">
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div class="student-fines-avatar" style="width:28px;height:28px;font-size:11px;flex:0 0 28px;">
                      <?= strtoupper(substr($sdata['student_name'], 0, 1)) ?>
                    </div>
                    <span style="font-weight:600;font-size:12px;"><?= htmlspecialchars($sdata['student_name']) ?></span>
                  </div>
                </td>
                <td style="text-align:left;padding-left:14px;font-size:11px;color:#6b7a99;"><?= htmlspecialchars($sdata['course']) ?></td>
                <td><?= htmlspecialchars($sdata['year_level']) ?></td>
                <td class="fine-amount"><?= $s_pending > 0 ? '₱' . number_format($s_pending, 2) : '<span style="color:#28a745;font-weight:600;">—</span>' ?></td>
                <td style="color:#28a745;font-weight:700;font-size:12px;"><?= $s_paid > 0 ? '₱' . number_format($s_paid, 2) : '—' ?></td>
                <td>
                  <?php if ($s_overdue > 0): ?>
                    <span class="status-badge status-pending"><?= $s_overdue ?> book<?= $s_overdue !== 1 ? 's' : '' ?></span>
                  <?php else: ?>
                    <span class="status-badge status-paid">None</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="?status_filter=<?= urlencode($status_filter) ?>&student_id=<?= urlencode($sid) ?>"
                     class="btn-fines btn-fines-success" style="text-decoration:none;padding:5px 10px;">
                    View
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php endif; ?>

    </div><!-- /.fines-container -->

  </main>

</div><!-- /.main-wrapper -->

</body>
</html>