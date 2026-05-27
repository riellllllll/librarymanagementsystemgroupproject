<?php

session_start();

// ── Page context ──
$current_page = basename($_SERVER['PHP_SELF']);
$request_badge = $pending_count ?? 0;
$archive_badge = isset($_SESSION['archived_books']) ? count($_SESSION['archived_books']) : 0;

// ── Demo data (replace with database queries in production) ──
$students = [
    ['id' => 1, 'student_id' => '101', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'juan.delacruz@cvsu.edu.ph', 'course' => 'BSIT', 'year' => '3rd Year', 'status' => 'active', 'borrowed' => 2, 'fines' => 0],
    ['id' => 2, 'student_id' => '102', 'first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria.santos@cvsu.edu.ph', 'course' => 'BSED', 'year' => '2nd Year', 'status' => 'active', 'borrowed' => 1, 'fines' => 20],
    ['id' => 3, 'student_id' => '103', 'first_name' => 'Pedro', 'last_name' => 'Reyes', 'email' => 'pedro.reyes@cvsu.edu.ph', 'course' => 'BSBA', 'year' => '4th Year', 'status' => 'active', 'borrowed' => 0, 'fines' => 0],
    ['id' => 4, 'student_id' => '104', 'first_name' => 'Ana', 'last_name' => 'Garcia', 'email' => 'ana.garcia@cvsu.edu.ph', 'course' => 'BSN', 'year' => '1st Year', 'status' => 'active', 'borrowed' => 3, 'fines' => 50],
    ['id' => 5, 'student_id' => '105', 'first_name' => 'Carlos', 'last_name' => 'Mendoza', 'email' => 'carlos.mendoza@cvsu.edu.ph', 'course' => 'BSCS', 'year' => '3rd Year', 'status' => 'inactive', 'borrowed' => 0, 'fines' => 0],
    ['id' => 6, 'student_id' => '106', 'first_name' => 'Sofia', 'last_name' => 'Lim', 'email' => 'sofia.lim@cvsu.edu.ph', 'course' => 'BSPSY', 'year' => '2nd Year', 'status' => 'active', 'borrowed' => 1, 'fines' => 0],
];

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);

// Handle form submissions (demo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_student') {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Student account added successfully!'];
        header('Location: manage_students.php');
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'remove_student') {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Student account removed successfully!'];
        header('Location: manage_students.php');
        exit;
    }
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
</head>
<body>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
$request_badge = $pending_count ?? 0;
$archive_badge = isset($_SESSION['archived_books']) ? count($_SESSION['archived_books']) : 0;
?>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <svg viewBox="0 0 48 48" aria-hidden="true">
        <rect x="6" y="8" width="8" height="32" rx="1.5" fill="#c9973a"/>
        <rect x="16" y="10" width="6" height="30" rx="1.5" fill="#e8c26a"/>
        <rect x="24" y="6" width="10" height="36" rx="1.5" fill="#c9973a"/>
        <rect x="36" y="9" width="6" height="31" rx="1.5" fill="#a07830"/>
        <rect x="5" y="38" width="38" height="2.5" rx="1.25" fill="#7a6030"/>
      </svg>
    </div>

    <div>
      <h2>Cv<em>SU</em></h2>
      <div class="sidebar-subtitle">Admin Panel</div>
    </div>
  </div>

  <nav class="sidebar-nav" aria-label="Admin navigation">
    <div class="nav-section-label">Main</div>

    <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <div class="nav-section-label">Books</div>

    <a href="view_books.php" class="nav-link <?= $current_page === 'view_books.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      View Books
    </a>

    <a href="archive_books.php" class="nav-link <?= $current_page === 'archive_books.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <rect x="3" y="4" width="18" height="4" rx="1"/>
        <path d="M5 8v11h14V8"/>
        <path d="M10 12h4"/>
      </svg>
      Archive Books

      <?php if ($archive_badge > 0): ?>
        <span class="nav-badge"><?= $archive_badge ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section-label">Borrowing</div>

    <a href="student_req.php" class="nav-link <?= $current_page === 'student_req.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
        <path d="M8 9h8M8 13h5"/>
      </svg>
      Student Requests

      <?php if ($request_badge > 0): ?>
        <span class="nav-badge"><?= $request_badge ?></span>
      <?php endif; ?>
    </a>

    <a href="borrowed_books.php" class="nav-link <?= $current_page === 'borrowed_books.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
      Borrowed Books
    </a>

    <a href="issue_book.php" class="nav-link <?= $current_page === 'issue_book.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M12 5v14M5 12l7-7 7 7"/>
      </svg>
      Issue Book
    </a>

    <a href="return_book.php" class="nav-link <?= $current_page === 'return_book.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M9 14l-4-4 4-4"/>
        <path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
      </svg>
      Return Book
    </a>

    <div class="nav-section-label">Students</div>

    <a href="view_students.php" class="nav-link <?= $current_page === 'view_students.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
      </svg>
      Students
    </a>

    <a href="manage_students.php" class="nav-link <?= $current_page === 'manage_students.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06"/>
        <path d="M4.27 7.11l.06.06A1.65 1.65 0 0 0 6.15 7.5"/>
      </svg>
      Manage Students
    </a>

    <div class="nav-section-label">Fines</div>

    <a href="view_fines.php" class="nav-link <?= $current_page === 'view_fines.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      Fines
    </a>

    <div class="nav-section-label">Account</div>

    <a href="admin_profile.php" class="nav-link <?= $current_page === 'admin_profile.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      My Profile
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">AD</div>

      <div class="user-info">
        <div class="user-name">Admin</div>
        <div class="user-role">Administrator</div>
      </div>
    </div>

    <label for="logoutModalToggle" class="btn-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Log Out
    </label>
  </div>
</aside>

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
    <button class="topbar-icon-btn" title="Notifications">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      <span class="topbar-notif-dot"></span>
    </button>
    <a href="my_profile.php" class="topbar-icon-btn" title="My Profile">
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
      <button class="btn-primary" onclick="openAddModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Student
      </button>
    </div>

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
              <tr data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
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
                  <button class="ms-action-btn ms-action-view" title="View Details">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button class="ms-action-btn ms-action-edit" title="Edit Student">
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

        <!-- Pagination -->
        <div class="pagination">
          <button class="page-btn" disabled><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
          <button class="page-btn active">1</button>
          <button class="page-btn">2</button>
          <button class="page-btn">3</button>
          <button class="page-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
        </div>
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
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
                <option value="BSBA">BSBA</option>
                <option value="BSED">BSED</option>
                <option value="BSN">BSN</option>
                <option value="BSPSY">BSPSY</option>
                <option value="BSA">BSA</option>
                <option value="BSCE">BSCE</option>
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
    closeRemoveModal();
  }
});

// ── Search Filter ──
document.getElementById('searchStudents').addEventListener('input', function() {
  const term = this.value.toLowerCase();
  const rows = document.querySelectorAll('#studentsTable tbody tr');
  rows.forEach(function(row) {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(term) ? '' : 'none';
  });
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