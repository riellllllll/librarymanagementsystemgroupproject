<?php
// ============================================================
// admin/issue_book.php — DB-powered (UI unchanged)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../classes/BorrowRecord.php';
require_once __DIR__ . '/../classes/Book.php';

// Session guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

$message = '';
$error   = '';

$db     = new Database();
$conn   = $db->getConnection();
$borrow = new BorrowRecord($conn);
$borrow->updateOverdueStatuses();

function redirect_with_message($type, $text) {
  header("Location: issue_book.php?$type=" . urlencode($text));
  exit();
}

function format_book_date($date) {
  if (empty($date)) return '-';
  $time = strtotime($date);
  return $time ? date('M d, Y', $time) : $date;
}

// ── Issue Book (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_book'])) {
  $book_id    = trim($_POST['book_id']    ?? '');
  $student_id = trim($_POST['student_id'] ?? '');   // student NUMBER (e.g. 101)
  $issue_date = date('Y-m-d');                      // face-to-face issuing: always today, ignore any posted value
  $due_date   = trim($_POST['due_date']   ?? '');
  $max_due    = date('Y-m-d', strtotime('+7 days'));

  if ($book_id === '' || $student_id === '' || $due_date === '') {
    $error = "All fields are required.";
  } elseif ($due_date < $issue_date) {
    $error = "Due date cannot be earlier than the issue date.";
  } elseif ($due_date > $max_due) {
    $error = "Due date cannot be more than 7 days from the issue date.";
  } else {
    // Look up student DB id from student_number
    $student = $borrow->findStudent($student_id);
    if (!$student) {
      $error = "Student ID was not found.";
    } else {
      $result = $borrow->issue(
        (int)$student['id'], (int)$book_id, $issue_date, $due_date,
        (int)$_SESSION['user_id']
      );
      if ($result === true) {
        redirect_with_message('msg', 'Book issued successfully.');
      } else {
        $error = is_string($result) ? $result : 'Failed to issue book.';
      }
    }
  }
}

// ── Mark returned (GET return_id) ──
if (isset($_GET['return_id'])) {
  $rid = (int)$_GET['return_id'];
  $result = $borrow->confirmReturn($rid);
  if (is_array($result)) {
    redirect_with_message('msg', 'Book marked as returned.');
  } else {
    redirect_with_message('err', is_string($result) ? $result : 'Record not found.');
  }
}

// ── Delete record (GET delete_id) ──
if (isset($_GET['delete_id'])) {
  $rid = (int)$_GET['delete_id'];
  // If record was still active, restore the copy first
  $rec = $borrow->getById($rid);
  if ($rec) {
    if (in_array($rec['status'], ['active','overdue','pending_return'])) {
      $conn->query("UPDATE books SET copies_available = copies_available + 1 WHERE id = " . (int)$rec['book_id']);
    }
    $del = $conn->prepare("DELETE FROM borrow_records WHERE id = ?");
    $del->bind_param('i', $rid);
    if ($del->execute()) {
      $del->close();
      redirect_with_message('msg', 'Record deleted successfully.');
    }
    $del->close();
  }
  redirect_with_message('err', 'Record not found.');
}

if (isset($_GET['msg'])) $message = $_GET['msg'];
if (isset($_GET['err'])) $error   = $_GET['err'];

// ── Load all records & map to UI shape ──
$rows = $borrow->getAll();
$borrowedBooks = [];
foreach ($rows as $r) {
  $borrowedBooks[] = [
    'id'          => (int)$r['id'],
    'request_id'  => null,
    'student'     => $r['student_name'],
    'student_id'  => $r['student_number'],
    'book_id'     => (int)$r['book_id'],
    'book_title'  => $r['book_title'],
    'issue_date'  => $r['borrow_date'],
    'due_date'    => $r['due_date'],
    'return_date' => $r['return_date'] ?? '',
    'date'        => date('M d, Y', strtotime($r['borrow_date'])),
    // UI uses 'borrowed' / 'returned'. DB has active/overdue/pending_return/returned.
    'status'      => $r['status'] === 'returned' ? 'returned' : 'borrowed',
  ];
}

// Sort: active first, then by date desc
usort($borrowedBooks, function ($a, $b) {
  $statusA = $a['status'] ?? '';
  $statusB = $b['status'] ?? '';
  if ($statusA === $statusB) {
    return strtotime($b['issue_date'] ?? 'now') - strtotime($a['issue_date'] ?? 'now');
  }
  return $statusA === 'borrowed' ? -1 : 1;
});

// ── Stats ──
$totalIssued  = count($borrowedBooks);
$activeIssued = count(array_filter($borrowedBooks, fn($b) => ($b['status'] ?? '') === 'borrowed'));
$overdue      = count(array_filter($rows,          fn($r) => $r['status'] === 'overdue'));
$returned     = count(array_filter($rows,          fn($r) => $r['status'] === 'returned' && ($r['return_date'] ?? '') === date('Y-m-d')));

$pending_count = pending_request_count();
$currentPage   = basename($_SERVER['PHP_SELF']);
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
                <span class="ico">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                  </svg>
                </span>
                <input type="number" id="book_id" name="book_id" placeholder="Enter Book ID" min="0" required>
              </div>
            </div>

            <div class="field">
              <label for="student_id">Student ID <span>*</span></label>
              <div class="input-wrap">
                <span class="ico">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/>
                  </svg>
                </span>
                <input type="text" id="student_id" name="student_id" placeholder="Enter Student ID" required>
              </div>
            </div>
          </div>

          <div class="field-grid">
            <div class="field">
              <label for="issue_date">Issue Date <span>*</span></label>
              <div class="input-wrap">
                <input class="no-icon" type="date" id="issue_date" value="<?php echo date('Y-m-d'); ?>" disabled>
                <input type="hidden" name="issue_date" value="<?php echo date('Y-m-d'); ?>">
              </div>
              <small class="field-hint">Issuing is face-to-face, so this is always today's date.</small>
            </div>

            <div class="field">
              <label for="due_date">Due Date <span>*</span></label>
              <div class="input-wrap">
                <input class="no-icon" type="date" id="due_date" name="due_date"
                       min="<?php echo date('Y-m-d'); ?>"
                       max="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"
                       value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"
                       required>
              </div>
              <small class="field-hint">Choose a date from today up to 7 days from now.</small>
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

        <div class="issue-book-record-head">
          <div>
            <div class="card-title">Issued Books Record</div>
            <p class="card-subtitle">Records from manual issuing and approved student requests.</p>
          </div>

          <div class="issue-book-filters">
            <div class="issue-search-wrap">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"/>
                <path d="m21 21-4.3-4.3"/>
              </svg>
              <input type="text" id="issueSearchInput" placeholder="Search student or book...">
            </div>

            <select id="issueStatusFilter">
              <option value="">All Status</option>
              <option value="borrowed">Borrowed</option>
              <option value="returned">Returned</option>
            </select>
          </div>
        </div>

        <div class="table-wrap">
          <table id="issueBookTable">
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
                  <tr data-status="<?php echo htmlspecialchars($row['status'] ?? ''); ?>"
                      data-search="<?php echo htmlspecialchars(strtolower(($row['student'] ?? '') . ' ' . ($row['student_id'] ?? '') . ' ' . ($row['book_title'] ?? '') . ' ' . ($row['book_id'] ?? ''))); ?>">
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

          <?php if (!empty($borrowedBooks)): ?>
            <div class="empty-state" id="issueNoMatches" style="display:none;">
              <h3>No matching records</h3>
              <p>Try a different search term or status filter.</p>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>

  </main>

</div>

<script>
(function () {
  var searchInput  = document.getElementById('issueSearchInput');
  var statusSelect = document.getElementById('issueStatusFilter');
  var table        = document.getElementById('issueBookTable');
  var noMatches     = document.getElementById('issueNoMatches');
  if (!table) return;

  var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-search]'));

  function applyFilters() {
    var term   = (searchInput ? searchInput.value : '').trim().toLowerCase();
    var status = statusSelect ? statusSelect.value : '';
    var visibleCount = 0;

    rows.forEach(function (row) {
      var matchesTerm   = !term || row.getAttribute('data-search').indexOf(term) !== -1;
      var matchesStatus = !status || row.getAttribute('data-status') === status ||
                           (status === 'borrowed' && row.getAttribute('data-status') !== 'returned');
      var visible = matchesTerm && matchesStatus;
      row.style.display = visible ? '' : 'none';
      if (visible) visibleCount++;
    });

    if (noMatches) {
      noMatches.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
      table.style.display = (visibleCount === 0 && rows.length > 0) ? 'none' : '';
    }
  }

  if (searchInput)  searchInput.addEventListener('input', applyFilters);
  if (statusSelect) statusSelect.addEventListener('change', applyFilters);
})();
</script>

</body>
</html>