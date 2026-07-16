<?php
// ============================================================
// admin/return_book.php — DB-powered (UI unchanged)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../classes/BorrowRecord.php';

// Session guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

$message = '';
$error   = '';
$fine_per_day = 5;

$db     = new Database();
$conn   = $db->getConnection();
$borrow = new BorrowRecord($conn);
$borrow->updateOverdueStatuses();

function redirect_with_message($type, $text) {
  header("Location: return_book.php?$type=" . urlencode($text));
  exit();
}

function format_book_date($date) {
  if (empty($date)) return '-';
  $time = strtotime($date);
  return $time ? date('M d, Y', $time) : $date;
}

function days_overdue($due_date) {
  if (empty($due_date)) return 0;
  $due   = strtotime($due_date);
  $today = strtotime(date('Y-m-d'));
  if ($due && $today > $due) return (int)floor(($today - $due) / 86400);
  return 0;
}

// ── Admin confirms or rejects a student's return request ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_request_action'])) {
  $rid    = (int)($_POST['return_request_id'] ?? 0);
  $action = $_POST['return_request_action'] ?? '';

  if ($action === 'confirm') {
    $result = $borrow->confirmReturn($rid);
    if (is_array($result)) {
      $msg = $result['fine'] > 0
        ? 'Return confirmed. Fine added: PHP ' . number_format($result['fine'], 2) .
          ' for ' . $result['days'] . ' overdue day' . ($result['days'] > 1 ? 's' : '') . '.'
        : 'Return confirmed. No overdue fine.';
      redirect_with_message('msg', $msg);
    } else {
      redirect_with_message('err', is_string($result) ? $result : 'Borrow record not found.');
    }
  }

  if ($action === 'reject') {
    if ($borrow->rejectReturn($rid)) {
      redirect_with_message('err', 'Return request rejected. The book was not marked as returned.');
    }
    redirect_with_message('err', 'Return request not found.');
  }
}

if (isset($_GET['msg'])) $message = $_GET['msg'];
if (isset($_GET['err'])) $error   = $_GET['err'];

// ── Stats from DB ──
$activeRows = $borrow->getAllActive();
$totalBorrowed  = count($activeRows);
$overdueCount   = count(array_filter($activeRows, fn($r) => $r['status'] === 'overdue'));
$dueTodayCount  = count(array_filter($activeRows, fn($r) => $r['due_date'] === date('Y-m-d') && $r['status'] === 'active'));

$returnedTodayQ = $conn->query(
  "SELECT COUNT(*) AS c FROM borrow_records WHERE status = 'returned' AND return_date = CURDATE()"
);
$returnedToday  = (int)($returnedTodayQ->fetch_assoc()['c'] ?? 0);

// ── Pending return requests for the table ──
$db_pending = $borrow->getPendingReturns();
$pendingReturnRequests = [];
foreach ($db_pending as $r) {
  $pendingReturnRequests[] = [
    'id'           => (int)$r['id'],
    'borrow_id'    => (int)$r['id'],
    'student'      => $r['student_name'],
    'student_id'   => $r['student_number'],
    'book_id'      => str_pad((string)$r['book_id'], 2, '0', STR_PAD_LEFT),
    'book_title'   => $r['book_title'],
    'issue_date'   => $r['borrow_date'],
    'due_date'     => $r['due_date'],
    'requested_at' => date('Y-m-d'),  // when DB transitioned to pending_return (using today)
    'status'       => 'pending',
  ];
}

$pending_count = pending_request_count();
$currentPage   = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Return Book</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
  <link rel="stylesheet" href="../assets/issue_book.css">
  <link rel="stylesheet" href="../assets/return_book.css">
  <style>
    .return-request-fine {
      display: inline-flex;
      flex-direction: column;
      gap: 3px;
      font-size: 13px;
      font-weight: 700;
      line-height: 1.25;
    }

    .return-request-fine.has-fine {
      color: #b93a2e;
    }

    .return-request-fine.no-fine {
      color: #588157;
    }

    .return-request-fine small {
      color: #667085;
      font-size: 11px;
      font-weight: 500;
    }
  </style>
</head>
<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper issue-book-page">

  <header class="topbar">
    <span class="topbar-title">Return Book</span>
    <div class="topbar-spacer"></div>
    <a href="student_req.php" class="topbar-icon-btn" title="Student Borrow Requests">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
      </svg>
      <?php if ($pending_count > 0): ?>
        <span class="topbar-notif-dot"></span>
      <?php endif; ?>
    </a>
    <a href="admin_profile.php" class="topbar-icon-btn" title="Admin Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
      </svg>
    </a>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h1>Return Book</h1>
      <p>Confirm student return requests, mark books as returned, and calculate overdue fines.</p>
      <div class="gold-rule"><span></span><i>*</i><span></span></div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-sage"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-rust"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="stats-grid issue-book-stats">

      <div class="stat-card">
        <div class="stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $totalBorrowed; ?></div>
        <div class="stat-label">Currently Borrowed</div>
      </div>

      <div class="stat-card stat-warning">
        <div class="stat-icon icon-danger">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7v6M12 17h.01"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $overdueCount; ?></div>
        <div class="stat-label">Overdue</div>
      </div>

      <div class="stat-card stat-info">
        <div class="stat-icon" style="color:#f59e0b;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7v5l3 3"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $dueTodayCount; ?></div>
        <div class="stat-label">Due Today</div>
      </div>

      <div class="stat-card stat-sage">
        <div class="stat-icon icon-sage">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="m8 12 3 3 5-6"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $returnedToday; ?></div>
        <div class="stat-label">Returned Today</div>
      </div>

    </div>

    <div class="card issue-book-card">
      <div class="card-body">
        <div class="card-title">Student Return Requests</div>
        <p class="card-subtitle">When a student returns a book, the request appears here for admin confirmation.</p>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Request ID</th>
                <th>Book</th>
                <th>Student</th>
                <th>Requested</th>
                <th>Due Date</th>
                <th>Fine If Confirmed</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($pendingReturnRequests)): ?>
                <?php foreach ($pendingReturnRequests as $request):
                  $overdueDays = days_overdue($request['due_date'] ?? '');
                  $fineAmount = $overdueDays * $fine_per_day;
                ?>
                  <tr class="<?php echo $overdueDays > 0 ? 'overdue-row' : ''; ?>">
                    <td><?php echo htmlspecialchars($request['id'] ?? '-'); ?></td>
                    <td>
                      <?php echo htmlspecialchars($request['book_title'] ?? 'Unknown Book'); ?>
                      <br>
                      <small class="text-muted">Book ID: <?php echo htmlspecialchars($request['book_id'] ?? '-'); ?></small>
                    </td>
                    <td>
                      <?php echo htmlspecialchars($request['student'] ?? 'Unknown Student'); ?>
                      <br>
                      <small class="text-muted"><?php echo htmlspecialchars($request['student_id'] ?? '-'); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars(format_book_date($request['requested_at'] ?? '')); ?></td>
                    <td>
                      <?php echo htmlspecialchars(format_book_date($request['due_date'] ?? '')); ?>
                      <?php if ($overdueDays > 0): ?>
                        <span class="badge-warning"><?php echo $overdueDays; ?> day<?php echo $overdueDays > 1 ? 's' : ''; ?> overdue</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($fineAmount > 0): ?>
                        <span class="return-request-fine has-fine">
                          PHP <?php echo number_format($fineAmount, 2); ?> fine
                          <small><?php echo $overdueDays; ?> day<?php echo $overdueDays > 1 ? 's' : ''; ?> overdue</small>
                        </span>
                      <?php else: ?>
                        <span class="return-request-fine no-fine">
                          No fine
                          <small>Returned on time</small>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form method="POST" action="return_book.php" style="display:flex; gap:8px; flex-wrap:wrap;">
                        <input type="hidden" name="return_request_id" value="<?php echo htmlspecialchars($request['id'] ?? ''); ?>">
                        <button type="submit" name="return_request_action" value="confirm" class="btn-primary">Confirm Return</button>
                        <button type="submit" name="return_request_action" value="reject" class="btn-outline">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7">
                    <div class="empty-state">
                      <h3>No return requests</h3>
                      <p>Student return requests will appear here before the book is marked returned.</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>

</div>

</body>
</html>