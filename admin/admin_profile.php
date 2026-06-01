<?php
require 'library_data.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$pending_count = pending_request_count();

$success_msg = $_SESSION['flash_success'] ?? '';
$error_msg = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'update_profile') {
    $contact = trim($_POST['contact'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $campus = trim($_POST['campus'] ?? '');

    if ($contact && !preg_match('/^\+?[\d\s\-]{7,15}$/', $contact)) {
      $_SESSION['flash_error'] = 'Please enter a valid contact number.';
    } else {
      $_SESSION['admin_contact'] = $contact;
      $_SESSION['admin_department'] = $department;
      $_SESSION['admin_campus'] = $campus;
      $_SESSION['flash_success'] = 'Admin profile updated successfully!';
    }

    header('Location: admin_profile.php');
    exit;
  }

  if ($action === 'change_password') {
    $current = $_POST['current_pw'] ?? '';
    $new_pw = $_POST['new_pw'] ?? '';
    $confirm = $_POST['confirm_pw'] ?? '';

    if (!$current || !$new_pw || !$confirm) {
      $_SESSION['flash_error'] = 'Please fill in all password fields.';
    } elseif ($new_pw !== $confirm) {
      $_SESSION['flash_error'] = 'New passwords do not match.';
    } elseif (strlen($new_pw) < 8) {
      $_SESSION['flash_error'] = 'Password must be at least 8 characters.';
    } else {
      $_SESSION['flash_success'] = 'Password updated successfully!';
    }

    header('Location: admin_profile.php');
    exit;
  }
}

$returns_today = count(array_filter($_SESSION['borrowed_books'], function ($book) {
  return ($book['status'] ?? '') === 'returned'
    && ($book['return_date'] ?? '') === date('Y-m-d');
}));

$admin = [
  'name' => $_SESSION['admin_name'] ?? 'Admin Librarian',
  'initials' => $_SESSION['admin_initials'] ?? 'AD',
  'admin_id' => $_SESSION['admin_id'] ?? '201',
  'employee_no' => $_SESSION['admin_employee_no'] ?? 'EMP-00045',
  'email' => $_SESSION['admin_email'] ?? 'admin.library@cvsu.edu.ph',
  'contact' => $_SESSION['admin_contact'] ?? '+63 912 345 6789',
  'role' => $_SESSION['admin_role'] ?? 'Administrator',
  'department' => $_SESSION['admin_department'] ?? 'Library Services',
  'campus' => $_SESSION['admin_campus'] ?? 'CvSU Imus Campus',
  'status' => $_SESSION['admin_status'] ?? 'Active',
  'joined' => $_SESSION['admin_joined'] ?? 'January 10, 2024',
  'last_login' => $_SESSION['admin_last_login'] ?? 'May 21, 2026',
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
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
  <link rel="stylesheet" href="../assets/admin_profile.css">
</head>

<body>

<?php include "sideBar.php"; ?>

<div class="main-wrapper admin-profile-page">

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

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>

      <p>Administrator account information and library system access details.</p>
    </div>

    <?php if ($success_msg): ?>
      <div class="alert alert-sage" style="margin-bottom:16px;">
        <span><?= htmlspecialchars($success_msg) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
      <div class="alert alert-rust" style="margin-bottom:16px;">
        <span><?= htmlspecialchars($error_msg) ?></span>
      </div>
    <?php endif; ?>

    <section class="admin-profile-hero">
      <div class="profile-banner"></div>

      <div class="admin-profile-body">
        <div class="admin-avatar-xl">
          <?= htmlspecialchars($admin['initials']) ?>
        </div>

        <div class="admin-profile-info">
          <h2><?= htmlspecialchars($admin['name']) ?></h2>

          <div class="admin-id">
            Admin ID: <?= htmlspecialchars($admin['admin_id']) ?>
          </div>

          <div class="admin-chips">
            <span class="admin-chip chip-gold"><?= htmlspecialchars($admin['role']) ?></span>
            <span class="admin-chip chip-sage"><?= htmlspecialchars($admin['status']) ?></span>
            <span class="admin-chip"><?= htmlspecialchars($admin['department']) ?></span>
          </div>
        </div>

        <div class="admin-profile-actions">
          <button class="btn-edit-toggle" id="editToggleBtn" type="button" onclick="toggleEdit()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Edit Profile
          </button>
        </div>
      </div>

      <div class="admin-stats">
        <div class="admin-stat-item">
          <span class="admin-stat-value"><?= active_book_count() ?></span>
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
          <span class="admin-stat-value"><?= $returns_today ?></span>
          <span class="admin-stat-label">Returns Today</span>
        </div>
      </div>
    </section>

    <div class="admin-profile-layout">

      <div>

        <section class="card" style="margin-bottom:16px;">
          <div class="card-body">
            <div class="card-title">Personal Information</div>
            <div class="card-subtitle">Admin contact and identity details</div>

            <div class="view-mode" id="viewMode">
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

            <form class="edit-form" id="editForm" method="POST" action="admin_profile.php">
              <input type="hidden" name="action" value="update_profile">

              <div class="field">
                <label>Full Name</label>
                <div class="input-wrap">
                  <input
                    class="no-icon"
                    type="text"
                    value="<?= htmlspecialchars($admin['name']) ?>"
                    readonly
                    style="opacity:0.6;cursor:not-allowed;"
                  >
                </div>
                <div class="field-hint">Name adjustments must be handled by the system administrator.</div>
              </div>

              <div class="field-grid">
                <div class="field">
                  <label>Employee No.</label>
                  <div class="input-wrap">
                    <input
                      class="no-icon"
                      type="text"
                      value="<?= htmlspecialchars($admin['employee_no']) ?>"
                      readonly
                      style="opacity:0.6;cursor:not-allowed;"
                    >
                  </div>
                </div>

                <div class="field">
                  <label>Email Address</label>
                  <div class="input-wrap">
                    <input
                      class="no-icon"
                      type="email"
                      value="<?= htmlspecialchars($admin['email']) ?>"
                      readonly
                      style="opacity:0.6;cursor:not-allowed;"
                    >
                  </div>
                  <div class="field-hint">Admin email cannot be changed here.</div>
                </div>
              </div>

              <div class="field">
                <label>Contact Number</label>
                <div class="input-wrap">
                  <input
                    class="no-icon"
                    type="tel"
                    name="contact"
                    value="<?= htmlspecialchars($admin['contact']) ?>"
                    placeholder="Contact number"
                  >
                </div>
              </div>

              <div class="field-grid">
                <div class="field">
                  <label>Department</label>
                  <div class="input-wrap">
                    <select class="no-icon" name="department">
                      <?php
                      $departments = [
                        'Library Services',
                        'Circulation Desk',
                        'Reference Services',
                        'Technical Services'
                      ];

                      foreach ($departments as $department):
                      ?>
                        <option
                          value="<?= htmlspecialchars($department) ?>"
                          <?= $department === $admin['department'] ? 'selected' : '' ?>
                        >
                          <?= htmlspecialchars($department) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="field">
                  <label>Campus</label>
                  <div class="input-wrap">
                    <select class="no-icon" name="campus">
                      <?php
                      $campuses = [
                        'CvSU Imus Campus',
                        'CvSU Main Campus',
                        'CvSU Bacoor Campus',
                        'CvSU General Trias Campus'
                      ];

                      foreach ($campuses as $campus):
                      ?>
                        <option
                          value="<?= htmlspecialchars($campus) ?>"
                          <?= $campus === $admin['campus'] ? 'selected' : '' ?>
                        >
                          <?= htmlspecialchars($campus) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="admin-profile-form-actions">
                <button type="button" class="btn-outline" onclick="cancelEdit()">
                  Cancel
                </button>

                <button type="submit" class="btn-primary">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </section>

        <section class="card">
          <div class="card-body">
            <div class="card-title">Change Password</div>
            <div class="card-subtitle">Update admin login credentials</div>

            <div class="alert alert-gold" style="margin-bottom:18px;">
              Use a strong password with at least 8 characters, including letters, numbers, and symbols.
            </div>

            <form method="POST" action="admin_profile.php" id="pwForm">
              <input type="hidden" name="action" value="change_password">

              <div class="field">
                <label>Current Password <span>*</span></label>
                <div class="input-wrap">
                  <input
                    class="no-icon"
                    type="password"
                    name="current_pw"
                    id="currentPw"
                    placeholder="Enter current password"
                  >
                </div>
              </div>

              <div class="field">
                <label>New Password <span>*</span></label>
                <div class="input-wrap">
                  <input
                    class="no-icon"
                    type="password"
                    name="new_pw"
                    id="newPw"
                    placeholder="Enter new password"
                    oninput="checkStrength(this.value)"
                  >
                </div>

                <div class="password-strength">
                  <div class="password-strength-bar" id="strengthBar"></div>
                </div>

                <div class="password-strength-label" id="strengthLabel"></div>
              </div>

              <div class="field">
                <label>Confirm New Password <span>*</span></label>
                <div class="input-wrap">
                  <input
                    class="no-icon"
                    type="password"
                    name="confirm_pw"
                    id="confirmPw"
                    placeholder="Confirm new password"
                  >
                </div>
              </div>

              <div class="admin-profile-form-actions">
                <button type="submit" class="btn-primary">
                  Update Password
                </button>
              </div>
            </form>
          </div>
        </section>

      </div>

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

<div class="toast" id="toast"></div>

<script>
  let isEditing = false;

  function toggleEdit() {
    isEditing = !isEditing;

    const btn = document.getElementById('editToggleBtn');
    const viewMode = document.getElementById('viewMode');
    const editForm = document.getElementById('editForm');

    if (isEditing) {
      btn.innerHTML = `
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
        Cancel
      `;
      btn.classList.add('editing');
      viewMode.classList.add('hidden');
      editForm.classList.add('active');
    } else {
      cancelEdit();
    }
  }

  function cancelEdit() {
    isEditing = false;

    const btn = document.getElementById('editToggleBtn');
    const viewMode = document.getElementById('viewMode');
    const editForm = document.getElementById('editForm');

    btn.innerHTML = `
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
      </svg>
      Edit Profile
    `;
    btn.classList.remove('editing');
    viewMode.classList.remove('hidden');
    editForm.classList.remove('active');
  }

  function checkStrength(val) {
    const bar = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');

    bar.className = 'password-strength-bar';

    if (!val) {
      label.textContent = '';
      return;
    }

    if (val.length < 6) {
      bar.classList.add('strength-weak');
      label.textContent = 'Weak - too short';
      label.style.color = 'var(--rust)';
    } else if (val.length < 10 || !/[0-9]/.test(val)) {
      bar.classList.add('strength-medium');
      label.textContent = 'Medium - add numbers or symbols';
      label.style.color = 'var(--gold-dk)';
    } else {
      bar.classList.add('strength-strong');
      label.textContent = 'Strong password!';
      label.style.color = 'var(--sage)';
    }
  }

  function showToast(msg, type = '') {
    const toast = document.getElementById('toast');

    toast.textContent = msg;
    toast.className = 'toast' + (type ? ' ' + type : '');

    void toast.offsetWidth;
    toast.classList.add('show');

    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }

  <?php if ($success_msg): ?>
    window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($success_msg) ?>, 'success'));
  <?php elseif ($error_msg): ?>
    window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error_msg) ?>, 'error'));
  <?php endif; ?>
</script>

</body>
</html>
