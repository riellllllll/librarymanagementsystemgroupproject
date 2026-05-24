<?php
require 'library_data.php';

$message = '';
$error = '';

if (!isset($_SESSION['borrowed_books']) || !is_array($_SESSION['borrowed_books'])) {
  $_SESSION['borrowed_books'] = [];
}

function redirect_with_message($type, $text) {
  header("Location: issue_book.php?$type=" . urlencode($text));
  exit();
}

function format_book_date($date) {
  if (empty($date)) {
    return '-';
  }

  $time = strtotime($date);
  return $time ? date('M d, Y', $time) : $date;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_book'])) {
  $book_id = trim($_POST['book_id'] ?? '');
  $student_id = trim($_POST['student_id'] ?? '');
  $issue_date = trim($_POST['issue_date'] ?? '');
  $due_date = trim($_POST['due_date'] ?? '');

  if ($book_id === '' || $student_id === '' || $issue_date === '' || $due_date === '') {
    $error = "All fields are required.";
  } elseif ($due_date < $issue_date) {
    $error = "Due date cannot be earlier than issue date.";
  } else {
    $book_index = find_book_index($book_id);

    if ($book_index === null) {
      $error = "Book ID was not found.";
    } elseif ((int)($_SESSION['books'][$book_index]['available'] ?? 0) <= 0) {
      $error = "This book has no available copies.";
    } else {
      $_SESSION['books'][$book_index]['available']--;

      $_SESSION['borrowed_books'][] = [
        'id' => uniqid('BRW-'),
        'request_id' => null,
        'student' => 'Manual Issue',
        'student_id' => $student_id,
        'book_id' => (int)$book_id,
        'book_title' => $_SESSION['books'][$book_index]['title'] ?? 'Unknown Book',
        'issue_date' => $issue_date,
        'due_date' => $due_date,
        'return_date' => '',
        'date' => date('M d, Y', strtotime($issue_date)),
        'status' => 'borrowed'
      ];

      redirect_with_message('msg', 'Book issued successfully.');
    }
  }
}

if (isset($_GET['return_id'])) {
  foreach ($_SESSION['borrowed_books'] as &$book) {
    if (($book['id'] ?? '') === $_GET['return_id']) {
      if (($book['status'] ?? '') === 'borrowed') {
        $book['status'] = 'returned';
        $book['return_date'] = date('Y-m-d');

        $book_index = find_book_index($book['book_id'] ?? 0);

        if ($book_index !== null) {
          $_SESSION['books'][$book_index]['available']++;
        }
      }

      redirect_with_message('msg', 'Book marked as returned.');
    }
  }

  unset($book);
  redirect_with_message('err', 'Record not found.');
}

if (isset($_GET['delete_id'])) {
  foreach ($_SESSION['borrowed_books'] as $index => $book) {
    if (($book['id'] ?? '') === $_GET['delete_id']) {
      if (($book['status'] ?? '') === 'borrowed') {
        $book_index = find_book_index($book['book_id'] ?? 0);

        if ($book_index !== null) {
          $_SESSION['books'][$book_index]['available']++;
        }
      }

      unset($_SESSION['borrowed_books'][$index]);
      $_SESSION['borrowed_books'] = array_values($_SESSION['borrowed_books']);

      redirect_with_message('msg', 'Record deleted successfully.');
    }
  }

  redirect_with_message('err', 'Record not found.');
}

if (isset($_GET['msg'])) {
  $message = $_GET['msg'];
}

if (isset($_GET['err'])) {
  $error = $_GET['err'];
}

$borrowedBooks = $_SESSION['borrowed_books'];

usort($borrowedBooks, function ($a, $b) {
  $statusA = $a['status'] ?? '';
  $statusB = $b['status'] ?? '';

  if ($statusA === $statusB) {
    return strtotime($b['issue_date'] ?? $b['date'] ?? 'now') - strtotime($a['issue_date'] ?? $a['date'] ?? 'now');
  }

  return $statusA === 'borrowed' ? -1 : 1;
});

$totalIssued = count($borrowedBooks);

$activeIssued = count(array_filter($borrowedBooks, function ($book) {
  return ($book['status'] ?? '') === 'borrowed';
}));

$overdue = count(array_filter($borrowedBooks, function ($book) {
  return ($book['status'] ?? '') === 'borrowed'
    && !empty($book['due_date'])
    && $book['due_date'] < date('Y-m-d');
}));

$returned = count(array_filter($borrowedBooks, function ($book) {
  return ($book['status'] ?? '') === 'returned'
    && ($book['return_date'] ?? '') === date('Y-m-d');
}));

$pending_count = pending_request_count();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Issue Book</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
  <link rel="stylesheet" href="../assets/issue_book.css">
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper issue-book-page">

  <header class="topbar">

    <span class="topbar-title">Issue Book</span>

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
      <h1>Issue Book</h1>
      <p>Issue books manually and track borrowed or returned records.</p>

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-sage">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-rust">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <div class="stats-grid issue-book-stats">

      <div class="stat-card">
        <div class="stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $totalIssued; ?></div>
        <div class="stat-label">Total Issued</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 7v14"></path>
            <path d="M3 6a7 7 0 0 1 9 1 7 7 0 0 1 9-1v13a7 7 0 0 0-9 1 7 7 0 0 0-9-1V6Z"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $activeIssued; ?></div>
        <div class="stat-label">Currently Issued</div>
      </div>

      <div class="stat-card stat-danger">
        <div class="stat-icon icon-danger">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7v6M12 17h.01"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $overdue; ?></div>
        <div class="stat-label">Overdue</div>
      </div>

      <div class="stat-card stat-sage">
        <div class="stat-icon icon-sage">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="m8 12 3 3 5-6"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $returned; ?></div>
        <div class="stat-label">Returned Today</div>
      </div>

    </div>

    <div class="card issue-book-card">
      <div class="card-body">

        <div class="card-title">Issue a Book</div>
        <p class="card-subtitle">Enter the book and student details below.</p>

        <form method="POST" action="issue_book.php">

          <div class="field-grid">
            <div class="field">
              <label for="book_id">Book ID <span>*</span></label>
              <div class="input-wrap">
                <input class="no-icon" type="number" id="book_id" name="book_id" placeholder="Enter Book ID" required>
              </div>
            </div>

            <div class="field">
              <label for="student_id">Student ID <span>*</span></label>
              <div class="input-wrap">
                <input class="no-icon" type="text" id="student_id" name="student_id" placeholder="Enter Student ID" required>
              </div>
            </div>
          </div>

          <div class="field-grid">
            <div class="field">
              <label for="issue_date">Issue Date <span>*</span></label>
              <div class="input-wrap">
                <input class="no-icon" type="date" id="issue_date" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>

            <div class="field">
              <label for="due_date">Due Date <span>*</span></label>
              <div class="input-wrap">
                <input class="no-icon" type="date" id="due_date" name="due_date" required>
              </div>
            </div>
          </div>

          <div class="issue-book-form-actions">
            <button type="submit" name="issue_book" class="btn-primary">Issue Book</button>
            <button type="reset" class="btn-outline">Clear</button>
          </div>

        </form>

      </div>
    </div>

    <div class="card issue-book-card">
      <div class="card-body">

        <div class="card-title">Issued Books Record</div>
        <p class="card-subtitle">Records from manual issuing and approved student requests.</p>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Book</th>
                <th>Student</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <?php if (!empty($borrowedBooks)): ?>
                <?php foreach ($borrowedBooks as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['id'] ?? $row['request_id'] ?? '-'); ?></td>

                    <td>
                      <?php echo htmlspecialchars($row['book_title'] ?? 'Unknown Book'); ?>
                      <br>
                      <small class="text-muted">Book ID: <?php echo htmlspecialchars($row['book_id'] ?? '-'); ?></small>
                    </td>

                    <td>
                      <?php echo htmlspecialchars($row['student'] ?? 'Unknown Student'); ?>
                      <br>
                      <small class="text-muted"><?php echo htmlspecialchars($row['student_id'] ?? '-'); ?></small>
                    </td>

                    <td><?php echo htmlspecialchars(format_book_date($row['issue_date'] ?? $row['date'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars(format_book_date($row['due_date'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars(format_book_date($row['return_date'] ?? '')); ?></td>

                    <td>
                      <?php if (($row['status'] ?? '') === 'returned'): ?>
                        <span class="badge badge-sage">Returned</span>
                      <?php else: ?>
                        <span class="badge badge-gold">Borrowed</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <div class="issue-book-table-actions">
                        <?php if (($row['status'] ?? '') === 'borrowed' && !empty($row['id'])): ?>
                          <a href="issue_book.php?return_id=<?php echo urlencode($row['id']); ?>" class="btn-outline btn-small" onclick="return confirm('Mark this book as returned?');">Return</a>
                        <?php endif; ?>

                        <?php if (!empty($row['id'])): ?>
                          <a href="issue_book.php?delete_id=<?php echo urlencode($row['id']); ?>" class="btn-danger btn-small" onclick="return confirm('Delete this record permanently?');">Delete</a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8">
                    <div class="empty-state">
                      <h3>No issued books found</h3>
                      <p>Borrowed book records will appear here.</p>
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