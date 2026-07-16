<?php
// ============================================================
// admin/manage_students.php — DB-powered (UI unchanged)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../classes/User.php';

// ── Page context ──
$current_page  = basename($_SERVER['PHP_SELF']);
$pending_count = pending_request_count();
$request_badge = $pending_count;
$archive_badge = archived_book_count();

$db   = new Database();
$conn = $db->getConnection();
$usr  = new User($conn);

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);

// ── Handle form submissions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_student') {
        $data = [
            'student_number' => trim($_POST['student_id']  ?? ''),
            'first_name'     => trim($_POST['first_name']  ?? ''),
            'last_name'      => trim($_POST['last_name']   ?? ''),
            'email'          => strtolower(trim($_POST['email'] ?? '')),
            'course'         => $_POST['course']           ?? '',
            'year_level'     => $_POST['year']             ?? '',
            'password'       => $_POST['password']         ?? 'CvSU@2026',
        ];
        $_SESSION['toast'] = $usr->addStudent($data)
            ? ['type' => 'success', 'message' => 'Student account added successfully!']
            : ['type' => 'error',   'message' => 'Failed to add student. Student number or email may already exist.'];
        header('Location: manage_students.php');
        exit;
    }

    if ($_POST['action'] === 'edit_student') {
        $id   = (int)($_POST['edit_id'] ?? 0);
        $data = [
            'student_number' => trim($_POST['edit_student_id']  ?? ''),
            'first_name'     => trim($_POST['edit_first_name']  ?? ''),
            'last_name'      => trim($_POST['edit_last_name']   ?? ''),
            'middle_name'    => trim($_POST['edit_middle_name'] ?? ''),
            'email'          => strtolower(trim($_POST['edit_email'] ?? '')),
            'course'         => $_POST['edit_course']     ?? '',
            'year_level'     => $_POST['edit_year']       ?? '',
            'status'         => $_POST['edit_status']     ?? 'active',
        ];
        $result = $usr->editStudent($id, $data);
        $_SESSION['toast'] = $result === true
            ? ['type' => 'success', 'message' => 'Student account updated successfully!']
            : ['type' => 'error',   'message' => is_string($result) ? $result : 'Failed to update student.'];
        header('Location: manage_students.php');
        exit;
    }

    if ($_POST['action'] === 'remove_student') {
        $id     = (int)($_POST['student_id'] ?? 0);
        $result = $usr->deleteStudent($id);
        $_SESSION['toast'] = $result === true
            ? ['type' => 'success', 'message' => 'Student account removed successfully!']
            : ['type' => 'error',   'message' => is_string($result) ? $result : 'Failed to remove student.'];
        header('Location: manage_students.php');
        exit;
    }
}

// ── Load students from DB ──
// Map DB row → the field names this page's UI expects
$db_students = $usr->getAllStudents();
$students    = [];
foreach ($db_students as $s) {
    $students[] = [
        'id'         => (int)$s['id'],
        'student_id' => $s['student_number'],          // UI calls it 'student_id'
        'first_name' => $s['first_name'],
        'last_name'  => $s['last_name'],
        'middle_name'=> $s['middle_name'] ?? '',
        'email'      => $s['email'],
        'course'     => $s['course']     ?? '',
        'year'       => $s['year_level'] ?? '',         // UI calls it 'year'
        'status'     => $s['status']     ?? 'active',
        'borrowed'   => (int)$s['active_borrows'],
        'fines'      => (float)$s['total_fines'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Students — CvSU Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/student.css">
<link rel="stylesheet" href="../assets/managestudent.css">
<link rel="stylesheet" href="../assets/adminStyle.css">
<style>
  .ms-sort-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
  }
  .ms-sort-icon {
    position: absolute;
    left: 14px;
    pointer-events: none;
    color: inherit;
    opacity: 0.7;
  }
  .ms-sort-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 10px 34px 10px 38px;
    border-radius: 10px;
    border: 1px solid rgba(0,0,0,0.12);
    background: #fff;
    cursor: pointer;
    color: inherit;
  }
  .ms-sort-select:hover { background: rgba(0,0,0,0.02); }
  .ms-sort-select:focus { outline: none; border-color: #8b3a2a; }
  .ms-sort-arrow {
    position: absolute;
    right: 12px;
    pointer-events: none;
    opacity: 0.6;
  }
</style>
</head>
<body>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
$request_badge = $pending_count ?? 0;
$archive_badge = isset($_SESSION['archived_books']) ? count($_SESSION['archived_books']) : 0;
?>

<?php include __DIR__ . "/sideBar.php"; ?>

<input type="checkbox" id="logoutModalToggle" class="logout-modal-check">

<div class="modal-backdrop logout-modal" role="dialog" aria-modal="true">
  <div class="modal" style="max-width:400px;">
    <div class="modal-top" style="background:linear-gradient(90deg,#8b3a2a,#c06040,#8b3a2a);"></div>

    <label for="logoutModalToggle" class="modal-close" aria-label="Cancel">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </label>

    <div class="modal-body" style="text-align:center;">
      <div style="width:60px;height:60px;border-radius:50%;background:rgba(192,57,43,0.1);border:1px solid rgba(192,57,43,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="1.8">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
      </div>

      <div class="modal-title" style="font-size:1.15rem;">
        Log Out?
      </div>

      <p class="modal-desc" style="margin-bottom:24px;">
        Are you sure you want to log out of the CvSU Library System?
        Any unsaved changes will be lost.
      </p>

      <div style="display:flex;gap:10px;">
        <label for="logoutModalToggle" class="btn-outline" style="flex:1;">
          Stay
        </label>

        <a href="logout.php"
           class="btn-danger"
           style="flex:1;padding:10px 20px;border-radius:10px;font-size:0.85rem;justify-content:center;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
          Yes, Log Out
        </a>
      </div>
    </div>
  </div>
</div>


<div class="main-wrapper">

  <!-- Top Bar -->
  <header class="topbar">
    <h1 class="topbar-title">Manage Students</h1>

    <div class="topbar-spacer"></div>
    <a href="student_req.php" class="topbar-icon-btn" title="Student Requests">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      <?php if ($pending_count > 0): ?>
        <span class="topbar-notif-dot"></span>
      <?php endif; ?>
    </a>
    <a href="admin_profile.php" class="topbar-icon-btn" title="Admin Profile">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </a>
  </header>

  <!-- Page Content -->
  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>Manage Student Accounts</h1>
      <p>Add new students or remove existing accounts from the library system.</p>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid ms-stats">
      <div class="stat-card">
        <div class="stat-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="stat-value"><?php echo count($students); ?></div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="stat-card stat-sage">
        <div class="stat-icon icon-sage"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="stat-value"><?php echo count(array_filter($students, fn($s) => $s['status'] === 'active')); ?></div>
        <div class="stat-label">Active Accounts</div>
      </div>
      <div class="stat-card stat-danger">
        <div class="stat-icon icon-danger"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="23" y2="14"/><line x1="23" y1="8" x2="17" y2="14"/></svg></div>
        <div class="stat-value"><?php echo count(array_filter($students, fn($s) => $s['status'] === 'inactive')); ?></div>
        <div class="stat-label">Inactive Accounts</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
        <div class="stat-value">PHP <?php echo array_sum(array_column($students, 'fines')); ?></div>
        <div class="stat-label">Total Fines</div>
      </div>
    </div>

    <!-- Action Bar -->
    <div class="ms-action-bar">
      <div class="ms-search-wrap">
        <svg class="ms-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="ms-search-input" id="searchStudents" placeholder="Search by name, ID, or course...">
      </div>

      <div class="ms-filters-wrap">
        <button type="button" class="ms-filter-trigger" id="filterTriggerBtn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><circle cx="9" cy="6" r="2" fill="currentColor" stroke="none"/><line x1="4" y1="12" x2="20" y2="12"/><circle cx="15" cy="12" r="2" fill="currentColor" stroke="none"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="11" cy="18" r="2" fill="currentColor" stroke="none"/></svg>
          Filters
          <span class="ms-filter-badge" id="filterBadge" style="display:none;">0</span>
        </button>

        <div class="ms-filter-backdrop" id="filterBackdrop"></div>

        <div class="ms-filter-panel" id="filterPanel">
          <div class="ms-filter-panel-header">
            <span class="ms-filter-panel-title">Filter Students</span>
            <button type="button" class="ms-filter-panel-close" id="filterPanelClose" aria-label="Close filters">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>

          <div class="ms-filter-group">
            <div class="ms-filter-group-title">Course</div>
            <div class="ms-filter-options" data-group="course">
              <button type="button" class="ms-filter-pill active" data-value="">All Courses</button>
              <button type="button" class="ms-filter-pill" data-value="BS Computer Science">BS Computer Science</button>
              <button type="button" class="ms-filter-pill" data-value="BS Information Technology">BS Information Technology</button>
              <button type="button" class="ms-filter-pill" data-value="BS Education">BS Education</button>
              <button type="button" class="ms-filter-pill" data-value="BS Nursing">BS Nursing</button>
              <button type="button" class="ms-filter-pill" data-value="BS Engineering">BS Engineering</button>
              <button type="button" class="ms-filter-pill" data-value="BS Business Administration">BS Business Administration</button>
              <button type="button" class="ms-filter-pill" data-value="BS Accountancy">BS Accountancy</button>
              <button type="button" class="ms-filter-pill" data-value="AB Communication">AB Communication</button>
            </div>
          </div>

          <div class="ms-filter-group">
            <div class="ms-filter-group-title">Year Level</div>
            <div class="ms-filter-options" data-group="year">
              <button type="button" class="ms-filter-pill active" data-value="">All Years</button>
              <button type="button" class="ms-filter-pill" data-value="1st Year">1st Year</button>
              <button type="button" class="ms-filter-pill" data-value="2nd Year">2nd Year</button>
              <button type="button" class="ms-filter-pill" data-value="3rd Year">3rd Year</button>
              <button type="button" class="ms-filter-pill" data-value="4th Year">4th Year</button>
            </div>
          </div>

          <div class="ms-filter-group">
            <div class="ms-filter-group-title">Status</div>
            <div class="ms-filter-options" data-group="status">
              <button type="button" class="ms-filter-pill active" data-value="">All Status</button>
              <button type="button" class="ms-filter-pill" data-value="active">Active</button>
              <button type="button" class="ms-filter-pill" data-value="inactive">Inactive</button>
            </div>
          </div>

          <div class="ms-filter-group">
            <div class="ms-filter-group-title">Borrowed Books</div>
            <div class="ms-filter-options" data-group="borrowed">
              <button type="button" class="ms-filter-pill active" data-value="">All Borrowed</button>
              <button type="button" class="ms-filter-pill" data-value="has">Has Borrowed Books</button>
              <button type="button" class="ms-filter-pill" data-value="none">No Borrowed Books</button>
            </div>
          </div>

          <div class="ms-filter-group">
            <div class="ms-filter-group-title">Fines</div>
            <div class="ms-filter-options" data-group="fines">
              <button type="button" class="ms-filter-pill active" data-value="">All Fines</button>
              <button type="button" class="ms-filter-pill" data-value="has">Has Fines</button>
              <button type="button" class="ms-filter-pill" data-value="none">No Fines</button>
            </div>
          </div>

          <div class="ms-filter-panel-footer">
            <button type="button" class="ms-filter-clear-link" id="clearFiltersBtn">Clear all</button>
            <button type="button" class="ms-filter-done-btn" id="filterDoneBtn">Done</button>
          </div>
        </div>
      </div>

      <div class="ms-sort-wrap">
        <svg class="ms-sort-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M6 12h12M10 18h4"/></svg>
        <select id="sortStudents" class="ms-sort-select" title="Sort students">
          <option value="default">Sort by</option>
          <option value="id-asc">Student ID (A–Z)</option>
          <option value="id-desc">Student ID (Z–A)</option>
          <option value="name-asc">Name (A–Z)</option>
          <option value="name-desc">Name (Z–A)</option>
          <option value="course-asc">Course (A–Z)</option>
          <option value="course-desc">Course (Z–A)</option>
          <option value="year-asc">Year Level (Low–High)</option>
          <option value="year-desc">Year Level (High–Low)</option>
          <option value="status-asc">Status (A–Z)</option>
          <option value="borrowed-desc">Most Borrowed Books</option>
          <option value="borrowed-asc">Least Borrowed Books</option>
          <option value="fines-desc">Highest Fines</option>
          <option value="fines-asc">Lowest Fines</option>
        </select>
        <span class="select-arrow ms-sort-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
      </div>

      <button class="btn-primary" onclick="openAddModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Student
      </button>
    </div>

    <!-- Active filter chips (YouTube-style) -->
    <div class="ms-active-chips" id="activeChipsRow"></div>
    <span class="ms-filter-count" id="filterResultCount"></span>

    <!-- Students Table Card -->
    <div class="card ms-table-card">
      <div class="card-body">
        <h2 class="card-title">Student Directory</h2>
        <p class="card-subtitle">All registered student accounts in the library system</p>

        <div class="table-wrap">
          <table class="ms-table" id="studentsTable">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Course</th>
                <th>Year</th>
                <th>Status</th>
                <th>Borrowed</th>
                <th>Fines</th>
                <th class="ms-actions-header">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $student): ?>
              <tr data-id="<?php echo $student['id']; ?>"
                  data-student-id="<?php echo htmlspecialchars($student['student_id']); ?>"
                  data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
                  data-course="<?php echo htmlspecialchars($student['course']); ?>"
                  data-year="<?php echo htmlspecialchars($student['year']); ?>"
                  data-status="<?php echo htmlspecialchars($student['status']); ?>"
                  data-borrowed="<?php echo (int)$student['borrowed']; ?>"
                  data-fines="<?php echo (float)$student['fines']; ?>">
                <td><span class="ms-student-id"><?php echo htmlspecialchars($student['student_id']); ?></span></td>
                <td>
                  <div class="ms-student-name">
                    <div class="ms-student-avatar"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></div>
                    <div>
                      <div class="ms-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                      <div class="ms-email"><?php echo htmlspecialchars($student['email']); ?></div>
                    </div>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($student['course']); ?></td>
                <td><?php echo htmlspecialchars($student['year']); ?></td>
                <td>
                  <?php if ($student['status'] === 'active'): ?>
                    <span class="badge badge-sage">Active</span>
                  <?php else: ?>
                    <span class="badge badge-rust">Inactive</span>
                  <?php endif; ?>
                </td>
                <td><?php echo $student['borrowed']; ?></td>
                <td>
                  <?php if ($student['fines'] > 0): ?>
                    <span class="ms-fine-amount">PHP <?php echo $student['fines']; ?></span>
                  <?php else: ?>
                    <span class="ms-no-fine">—</span>
                  <?php endif; ?>
                </td>
                <td class="ms-actions-cell">
                  <button class="ms-action-btn ms-action-view" title="View Details" onclick="openViewModal(<?php echo $student['id']; ?>)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button class="ms-action-btn ms-action-edit" title="Edit Student" onclick="openEditModal(<?php echo $student['id']; ?>)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="ms-action-btn ms-action-remove" title="Remove Student" onclick="openRemoveModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>', '<?php echo htmlspecialchars($student['student_id']); ?>')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php
          $per_page    = 10;
          $total_pages = max(1, (int)ceil(count($students) / $per_page));
        ?>
        <?php if ($total_pages > 1): ?>
        <!-- Pagination (only shown when needed) -->
        <div class="pagination">
          <button class="page-btn" disabled aria-label="Previous">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <button class="page-btn <?= $p === 1 ? 'active' : '' ?>"><?= $p ?></button>
          <?php endfor; ?>
          <button class="page-btn" aria-label="Next">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<!-- ════════════════════════════════════════════
     ADD STUDENT MODAL
     ════════════════════════════════════════════ -->
<div class="modal-backdrop" id="addModal">
  <div class="modal ms-modal">
    <div class="modal-top"></div>
    <button class="modal-close" onclick="closeAddModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="modal-body">
      <h2 class="modal-title">Add New Student</h2>
      <p class="modal-desc">Fill in the details below to register a new student account in the library system.</p>

      <form method="POST" action="manage_students.php" id="addStudentForm">
        <input type="hidden" name="action" value="add_student">

        <div class="field-grid">
          <div class="field">
            <label>First Name <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <input type="text" name="first_name" placeholder="e.g. Juan" required>
            </div>
          </div>
          <div class="field">
            <label>Last Name <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <input type="text" name="last_name" placeholder="e.g. Dela Cruz" required>
            </div>
          </div>
        </div>

        <div class="field">
          <label>Student ID <span>*</span></label>
          <div class="input-wrap">
            <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            <input type="text" name="student_id" placeholder="e.g. 2024-00123" required>
          </div>
        </div>

        <div class="field">
          <label>Email Address <span>*</span></label>
          <div class="input-wrap">
            <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
            <input type="email" name="email" placeholder="e.g. juan.delacruz@cvsu.edu.ph" required>
          </div>
        </div>

        <div class="field-grid">
          <div class="field">
            <label>Course <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></span>
              <select name="course" required>
                <option value="" disabled selected>Select course</option>
                <option value="BS Computer Science">BS Computer Science</option>
                <option value="BS Information Technology">BS Information Technology</option>
                <option value="BS Education">BS Education</option>
                <option value="BS Nursing">BS Nursing</option>
                <option value="BS Engineering">BS Engineering</option>
                <option value="BS Business Administration">BS Business Administration</option>
                <option value="BS Accountancy">BS Accountancy</option>
                <option value="AB Communication">AB Communication</option>
              </select>
              <span class="select-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </div>
          </div>
          <div class="field">
            <label>Year Level <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
              <select name="year" required>
                <option value="" disabled selected>Select year</option>
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
              </select>
              <span class="select-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </div>
          </div>
        </div>

        <div class="field">
          <label>Password <span>*</span></label>
          <div class="input-wrap">
            <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            <input type="password" name="password" placeholder="Create a password" required>
          </div>
          <p class="field-hint">Minimum 8 characters with letters and numbers.</p>
        </div>

        <div class="ms-modal-actions">
          <button type="button" class="btn-outline" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Student
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════
     REMOVE CONFIRMATION MODAL
     ════════════════════════════════════════════ -->
<div class="modal-backdrop" id="removeModal">
  <div class="modal ms-modal ms-modal--danger">
    <div class="modal-top"></div>
    <button class="modal-close" onclick="closeRemoveModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="modal-body ms-modal-body--center">
      <div class="ms-remove-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
      </div>
      <h2 class="modal-title">Remove Student Account</h2>
      <p class="modal-desc" id="removeModalDesc">Are you sure you want to remove this student from the system? This action cannot be undone.</p>

      <div class="ms-remove-warning">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>All borrowing history and fine records will be permanently deleted.</span>
      </div>

      <form method="POST" action="manage_students.php" id="removeStudentForm">
        <input type="hidden" name="action" value="remove_student">
        <input type="hidden" name="student_id" id="removeStudentId">

        <div class="ms-modal-actions ms-modal-actions--danger">
          <button type="button" class="btn-outline" onclick="closeRemoveModal()">Cancel</button>
          <button type="submit" class="btn-danger ms-btn-remove">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Yes, Remove Student
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════
     VIEW STUDENT MODAL
     ════════════════════════════════════════════ -->
<div class="modal-backdrop" id="viewModal">
  <div class="modal ms-modal">
    <div class="modal-top"></div>
    <button class="modal-close" onclick="closeViewModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="modal-body">
      <div class="ms-view-header">
        <div class="ms-view-avatar" id="viewAvatar"></div>
        <div class="ms-view-title-wrap">
          <h2 class="modal-title" id="viewName"></h2>
          <p class="modal-desc" id="viewStudentId"></p>
        </div>
      </div>

      <div class="ms-view-details">
        <div class="ms-view-row">
          <span class="ms-view-label">Email</span>
          <span class="ms-view-value" id="viewEmail"></span>
        </div>
        <div class="ms-view-row">
          <span class="ms-view-label">Course</span>
          <span class="ms-view-value" id="viewCourse"></span>
        </div>
        <div class="ms-view-row">
          <span class="ms-view-label">Year Level</span>
          <span class="ms-view-value" id="viewYear"></span>
        </div>
        <div class="ms-view-row">
          <span class="ms-view-label">Status</span>
          <span class="ms-view-value" id="viewStatus"></span>
        </div>
        <div class="ms-view-row">
          <span class="ms-view-label">Books Borrowed</span>
          <span class="ms-view-value" id="viewBorrowed"></span>
        </div>
        <div class="ms-view-row">
          <span class="ms-view-label">Total Fines</span>
          <span class="ms-view-value" id="viewFines"></span>
        </div>
      </div>

      <div class="ms-modal-actions">
        <button type="button" class="btn-outline" onclick="closeViewModal()">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════
     EDIT STUDENT MODAL
     ════════════════════════════════════════════ -->
<div class="modal-backdrop" id="editModal">
  <div class="modal ms-modal">
    <div class="modal-top"></div>
    <button class="modal-close" onclick="closeEditModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="modal-body">
      <h2 class="modal-title">Edit Student</h2>
      <p class="modal-desc">Update the student information below.</p>

      <form method="POST" action="manage_students.php" id="editStudentForm">
        <input type="hidden" name="action" value="edit_student">
        <input type="hidden" name="edit_id" id="editId">

        <div class="field-grid">
          <div class="field">
            <label>First Name <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <input type="text" name="edit_first_name" id="editFirstName" required>
            </div>
          </div>
          <div class="field">
            <label>Last Name <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <input type="text" name="edit_last_name" id="editLastName" required>
            </div>
          </div>
        </div>

        <div class="field">
          <label>Middle Name <small style="color:var(--muted);font-weight:400;">(optional)</small></label>
          <div class="input-wrap">
            <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <input type="text" name="edit_middle_name" id="editMiddleName" placeholder="Middle name">
          </div>
        </div>

        <div class="field">
          <label>Student ID <span>*</span></label>
          <div class="input-wrap">
            <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            <input type="text" name="edit_student_id" id="editStudentIdInput" required>
          </div>
        </div>

        <div class="field">
          <label>Email Address <span>*</span></label>
          <div class="input-wrap">
            <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
            <input type="email" name="edit_email" id="editEmail" required>
          </div>
        </div>

        <div class="field-grid">
          <div class="field">
            <label>Course <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></span>
              <select name="edit_course" id="editCourse" required>
                <option value="BS Computer Science">BS Computer Science</option>
                <option value="BS Information Technology">BS Information Technology</option>
                <option value="BS Education">BS Education</option>
                <option value="BS Nursing">BS Nursing</option>
                <option value="BS Engineering">BS Engineering</option>
                <option value="BS Business Administration">BS Business Administration</option>
                <option value="BS Accountancy">BS Accountancy</option>
                <option value="AB Communication">AB Communication</option>
              </select>
              <span class="select-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </div>
          </div>
          <div class="field">
            <label>Year Level <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
              <select name="edit_year" id="editYear" required>
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
              </select>
              <span class="select-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </div>
          </div>
        </div>

        <div class="field">
          <label>Status <span>*</span></label>
          <div class="input-wrap">
            <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
            <select name="edit_status" id="editStatus" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <span class="select-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
          </div>
        </div>

        <div class="ms-modal-actions">
          <button type="button" class="btn-outline" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ── Modal: Add Student ──
function openAddModal() {
  document.getElementById('addModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeAddModal() {
  document.getElementById('addModal').classList.remove('open');
  document.body.style.overflow = '';
  document.getElementById('addStudentForm').reset();
}

// ── Modal: Remove Student ──
function openRemoveModal(id, name, studentId) {
  document.getElementById('removeStudentId').value = id;
  document.getElementById('removeModalDesc').innerHTML =
    'Are you sure you want to remove <strong>' + name + '</strong> (' + studentId + ') from the system? This action cannot be undone.';
  document.getElementById('removeModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeRemoveModal() {
  document.getElementById('removeModal').classList.remove('open');
  document.body.style.overflow = '';
}

// ── Close modals on backdrop click ──
document.getElementById('addModal').addEventListener('click', function(e) {
  if (e.target === this) closeAddModal();
});
document.getElementById('removeModal').addEventListener('click', function(e) {
  if (e.target === this) closeRemoveModal();
});

// ── Close modals on Escape key ──
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeAddModal();
    closeViewModal();
    closeEditModal();
    closeRemoveModal();
    closeFilterPanel();
  }
});

// ── Search + Filter (Course / Year / Status / Borrowed / Fines) — YouTube-style panel ──
// Groups in this list allow selecting more than one pill at once.
// Everything else (Status, Borrowed, Fines) stays single-select.
const MULTI_SELECT_GROUPS = ['course', 'year'];
function isMulti(group) { return MULTI_SELECT_GROUPS.includes(group); }

// Multi-select groups store an array of values; single-select groups store a plain string.
const studentFilterState = { course: [], year: [], status: '', borrowed: '', fines: '' };

const filterTriggerBtn = document.getElementById('filterTriggerBtn');
const filterPanel      = document.getElementById('filterPanel');
const filterBackdrop   = document.getElementById('filterBackdrop');
const filterBadge      = document.getElementById('filterBadge');
const activeChipsRow   = document.getElementById('activeChipsRow');

function openFilterPanel() {
  filterPanel.classList.add('open');
  filterBackdrop.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeFilterPanel() {
  filterPanel.classList.remove('open');
  filterBackdrop.classList.remove('open');
  document.body.style.overflow = '';
}

const filterGroupLabels = {
  course:   'Course',
  year:     'Year',
  status:   'Status',
  borrowed: 'Borrowed',
  fines:    'Fines'
};

function findPill(group, value) {
  return document.querySelector(`.ms-filter-options[data-group="${group}"] .ms-filter-pill[data-value="${CSS.escape(value)}"]`);
}

// Refresh which pills look "active" for a given group based on studentFilterState.
function syncPillsUI(group) {
  const value = studentFilterState[group];
  document.querySelectorAll(`.ms-filter-options[data-group="${group}"] .ms-filter-pill`).forEach(function(pill) {
    if (isMulti(group)) {
      const isAllPill = pill.dataset.value === '';
      pill.classList.toggle('active', isAllPill ? value.length === 0 : value.includes(pill.dataset.value));
    } else {
      pill.classList.toggle('active', pill.dataset.value === value);
    }
  });
}

// Toggle a single pill's value on/off within a group, respecting multi vs single select.
function togglePill(group, value) {
  if (isMulti(group)) {
    if (value === '') {
      studentFilterState[group] = []; // "All" pill clears the group
    } else {
      const arr = studentFilterState[group];
      const idx = arr.indexOf(value);
      if (idx === -1) arr.push(value); else arr.splice(idx, 1);
    }
  } else {
    studentFilterState[group] = value;
  }
  syncPillsUI(group);
  refreshFilterChrome();
  applyStudentFilters();
}

// Used by the chip "x" buttons to remove one specific value.
function removeFilterValue(group, value) {
  if (isMulti(group)) {
    const arr = studentFilterState[group];
    const idx = arr.indexOf(value);
    if (idx !== -1) arr.splice(idx, 1);
  } else {
    studentFilterState[group] = '';
  }
  syncPillsUI(group);
  refreshFilterChrome();
  applyStudentFilters();
}

function refreshFilterChrome() {
  const activeGroups = Object.keys(studentFilterState).filter(g =>
    isMulti(g) ? studentFilterState[g].length > 0 : studentFilterState[g] !== ''
  );

  // Count total selected values (so picking 2 courses shows badge "2", not "1 group").
  const count = activeGroups.reduce((sum, g) => sum + (isMulti(g) ? studentFilterState[g].length : 1), 0);

  filterTriggerBtn.classList.toggle('active', count > 0);
  if (count > 0) {
    filterBadge.style.display = 'inline-flex';
    filterBadge.textContent = count;
  } else {
    filterBadge.style.display = 'none';
  }

  activeChipsRow.innerHTML = '';
  activeGroups.forEach(function(group) {
    const values = isMulti(group) ? studentFilterState[group] : [studentFilterState[group]];
    values.forEach(function(value) {
      const pill  = findPill(group, value);
      const label = pill ? pill.textContent : value;

      const chip = document.createElement('span');
      chip.className = 'ms-chip';
      chip.innerHTML = `${filterGroupLabels[group]}: ${label} <button type="button" aria-label="Remove filter" data-group="${group}">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>`;
      chip.querySelector('button').addEventListener('click', function() {
        removeFilterValue(group, value);
      });
      activeChipsRow.appendChild(chip);
    });
  });
}

document.querySelectorAll('.ms-filter-options').forEach(function(optionsEl) {
  const group = optionsEl.dataset.group;
  optionsEl.querySelectorAll('.ms-filter-pill').forEach(function(pill) {
    pill.addEventListener('click', function() {
      togglePill(group, pill.dataset.value);
    });
  });
});

filterTriggerBtn.addEventListener('click', function(e) {
  e.stopPropagation();
  if (filterPanel.classList.contains('open')) {
    closeFilterPanel();
  } else {
    openFilterPanel();
  }
});

document.getElementById('filterDoneBtn').addEventListener('click', closeFilterPanel);
document.getElementById('filterPanelClose').addEventListener('click', closeFilterPanel);
filterBackdrop.addEventListener('click', closeFilterPanel);

function applyStudentFilters() {
  const term      = document.getElementById('searchStudents').value.toLowerCase();
  const course    = studentFilterState.course; // array
  const year      = studentFilterState.year;   // array
  const status    = studentFilterState.status;
  const borrowed  = studentFilterState.borrowed;
  const fines     = studentFilterState.fines;

  const rows = document.querySelectorAll('#studentsTable tbody tr');
  let visibleCount = 0;

  rows.forEach(function(row) {
    const text       = row.textContent.toLowerCase();
    const rCourse    = row.dataset.course || '';
    const rYear      = row.dataset.year || '';
    const rStatus    = row.dataset.status || '';
    const rBorrowed  = parseInt(row.dataset.borrowed, 10) || 0;
    const rFines     = parseFloat(row.dataset.fines) || 0;

    let visible = true;
    if (term && !text.includes(term))               visible = false;
    if (course.length && !course.includes(rCourse)) visible = false;
    if (year.length && !year.includes(rYear))        visible = false;
    if (status && rStatus !== status)           visible = false;
    if (borrowed === 'has'  && rBorrowed <= 0)  visible = false;
    if (borrowed === 'none' && rBorrowed > 0)   visible = false;
    if (fines === 'has'  && rFines <= 0)        visible = false;
    if (fines === 'none' && rFines > 0)         visible = false;

    row.style.display = visible ? '' : 'none';
    if (visible) visibleCount++;
  });

  const countEl = document.getElementById('filterResultCount');
  const totalRows = rows.length;
  countEl.textContent = (visibleCount === totalRows)
    ? ''
    : `Showing ${visibleCount} of ${totalRows}`;
}

document.getElementById('searchStudents').addEventListener('input', applyStudentFilters);

// ── Sort ──
function getSortValue(row, key) {
  switch (key) {
    case 'id':       return (row.dataset.studentId || '').toLowerCase();
    case 'name':     return (row.dataset.name || '').toLowerCase();
    case 'course':   return (row.dataset.course || '').toLowerCase();
    case 'year':     return parseInt(row.dataset.year, 10) || 0;
    case 'status':   return (row.dataset.status || '').toLowerCase();
    case 'borrowed': return parseInt(row.dataset.borrowed, 10) || 0;
    case 'fines':    return parseFloat(row.dataset.fines) || 0;
    default:         return '';
  }
}

function sortStudentsTable(sortVal) {
  if (!sortVal || sortVal === 'default') return;

  const [key, dir] = sortVal.split('-');
  const tbody = document.querySelector('#studentsTable tbody');
  const rows  = Array.from(tbody.querySelectorAll('tr'));

  rows.sort(function(a, b) {
    const va = getSortValue(a, key);
    const vb = getSortValue(b, key);
    if (va < vb) return dir === 'asc' ? -1 : 1;
    if (va > vb) return dir === 'asc' ? 1 : -1;
    return 0;
  });

  rows.forEach(function(row) { tbody.appendChild(row); });
}

document.getElementById('sortStudents').addEventListener('change', function() {
  sortStudentsTable(this.value);
});

document.getElementById('clearFiltersBtn').addEventListener('click', function() {
  document.getElementById('searchStudents').value = '';
  Object.keys(studentFilterState).forEach(function(group) {
    studentFilterState[group] = isMulti(group) ? [] : '';
    syncPillsUI(group);
  });
  refreshFilterChrome();
  applyStudentFilters();
});

// ── Modal: View Student ──
const studentsData = <?php echo json_encode($students); ?>;

function openViewModal(id) {
  const student = studentsData.find(s => s.id == id);
  if (!student) return;

  document.getElementById('viewAvatar').textContent = (student.first_name[0] + student.last_name[0]).toUpperCase();
  document.getElementById('viewName').textContent = student.first_name + ' ' + student.last_name;
  document.getElementById('viewStudentId').textContent = 'Student ID: ' + student.student_id;
  document.getElementById('viewEmail').textContent = student.email;
  document.getElementById('viewCourse').textContent = student.course;
  document.getElementById('viewYear').textContent = student.year;
  document.getElementById('viewStatus').innerHTML = student.status === 'active'
    ? '<span class="badge badge-sage">Active</span>'
    : '<span class="badge badge-rust">Inactive</span>';
  document.getElementById('viewBorrowed').textContent = student.borrowed;
  document.getElementById('viewFines').textContent = student.fines > 0 ? 'PHP ' + student.fines : '—';

  document.getElementById('viewModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeViewModal() {
  document.getElementById('viewModal').classList.remove('open');
  document.body.style.overflow = '';
}

// ── Modal: Edit Student ──
function openEditModal(id) {
  const student = studentsData.find(s => s.id == id);
  if (!student) return;

  document.getElementById('editId').value = student.id;
  document.getElementById('editFirstName').value = student.first_name;
  document.getElementById('editLastName').value = student.last_name;
  document.getElementById('editMiddleName').value = student.middle_name || '';
  document.getElementById('editStudentIdInput').value = student.student_id;
  document.getElementById('editEmail').value = student.email;
  document.getElementById('editCourse').value = student.course;
  document.getElementById('editYear').value = student.year;
  document.getElementById('editStatus').value = student.status;

  document.getElementById('editModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeEditModal() {
  document.getElementById('editModal').classList.remove('open');
  document.body.style.overflow = '';
}

// ── Close modals on backdrop click ──
document.getElementById('viewModal').addEventListener('click', function(e) {
  if (e.target === this) closeViewModal();
});
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});

// ── Toast ──
<?php if ($toast): ?>
(function() {
  const toast = document.getElementById('toast');
  toast.textContent = <?php echo json_encode($toast['message']); ?>;
  toast.className = 'toast show ' + <?php echo json_encode($toast['type']); ?>;
  setTimeout(function() { toast.classList.remove('show'); }, 3500);
})();
<?php endif; ?>
</script>

</body>
</html>