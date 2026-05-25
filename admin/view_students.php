<?php
require 'library_data.php';

$message = '';
$error = '';

// Make sure students array exists
if (!isset($_SESSION['students']) || !is_array($_SESSION['students'])) {
  $_SESSION['students'] = [];
}

function redirect_with_message($type, $text) {
  header("Location: view_student.php?$type=" . urlencode($text));
  exit();
}

function format_date($date) {
  if (empty($date)) return '-';
  $time = strtotime($date);
  return $time ? date('M d, Y', $time) : $date;
}

function get_initials($name) {
  $parts = explode(' ', trim($name));
  $initials = '';
  foreach ($parts as $part) {
    if (!empty($part)) {
      $initials .= strtoupper(substr($part, 0, 1));
      if (strlen($initials) >= 2) break;
    }
  }
  return $initials ?: 'ST';
}

function count_student_borrows($student_id) {
  if (!isset($_SESSION['borrowed_books']) || !is_array($_SESSION['borrowed_books'])) {
    return 0;
  }
  $count = 0;
  foreach ($_SESSION['borrowed_books'] as $book) {
    if (($book['student_id'] ?? '') === $student_id && ($book['status'] ?? '') === 'borrowed') {
      $count++;
    }
  }
  return $count;
}

function count_student_overdue($student_id) {
  if (!isset($_SESSION['borrowed_books']) || !is_array($_SESSION['borrowed_books'])) {
    return 0;
  }
  $count = 0;
  foreach ($_SESSION['borrowed_books'] as $book) {
    if (($book['student_id'] ?? '') === $student_id
      && ($book['status'] ?? '') === 'borrowed'
      && !empty($book['due_date'])
      && $book['due_date'] < date('Y-m-d')) {
      $count++;
    }
  }
  return $count;
}

// Handle delete
if (isset($_GET['delete_id'])) {
  $delete_id = $_GET['delete_id'];
  foreach ($_SESSION['students'] as $index => $student) {
    if (($student['id'] ?? '') === $delete_id) {
      if (count_student_borrows($delete_id) > 0) {
        redirect_with_message('err', 'Cannot delete student with active borrowed books.');
      }
      unset($_SESSION['students'][$index]);
      $_SESSION['students'] = array_values($_SESSION['students']);
      redirect_with_message('msg', 'Student deleted successfully.');
    }
  }
  redirect_with_message('err', 'Student not found.');
}

// Handle status toggle
if (isset($_GET['toggle_id'])) {
  $toggle_id = $_GET['toggle_id'];
  foreach ($_SESSION['students'] as &$student) {
    if (($student['id'] ?? '') === $toggle_id) {
      $current = $student['status'] ?? 'active';
      $student['status'] = ($current === 'active') ? 'inactive' : 'active';
      redirect_with_message('msg', 'Student status updated.');
    }
  }
  unset($student);
  redirect_with_message('err', 'Student not found.');
}

if (isset($_GET['msg'])) {
  $message = $_GET['msg'];
}
if (isset($_GET['err'])) {
  $error = $_GET['err'];
}

// Search, filter, sort
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'name';

$students = $_SESSION['students'];

// Search filter
if ($search !== '') {
  $search_lower = strtolower($search);
  $filtered = [];
  foreach ($students as $s) {
    $haystack = strtolower(($s['name'] ?? '') . ' ' . ($s['id'] ?? '') . ' ' . ($s['email'] ?? '') . ' ' . ($s['course'] ?? '') . ' ' . ($s['year'] ?? ''));
    if (strpos($haystack, $search_lower) !== false) {
      $filtered[] = $s;
    }
  }
  $students = $filtered;
}

// Status filter
if ($status_filter !== 'all') {
  $filtered = [];
  foreach ($students as $s) {
    if (($s['status'] ?? 'active') === $status_filter) {
      $filtered[] = $s;
    }
  }
  $students = $filtered;
}

// Sort
usort($students, function ($a, $b) use ($sort_by) {
  switch ($sort_by) {
    case 'id':
      return strcmp($a['id'] ?? '', $b['id'] ?? '');
    case 'recent':
      return strtotime($b['registered'] ?? '0') - strtotime($a['registered'] ?? '0');
    case 'borrows':
      $ba = count_student_borrows($a['id'] ?? '');
      $bb = count_student_borrows($b['id'] ?? '');
      return $bb - $ba;
    case 'name':
    default:
      return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
  }
});

// Pagination
$per_page = 10;
$total_students = count($students);
$total_pages = max(1, ceil($total_students / $per_page));
$current_page = max(1, min($total_pages, intval($_GET['page'] ?? 1)));
$offset = ($current_page - 1) * $per_page;
$paginated_students = array_slice($students, $offset, $per_page);

// Stats
$totalAll = count($_SESSION['students']);
$activeCount = 0;
$inactiveCount = 0;
foreach ($_SESSION['students'] as $s) {
  if (($s['status'] ?? 'active') === 'active') $activeCount++;
  else $inactiveCount++;
}
$totalBorrowing = 0;
foreach ($_SESSION['students'] as $s) {
  if (count_student_borrows($s['id'] ?? '') > 0) $totalBorrowing++;
}

// Safe call for pending count
$pending_count = 0;
if (function_exists('pending_request_count')) {
  $pending_count = pending_request_count();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Students</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
  <link rel="stylesheet" href="../assets/issue_book.css">
  <link rel="stylesheet" href="../assets/view_student.css">
</head>
<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper view-student-page">

  <header class="topbar">
    <span class="topbar-title">View Students</span>
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
      <h1>View Students</h1>
      <p>Manage and view all registered student records.</p>
      <div class="gold-rule"><span></span><i>*</i><span></span></div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-sage"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-rust"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">

      <div class="stat-card">
        <div class="stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $totalAll; ?></div>
        <div class="stat-label">Total Students</div>
      </div>

      <div class="stat-card stat-green">
        <div class="stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <path d="m9 12 2 2 4-4"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $activeCount; ?></div>
        <div class="stat-label">Active</div>
      </div>

      <div class="stat-card stat-blue">
        <div class="stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $totalBorrowing; ?></div>
        <div class="stat-label">Currently Borrowing</div>
      </div>

      <div class="stat-card stat-purple">
        <div class="stat-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7v6M12 17h.01"></path>
          </svg>
        </div>
        <div class="stat-value"><?php echo $inactiveCount; ?></div>
        <div class="stat-label">Inactive</div>
      </div>

    </div>

    <!-- Student Directory Card -->
    <div class="card issue-book-card">
      <div class="card-body">
        <div class="card-title">Student Directory</div>
        <p class="card-subtitle">Search, filter, and manage student records.</p>

        <form method="GET" action="view_student.php" class="student-search-bar">
          <div class="field">
            <label for="search">Search</label>
            <div class="input-wrap">
              <input class="no-icon" type="text" id="search" name="search" placeholder="Name, ID, email, or course..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
          </div>
          <div class="field">
            <label for="status">Status</label>
            <div class="input-wrap">
              <select class="no-icon" id="status" name="status">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label for="sort">Sort By</label>
            <div class="input-wrap">
              <select class="no-icon" id="sort" name="sort">
                <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                <option value="id" <?php echo $sort_by === 'id' ? 'selected' : ''; ?>>Student ID</option>
                <option value="recent" <?php echo $sort_by === 'recent' ? 'selected' : ''; ?>>Recently Added</option>
                <option value="borrows" <?php echo $sort_by === 'borrows' ? 'selected' : ''; ?>>Most Borrows</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn-primary">Apply</button>
          <?php if ($search !== '' || $status_filter !== 'all' || $sort_by !== 'name'): ?>
            <a href="view_student.php" class="btn-outline">Reset</a>
          <?php endif; ?>
        </form>

        <!-- Table -->
        <div class="student-table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>ID</th>
                <th>Course / Year</th>
                <th>Email</th>
                <th>Registered</th>
                <th>Borrows</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($paginated_students)): ?>
                <?php foreach ($paginated_students as $student):
                  $student_id = $student['id'] ?? '';
                  $student_name = $student['name'] ?? 'Unknown';
                  $student_status = $student['status'] ?? 'active';
                  $borrow_count = count_student_borrows($student_id);
                  $overdue_count = count_student_overdue($student_id);
                  $initials = get_initials($student_name);
                ?>
                  <tr>
                    <td>
                      <div style="display:flex; align-items:center;">
                        <span class="student-avatar"><?php echo htmlspecialchars($initials); ?></span>
                        <div>
                          <div class="student-name"><?php echo htmlspecialchars($student_name); ?></div>
                          <?php if ($overdue_count > 0): ?>
                            <div style="font-size:0.75rem; color:#e74c3c; margin-top:2px;"><?php echo $overdue_count; ?> overdue book<?php echo $overdue_count > 1 ? 's' : ''; ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </td>
                    <td><span class="student-id"><?php echo htmlspecialchars($student_id); ?></span></td>
                    <td>
                      <?php echo htmlspecialchars($student['course'] ?? '-'); ?>
                      <br><small class="text-muted"><?php echo htmlspecialchars($student['year'] ?? ''); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars(format_date($student['registered'] ?? '')); ?></td>
                    <td><span class="borrow-count"><?php echo $borrow_count; ?></span></td>
                    <td>
                      <?php if ($student_status === 'active'): ?>
                        <span class="badge-active">Active</span>
                      <?php elseif ($student_status === 'inactive'): ?>
                        <span class="badge-inactive">Inactive</span>
                      <?php else: ?>
                        <span class="badge-suspended">Suspended</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="student-actions">
                        <a href="edit_student.php?id=<?php echo urlencode($student_id); ?>" class="btn-outline btn-small">Edit</a>
                        <a href="view_student.php?toggle_id=<?php echo urlencode($student_id); ?>" class="btn-outline btn-small" onclick="return confirm('Change status for <?php echo htmlspecialchars($student_name); ?>?');">
                          <?php echo $student_status === 'active' ? 'Deactivate' : 'Activate'; ?>
                        </a>
                        <a href="view_student.php?delete_id=<?php echo urlencode($student_id); ?>" class="btn-danger btn-small" onclick="return confirm('Delete <?php echo htmlspecialchars($student_name); ?>? This cannot be undone.');">Delete</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8">
                    <div class="empty-state">
                      <h3>No students found</h3>
                      <p><?php echo $search !== '' ? 'Try a different search term.' : 'No student records available.'; ?></p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-wrap">
          <div class="pagination-info">
            Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $per_page, $total_students); ?> of <?php echo $total_students; ?> students
          </div>
          <div class="pagination-buttons">
            <?php if ($current_page > 1): ?>
              <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_by; ?>">← Prev</a>
            <?php else: ?>
              <span class="disabled">← Prev</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <?php if ($i === $current_page): ?>
                <span class="current"><?php echo $i; ?></span>
              <?php else: ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_by; ?>"><?php echo $i; ?></a>
              <?php endif; ?>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
              <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_by; ?>">Next →</a>
            <?php else: ?>
              <span class="disabled">Next →</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </main>

</div>

</body>
</html>