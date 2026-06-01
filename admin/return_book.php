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
$error = '';

if (!isset($_SESSION['borrowed_books']) || !is_array($_SESSION['borrowed_books'])) {
  $_SESSION['borrowed_books'] = [];
}

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

// Process return via GET (table button)
if (isset($_GET['return_id'])) {
  $found = false;
  foreach ($_SESSION['borrowed_books'] as &$book) {
    if (($book['id'] ?? '') === $_GET['return_id']) {
      if (($book['status'] ?? '') === 'borrowed') {
        $book['status'] = 'returned';
        $book['return_date'] = date('Y-m-d');

        $book_index = find_book_index($book['book_id'] ?? 0);
        if ($book_index !== null) {
          $_SESSION['books'][$book_index]['available']++;
        }
        $found = true;
        redirect_with_message('msg', 'Book returned successfully.');
      } else {
        redirect_with_message('err', 'This book has already been returned.');
      }
    }
  }
  unset($book);
  if (!$found) {
    redirect_with_message('err', 'Record not found.');
  }
}

// Process return via POST (quick return form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
  $borrow_id = trim($_POST['borrow_id'] ?? '');

  if ($borrow_id === '') {
    $error = "Please enter a Borrow Record ID or Book ID.";
  } else {
    $found = false;
    foreach ($_SESSION['borrowed_books'] as &$book) {
      if (($book['id'] ?? '') === $borrow_id || (string)($book['book_id'] ?? '') === $borrow_id) {
        if (($book['status'] ?? '') === 'borrowed') {
          $book['status'] = 'returned';
          $book['return_date'] = date('Y-m-d');

          $book_index = find_book_index($book['book_id'] ?? 0);
          if ($book_index !== null) {
            $_SESSION['books'][$book_index]['available']++;
          }
          $found = true;
          redirect_with_message('msg', 'Book returned successfully.');
        } else {
          redirect_with_message('err', 'This book has already been returned.');
        }
      }
    }
    unset($book);
    if (!$found) {
      $error = "No active borrow record found with that ID.";
    }
  }
}

if (isset($_GET['msg'])) {
  $message = $_GET['msg'];
}

if (isset($_GET['err'])) {
  $error = $_GET['err'];
}

// Filter and sort borrowed books
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$borrowedBooks = array_filter($_SESSION['borrowed_books'], function ($book) {
  return ($book['status'] ?? '') === 'borrowed';
});

// Apply search
if ($search !== '') {
  $search_lower = strtolower($search);
  $borrowedBooks = array_filter($borrowedBooks, function ($book) use ($search_lower) {
    return
      str_contains(strtolower($book['student'] ?? ''), $search_lower) ||
      str_contains(strtolower($book['student_id'] ?? ''), $search_lower) ||
      str_contains(strtolower($book['book_title'] ?? ''), $search_lower) ||
      str_contains((string)($book['book_id'] ?? ''), $search_lower) ||
      str_contains(strtolower($book['id'] ?? ''), $search_lower);
  });
}

// Apply filter
if ($filter === 'overdue') {
  $borrowedBooks = array_filter($borrowedBooks, function ($book) {
    return !empty($book['due_date']) && $book['due_date'] < date('Y-m-d');
  });
} elseif ($filter === 'due_today') {
  $borrowedBooks = array_filter($borrowedBooks, function ($book) {
    return ($book['due_date'] ?? '') === date('Y-m-d');
  });
}

// Sort: overdue first, then by due date
usort($borrowedBooks, function ($a, $b) {
  $overdueA = !empty($a['due_date']) && $a['due_date'] < date('Y-m-d') ? 1 : 0;
  $overdueB = !empty($b['due_date']) && $b['due_date'] < date('Y-m-d') ? 1 : 0;
  if ($overdueA !== $overdueB) {
    return $overdueB - $overdueA;
  }
  return strtotime($a['due_date'] ?? '9999-12-31') - strtotime($b['due_date'] ?? '9999-12-31');
});

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