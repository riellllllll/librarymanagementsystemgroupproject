<?php
require 'library_data.php';

$message = '';
$error = '';
$fine_per_day = 5;

if (!isset($_SESSION['borrowed_books']) || !is_array($_SESSION['borrowed_books'])) {
  $_SESSION['borrowed_books'] = [];
}

function has_pending_return_requests() {
  foreach ($_SESSION['return_requests'] as $request) {
    if (($request['status'] ?? '') === 'pending') {
      return true;
    }
  }

  return false;
}

function demo_requests_already_added() {
  foreach ($_SESSION['return_requests'] as $request) {
    if (str_starts_with($request['id'] ?? '', 'RET-DEMO-')) {
      return true;
    }
  }
  return false;
}

function add_demo_return_request($request) {
  $has_borrow = false;
  $has_request = false;

  foreach ($_SESSION['borrowed_books'] as $index => $book) {
    if (($book['id'] ?? '') === $request['borrow_id']) {
      $has_borrow = true;
      $_SESSION['borrowed_books'][$index]['student'] = $request['student'];
      $_SESSION['borrowed_books'][$index]['student_id'] = $request['student_id'];
      $_SESSION['borrowed_books'][$index]['book_id'] = $request['book_id'];
      $_SESSION['borrowed_books'][$index]['book_title'] = $request['book_title'];
      $_SESSION['borrowed_books'][$index]['issue_date'] = $request['issue_date'];
      $_SESSION['borrowed_books'][$index]['due_date'] = $request['due_date'];
      $_SESSION['borrowed_books'][$index]['return_date'] = '';
      $_SESSION['borrowed_books'][$index]['days_overdue'] = 0;
      $_SESSION['borrowed_books'][$index]['fine_amount'] = 0;
      $_SESSION['borrowed_books'][$index]['fine_status'] = 'none';
      $_SESSION['borrowed_books'][$index]['status'] = 'borrowed';
      break;
    }
  }

  foreach ($_SESSION['return_requests'] as $index => $existing_request) {
    if (($existing_request['id'] ?? '') === $request['id']) {
      $has_request = true;
      $_SESSION['return_requests'][$index] = $request;
      break;
    }
  }

  if (!$has_borrow) {
    $_SESSION['borrowed_books'][] = [
      'id' => $request['borrow_id'],
      'request_id' => null,
      'student' => $request['student'],
      'student_id' => $request['student_id'],
      'book_id' => $request['book_id'],
      'book_title' => $request['book_title'],
      'issue_date' => $request['issue_date'],
      'due_date' => $request['due_date'],
      'return_date' => '',
      'date' => date('M d, Y', strtotime($request['issue_date'])),
      'status' => 'borrowed'
    ];
  }

  if (!$has_request) {
    $_SESSION['return_requests'][] = $request;
  }
}

if (!demo_requests_already_added()) {
  add_demo_return_request([
    'id' => 'RET-DEMO-001',
    'borrow_id' => 'BRW-DEMO-001',
    'student' => 'Juan Dela Cruz',
    'student_id' => '101',
    'book_id' => '01',
    'book_title' => 'The Great Gatsby',
    'issue_date' => '2026-05-10',
    'due_date' => date('Y-m-d', strtotime('-7 days')),
    'requested_at' => date('Y-m-d'),
    'status' => 'pending'
  ]);

  add_demo_return_request([
    'id' => 'RET-DEMO-002',
    'borrow_id' => 'BRW-DEMO-002',
    'student' => 'Maria Santos',
    'student_id' => '102',
    'book_id' => '05',
    'book_title' => 'Clean Code',
    'issue_date' => date('Y-m-d', strtotime('-3 days')),
    'due_date' => date('Y-m-d', strtotime('+4 days')),
    'requested_at' => date('Y-m-d'),
    'status' => 'pending'
  ]);

  add_demo_return_request([
    'id' => 'RET-DEMO-003',
    'borrow_id' => 'BRW-DEMO-003',
    'student' => 'Lina Zhang',
    'student_id' => '103',
    'book_id' => '04',
    'book_title' => 'Sapiens',
    'issue_date' => date('Y-m-d', strtotime('-14 days')),
    'due_date' => date('Y-m-d', strtotime('-1 day')),
    'requested_at' => date('Y-m-d'),
    'status' => 'pending'
  ]);

  add_demo_return_request([
    'id' => 'RET-DEMO-004',
    'borrow_id' => 'BRW-DEMO-004',
    'student' => 'Oliver Chen',
    'student_id' => '104',
    'book_id' => '08',
    'book_title' => 'Calculus Made Easy',
    'issue_date' => date('Y-m-d', strtotime('-5 days')),
    'due_date' => date('Y-m-d'),
    'requested_at' => date('Y-m-d'),
    'status' => 'pending'
  ]);
}

function redirect_with_message($type, $text) {
  header("Location: return_book.php?$type=" . urlencode($text));
  exit();
}

function format_book_date($date) {
  if (empty($date)) {
    return '-';
  }
  $time = strtotime($date);
  return $time ? date('M d, Y', $time) : $date;
}

function days_overdue($due_date) {
  if (empty($due_date)) return 0;
  $due = strtotime($due_date);
  $today = strtotime(date('Y-m-d'));
  if ($due && $today > $due) {
    return floor(($today - $due) / 86400);
  }
  return 0;
}

function return_success_message($days_overdue, $fine_amount) {
  if ($fine_amount > 0) {
    return 'Return confirmed. Fine added: PHP ' . number_format($fine_amount, 2) . ' for ' . $days_overdue . ' overdue day' . ($days_overdue > 1 ? 's' : '') . '.';
  }

  return 'Return confirmed. No overdue fine.';
}

function process_return_record(&$book, $fine_per_day) {
  $overdue_days = days_overdue($book['due_date'] ?? '');
  $fine_amount = $overdue_days * $fine_per_day;

  $book['status'] = 'returned';
  $book['return_date'] = date('Y-m-d');
  $book['days_overdue'] = $overdue_days;
  $book['fine_amount'] = $fine_amount;
  $book['fine_status'] = $fine_amount > 0 ? 'pending' : 'none';

  if ($fine_amount > 0) {
    $_SESSION['pending_fines_total'] = pending_fines_total() + $fine_amount;
  }

  $book_index = find_book_index($book['book_id'] ?? 0);
  if ($book_index !== null) {
    $_SESSION['books'][$book_index]['available']++;
  }

  return [$overdue_days, $fine_amount];
}

// Admin confirms or rejects a student's return request.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_request_action'])) {
  $request_id = $_POST['return_request_id'] ?? '';
  $action = $_POST['return_request_action'] ?? '';

  $found_request = false;

  foreach ($_SESSION['return_requests'] as $request_index => $request) {
    if (($request['id'] ?? '') === $request_id && ($request['status'] ?? '') === 'pending') {
      $found_request = true;

      if ($action === 'confirm') {
        $borrow_found = false;

        foreach ($_SESSION['borrowed_books'] as $book_index => $book) {
          if (($book['id'] ?? '') === ($request['borrow_id'] ?? '') && ($book['status'] ?? '') === 'borrowed') {
            $borrow_found = true;

            [$overdue_days, $fine_amount] = process_return_record($_SESSION['borrowed_books'][$book_index], $fine_per_day);

            $_SESSION['return_requests'][$request_index]['status'] = 'confirmed';
            $_SESSION['return_requests'][$request_index]['confirmed_at'] = date('Y-m-d');
            $_SESSION['return_requests'][$request_index]['days_overdue'] = $overdue_days;
            $_SESSION['return_requests'][$request_index]['fine_amount'] = $fine_amount;

            session_write_close();
            redirect_with_message('msg', return_success_message($overdue_days, $fine_amount));
          }
        }

        if (!$borrow_found) {
          session_write_close();
          redirect_with_message('err', 'Borrow record not found or already returned.');
        }
      }

      if ($action === 'reject') {
        $_SESSION['return_requests'][$request_index]['status'] = 'rejected';
        $_SESSION['return_requests'][$request_index]['rejected_at'] = date('Y-m-d');

        session_write_close();
        redirect_with_message('err', 'Return request rejected. The book was not marked as returned.');
      }

      break;
    }
  }

  if (!$found_request) {
    redirect_with_message('err', 'Return request not found.');
  }
}

if (isset($_GET['msg'])) {
  $message = $_GET['msg'];
}

if (isset($_GET['err'])) {
  $error = $_GET['err'];
}

$totalBorrowed = count(array_filter($_SESSION['borrowed_books'], function ($b) {
  return ($b['status'] ?? '') === 'borrowed';
}));

$overdueCount = count(array_filter($_SESSION['borrowed_books'], function ($b) {
  return ($b['status'] ?? '') === 'borrowed' && !empty($b['due_date']) && $b['due_date'] < date('Y-m-d');
}));

$dueTodayCount = count(array_filter($_SESSION['borrowed_books'], function ($b) {
  return ($b['status'] ?? '') === 'borrowed' && ($b['due_date'] ?? '') === date('Y-m-d');
}));

$returnedToday = count(array_filter($_SESSION['borrowed_books'], function ($b) {
  return ($b['status'] ?? '') === 'returned' && ($b['return_date'] ?? '') === date('Y-m-d');
}));

$pendingReturnRequests = array_filter($_SESSION['return_requests'], function ($request) {
  return ($request['status'] ?? '') === 'pending';
});

$pending_count = pending_request_count();
$currentPage = basename($_SERVER['PHP_SELF']);
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