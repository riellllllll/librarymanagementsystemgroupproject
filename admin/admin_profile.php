<?php
session_start();

$pending_count = 1;

$admin = [
  'name' => 'Admin Librarian',
  'initials' => 'AD',
  'admin_id' => 'ADM-2026-001',
  'employee_no' => 'EMP-00045',
  'email' => 'admin.library@cvsu.edu.ph',
  'contact' => '+63 912 345 6789',
  'role' => 'Administrator',
  'department' => 'Library Services',
  'campus' => 'CvSU Imus Campus',
  'status' => 'Active',
  'joined' => 'January 10, 2024',
  'last_login' => 'May 21, 2026',
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Admin Profile - CvSU Library</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/admin_profile.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
</head>

<body>

<?php include "sideBar.php"; ?>

<div class="main-wrapper">

  <header class="topbar">

    <span class="topbar-title">Admin Profile</span>

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
      <h1>Admin <em>Profile</em></h1>
      <div class="gold-rule"><span></span><i>*</i><span></span></div>
      <p>Administrator account information and library system access details.</p>
    </div>

    <section class="admin-profile-hero">
      <div class="profile-banner"></div>

      <div class="admin-profile-body">
        <div class="admin-avatar-xl">
          <?= htmlspecialchars($admin['initials']) ?>
        </div>

        <div class="admin-profile-info">
          <h2><?= htmlspecialchars($admin['name']) ?></h2>
          <div class="admin-id">Admin ID: <?= htmlspecialchars($admin['admin_id']) ?></div>

          <div class="admin-chips">
            <span class="admin-chip chip-gold"><?= htmlspecialchars($admin['role']) ?></span>
            <span class="admin-chip chip-sage"><?= htmlspecialchars($admin['status']) ?></span>
            <span class="admin-chip"><?= htmlspecialchars($admin['department']) ?></span>
          </div>
        </div>
      </div>

      <div class="admin-stats">
        <div class="admin-stat-item">
          <span class="admin-stat-value">128</span>
          <span class="admin-stat-label">Books Managed</span>
        </div>
        <div class="admin-stat-divider"></div>
        <div class="admin-stat-item">
          <span class="admin-stat-value">24</span>
          <span class="admin-stat-label">Students</span>
        </div>
        <div class="admin-stat-divider"></div>
        <div class="admin-stat-item">
          <span class="admin-stat-value"><?= $pending_count ?></span>
          <span class="admin-stat-label">Pending Requests</span>
        </div>
        <div class="admin-stat-divider"></div>
        <div class="admin-stat-item">
          <span class="admin-stat-value">5</span>
          <span class="admin-stat-label">Returns Today</span>
        </div>
      </div>
    </section>

    <div class="admin-profile-layout">

      <section class="card">
        <div class="card-body">
          <div class="card-title">Personal Information</div>
          <div class="card-subtitle">Admin contact and identity details</div>

          <div class="info-grid">
            <div class="info-cell">
              <div class="info-label">Full Name</div>
              <div class="info-value"><?= htmlspecialchars($admin['name']) ?></div>
            </div>

            <div class="info-cell">
              <div class="info-label">Employee No.</div>
              <div class="info-value"><?= htmlspecialchars($admin['employee_no']) ?></div>
            </div>

            <div class="info-cell">
              <div class="info-label">Email Address</div>
              <div class="info-value"><?= htmlspecialchars($admin['email']) ?></div>
            </div>

            <div class="info-cell">
              <div class="info-label">Contact Number</div>
              <div class="info-value"><?= htmlspecialchars($admin['contact']) ?></div>
            </div>

            <div class="info-cell">
              <div class="info-label">Department</div>
              <div class="info-value"><?= htmlspecialchars($admin['department']) ?></div>
            </div>

            <div class="info-cell">
              <div class="info-label">Campus</div>
              <div class="info-value"><?= htmlspecialchars($admin['campus']) ?></div>
            </div>
          </div>
        </div>
      </section>

      <aside>
        <section class="card admin-side-card">
          <div class="card-body">
            <div class="card-title">Account Details</div>
            <div class="card-subtitle">System-assigned information</div>

            <div class="detail-list">
              <div>
                <span>Role</span>
                <strong><?= htmlspecialchars($admin['role']) ?></strong>
              </div>

              <div>
                <span>Status</span>
                <strong><span class="badge badge-sage"><?= htmlspecialchars($admin['status']) ?></span></strong>
              </div>

              <div>
                <span>Registered On</span>
                <strong><?= htmlspecialchars($admin['joined']) ?></strong>
              </div>

              <div>
                <span>Last Login</span>
                <strong><?= htmlspecialchars($admin['last_login']) ?></strong>
              </div>
            </div>
          </div>
        </section>

        <section class="card admin-side-card">
          <div class="card-body">
            <div class="card-title">Quick Links</div>

            <div class="admin-quick-links">
              <a href="student_req.php">Student Requests</a>
              <a href="view_books.php">View Books</a>
              <a href="borrowed_books.php">Borrowed Books</a>
              <a href="view_students.php">Students</a>
            </div>
          </div>
        </section>

        <section class="admin-assistance">
          <h4>Account Assistance</h4>
          <p>For admin credential or permission concerns, contact the system administrator.</p>
          <a href="mailto:library@cvsu.edu.ph">Contact Support</a>
        </section>
      </aside>

    </div>

  </main>

</div>

</body>
</html>