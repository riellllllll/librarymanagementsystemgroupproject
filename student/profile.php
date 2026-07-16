<?php
// ============================================================
// profile.php — CvSU Library My Profile (DB-powered)
// ============================================================
session_start();
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRecord.php';
require_once __DIR__ . '/../classes/Fine.php';

$usr        = new User($conn);
$borrowObj  = new BorrowRecord($conn);
$fineObj    = new Fine($conn);

// ── Flash messages ──
$success_msg = $_SESSION['flash_success'] ?? '';
$error_msg   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ── Handle form submissions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $phone = trim($_POST['phone'] ?? '');

        if ($phone && !preg_match('/^\+?[\d\s\-]{7,15}$/', $phone)) {
            $_SESSION['flash_error'] = 'Please enter a valid contact number.';
        } else {
            // Load current row so we don't blank locked fields (course, year_level, email)
            $cur = $usr->getStudentById($student_id);
            $ok = $usr->updateProfile($student_id, [
                'email'      => $cur['email']      ?? '',
                'phone'      => $phone,
                'course'     => $cur['course']     ?? '',
                'year_level' => $cur['year_level'] ?? '',
            ]);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] =
                $ok ? 'Profile updated successfully!' : 'Failed to update profile.';
        }
        header('Location: profile.php');
        exit;
    }

    if ($action === 'change_password') {
        $current = $_POST['current_pw'] ?? '';
        $new_pw  = $_POST['new_pw']     ?? '';
        $confirm = $_POST['confirm_pw'] ?? '';

        if (!$current || !$new_pw || !$confirm) {
            $_SESSION['flash_error'] = 'Please fill in all password fields.';
        } elseif ($new_pw !== $confirm) {
            $_SESSION['flash_error'] = 'New passwords do not match.';
        } elseif (strlen($new_pw) < 8) {
            $_SESSION['flash_error'] = 'Password must be at least 8 characters.';
        } else {
            $result = $usr->changePassword($student_id, $current, $new_pw);
            $_SESSION[$result === true ? 'flash_success' : 'flash_error'] =
                $result === true ? 'Password updated successfully!'
                                 : (is_string($result) ? $result : 'Failed to update password.');
        }
        header('Location: profile.php');
        exit;
    }
}

// ── Load student data from DB ──
$me = $usr->getStudentById($student_id);

$student = [
    'id'           => $me['student_number'] ?? '',
    'email'        => $me['email']          ?? '',
    'phone'        => $me['phone']          ?? '',
    'course'       => $me['course']         ?? '',
    'year'         => $me['year_level']     ?? '',
    'department'   => 'College of Engineering and Information Technology',
    'lib_card'     => 'LIB-' . str_pad((string)$student_id, 6, '0', STR_PAD_LEFT),
    'status'       => ucfirst($me['status'] ?? 'Active'),
    'member_since' => !empty($me['created_at']) ? date('Y-m-d', strtotime($me['created_at'])) : '',
];

// Name parts
$first_name  = $me['first_name']  ?? '';
$middle_name = $me['middle_name'] ?? '';
$last_name   = $me['last_name']   ?? '';
if ($first_name === '' && $last_name === '' && !empty($me['full_name'])) {
    $parts = explode(' ', trim($me['full_name']));
    $first_name = $parts[0] ?? '';
    $last_name  = $parts[count($parts) - 1] ?? '';
}

$initials  = strtoupper(substr($first_name, 0, 1)) . strtoupper(substr($last_name, 0, 1));
$full_name = trim("$first_name $middle_name $last_name");

// ── Library stats from DB ──
$history = $borrowObj->getByStudent($student_id);
$active_loans   = count(array_filter($history, fn($r) => in_array($r['status'], ['active','overdue','pending_return'])));
$returned_count = count(array_filter($history, fn($r) => $r['status'] === 'returned'));
$total_borrowed = count($history);

$my_fines = $fineObj->getByStudent($student_id);
$unpaid_total = array_sum(array_map(
    fn($f) => $f['paid_status'] === 'unpaid' ? (float)$f['amount'] : 0,
    $my_fines
));

$stats = [
    'total_borrowed' => $total_borrowed,
    'returned'       => $returned_count,
    'active_loans'   => $active_loans,
    'unpaid_fines'   => $unpaid_total,
];

$has_fines = $stats['unpaid_fines'] > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/profile.css">
  <style>
    /* Eye toggle button inside password input */
    .input-wrap { position: relative; }
    .pw-eye-btn {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      padding: 4px;
      cursor: pointer;
      color: var(--muted, #8a8a8a);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.15s;
    }
    .pw-eye-btn:hover { color: var(--gold, #b88a3e); }
    .input-wrap input[type="password"],
    .input-wrap input[type="text"] { padding-right: 38px; }

    /* Rust/red chip for inactive status */
    .chip-rust {
      background: #fdecec !important;
      color: #a32d2d !important;
      border-color: #f2c4c4 !important;
    }
  </style>
</head>
<body>

<?php require_once '../includes/sidebar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">My Profile</span>
    <div class="topbar-spacer"></div>
    <?php require_once '../includes/student_notifications.php'; ?>
    
    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>


  <main class="page-content">

    <div class="page-header">
      <h1>My <em style="font-style:italic;color:var(--gold)">Profile</em></h1>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
    </div>

    <?php if ($success_msg): ?>
      <div class="alert alert-sage" style="margin-bottom:16px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span><?= htmlspecialchars($success_msg) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
      <div class="alert alert-rust" style="margin-bottom:16px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span><?= htmlspecialchars($error_msg) ?></span>
      </div>
    <?php endif; ?>

    <div class="profile-hero">
      <div class="profile-banner"></div>
      <div class="profile-hero-body">
        <div class="profile-avatar-xl">
          <?= htmlspecialchars($initials) ?>
        </div>
        <div class="profile-hero-info">
          <h2><?= htmlspecialchars($full_name) ?></h2>
          <div class="ph-id">Student ID: <?= htmlspecialchars($student['id']) ?></div>
          <div class="profile-hero-chips">
            <span class="profile-chip chip-gold">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
              Student
            </span>
            <?php
              $is_active = strcasecmp($student['status'], 'Active') === 0;
              $chip_class = $is_active ? 'chip-sage' : 'chip-rust';
              // Inline fallback so the red still shows even if .chip-rust isn't defined in CSS
              $chip_style = $is_active
                ? ''
                : 'style="background:#fdecec;color:#a32d2d;border-color:#f2c4c4;"';
            ?>
            <span class="profile-chip <?= $chip_class ?>" <?= $chip_style ?>>
              <?php if ($is_active): ?>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              <?php else: ?>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              <?php endif; ?>
              <?= htmlspecialchars($student['status']) ?>
            </span>
            <span class="profile-chip">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
              </svg>
              <?= htmlspecialchars($student['course']) ?>
            </span>
          </div>
        </div>
        <div class="profile-hero-actions">
          <button class="btn-edit-toggle" id="editToggleBtn" onclick="toggleEdit()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit Profile
          </button>
        </div>
      </div>

      <div class="profile-stats">
        <div class="ps-item">
          <span class="psi-val"><?= $stats['total_borrowed'] ?></span>
          <span class="psi-lbl">Total Borrowed</span>
        </div>
        <div class="ps-divider"></div>
        <div class="ps-item">
          <span class="psi-val"><?= $stats['returned'] ?></span>
          <span class="psi-lbl">Returned</span>
        </div>
        <div class="ps-divider"></div>
        <div class="ps-item">
          <span class="psi-val"><?= $stats['active_loans'] ?></span>
          <span class="psi-lbl">Active Loans</span>
        </div>
        <div class="ps-divider"></div>
        <div class="ps-item">
          <span class="psi-val" <?= $stats['unpaid_fines'] > 0 ? 'style="color:var(--rust)"' : '' ?>>
            <?= $stats['unpaid_fines'] > 0 ? '₱' . number_format($stats['unpaid_fines']) : 'None' ?>
          </span>
          <span class="psi-lbl">Unpaid Fines</span>
        </div>
        <div class="ps-divider"></div>
        <div class="ps-item">
          <span class="psi-val"><?= date('M j, Y', strtotime($student['member_since'])) ?></span>
          <span class="psi-lbl">Member Since</span>
        </div>
      </div>
    </div>


    <div class="profile-layout">

      <div>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-body">
            <div class="edit-toggle-header">
              <h3>Personal Information</h3>
            </div>

            <div class="view-mode" id="viewMode">
              <div class="info-grid">
                <div class="info-cell left-col">
                  <div class="ic-label">First Name</div>
                  <div class="ic-val"><?= htmlspecialchars($first_name) ?></div>
                </div>
                <div class="info-cell right-col">
                  <div class="ic-label">Middle Name</div>
                  <div class="ic-val"><?= htmlspecialchars($middle_name) ?></div>
                </div>
                <div class="info-cell left-col">
                  <div class="ic-label">Last Name</div>
                  <div class="ic-val"><?= htmlspecialchars($last_name) ?></div>
                </div>
                <div class="info-cell right-col">
                  <div class="ic-label">Email Address</div>
                  <div class="ic-val"><?= htmlspecialchars($student['email']) ?></div>
                </div>
                <div class="info-cell left-col">
                  <div class="ic-label">Contact Number</div>
                  <div class="ic-val"><?= htmlspecialchars($student['phone']) ?></div>
                </div>
                <div class="info-cell right-col">
                  <div class="ic-label">Course / Program</div>
                  <div class="ic-val"><?= htmlspecialchars($student['course']) ?></div>
                </div>
                <div class="info-cell left-col">
                  <div class="ic-label">Year Level</div>
                  <div class="ic-val"><?= htmlspecialchars($student['year']) ?></div>
                </div>
              </div>
            </div>

            <form class="edit-form" id="editForm" method="POST" action="profile.php">
              <input type="hidden" name="action" value="update_profile">

              <div class="field-grid">
                <div class="field">
                  <label>First Name</label>
                  <div class="input-wrap">
                    <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <input type="text" value="<?= htmlspecialchars($first_name) ?>" readonly style="opacity:0.6;cursor:not-allowed;">
                  </div>
                  <div class="field-hint">Name adjustments must be requested at the library counter.</div>
                </div>
                <div class="field">
                  <label>Middle Name</label>
                  <div class="input-wrap">
                    <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <input type="text" value="<?= htmlspecialchars($middle_name) ?>" readonly style="opacity:0.6;cursor:not-allowed;">
                  </div>
                </div>
              </div>

              <div class="field">
                <label>Last Name</label>
                <div class="input-wrap">
                  <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                  <input type="text" value="<?= htmlspecialchars($last_name) ?>" readonly style="opacity:0.6;cursor:not-allowed;">
                </div>
              </div>

              <div class="field">
                <label>Email Address</label>
                <div class="input-wrap">
                  <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                  <input type="email" value="<?= htmlspecialchars($student['email']) ?>" readonly style="opacity:0.6;cursor:not-allowed;">
                </div>
                <div class="field-hint">Institutional email cannot be changed.</div>
              </div>

              <div class="field">
                <label>Contact Number</label>
                <div class="input-wrap">
                  <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.56 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                  <input type="tel" name="phone" value="<?= htmlspecialchars($student['phone']) ?>" placeholder="Contact number">
                </div>
              </div>

              <div class="field-grid">
                <div class="field">
                  <label>Course / Program <small style="color:var(--muted);font-weight:400;">(locked)</small></label>
                  <div class="input-wrap">
                    <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    <input type="text" class="no-icon" style="padding-left:42px;background:#f7f6f1;color:var(--muted);cursor:not-allowed;"
                           value="<?= htmlspecialchars($student['course']) ?>" readonly disabled>
                  </div>
                </div>
                <div class="field">
                  <label>Year Level <small style="color:var(--muted);font-weight:400;">(locked)</small></label>
                  <div class="input-wrap">
                    <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    <input type="text" class="no-icon" style="padding-left:42px;background:#f7f6f1;color:var(--muted);cursor:not-allowed;"
                           value="<?= htmlspecialchars($student['year']) ?>" readonly disabled>
                  </div>
                </div>
              </div>

              <p style="font-size:0.74rem;color:var(--muted);margin:-4px 0 8px;">
                ⓘ Course and Year Level can only be changed by the library administrator.
              </p>

              <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
                <button type="button" class="btn-outline" onclick="cancelEdit()">Cancel</button>
                <button type="submit" class="btn-primary">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  Save Changes
                </button>
              </div>
            </form>

          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="edit-toggle-header"><h3>Change Password</h3></div>

            <div class="alert alert-gold" style="margin-bottom:18px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <span>Use a strong password with at least 8 characters, including letters, numbers, and symbols.</span>
            </div>

            <form method="POST" action="profile.php" id="pwForm">
              <input type="hidden" name="action" value="change_password">

              <div class="field">
                <label>Current Password <span>*</span></label>
                <div class="input-wrap">
                  <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                  <input type="password" name="current_pw" id="currentPw" placeholder="Enter current password">
                  <button type="button" class="pw-eye-btn" onclick="togglePwField('currentPw', this)" aria-label="Show password">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>

              <div class="field">
                <label>New Password <span>*</span></label>
                <div class="input-wrap">
                  <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                  <input type="password" name="new_pw" id="newPw" placeholder="Enter new password" oninput="checkStrength(this.value)">
                  <button type="button" class="pw-eye-btn" onclick="togglePwField('newPw', this)" aria-label="Show password">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <div class="password-strength"><div class="password-strength-bar" id="strengthBar"></div></div>
                <div class="password-strength-label" id="strengthLabel"></div>
              </div>

              <div class="field">
                <label>Confirm New Password <span>*</span></label>
                <div class="input-wrap">
                  <span class="ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                  <input type="password" name="confirm_pw" id="confirmPw" placeholder="Confirm new password">
                  <button type="button" class="pw-eye-btn" onclick="togglePwField('confirmPw', this)" aria-label="Show password">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>

              <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn-primary">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                  Update Password
                </button>
              </div>
            </form>

          </div>
        </div>

      </div>

      <div>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-body">
            <div class="card-title">Account Details</div>
            <div class="card-subtitle">System-assigned information</div>
            <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px;">
              <div class="info-cell" style="padding:0;border:none;">
                <div class="ic-label">Student ID</div>
                <div class="ic-val"><?= htmlspecialchars($student['id']) ?></div>
              </div>
              <div class="info-cell" style="padding:0;border:none;">
                <div class="ic-label">Account Status</div>
                <div class="ic-val">
                  <span class="badge <?= strcasecmp($student['status'], 'Active') === 0 ? 'badge-sage' : 'badge-rust' ?>">
                    <?= htmlspecialchars($student['status']) ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>


        <div class="danger-zone">
          <h4>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            Account Assistance
          </h4>
          <p>If you need to report an issue or dispute a fine, contact the library directly.</p>
          <button class="btn-danger" id="contactLibBtn" onclick="toggleContactPanel()" style="font-size:0.78rem;padding:8px 16px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;border:none;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
            </svg>
            Contact Library
          </button>

          <!-- Contact Panel (hidden by default) -->
          <div id="contactPanel" style="display:none;margin-top:12px;background:#fff;border:1px solid rgba(192,57,43,0.18);border-radius:10px;padding:14px 16px;animation:fadeSlideIn 0.22s ease;">
            <div style="font-size:0.63rem;letter-spacing:0.14em;text-transform:uppercase;color:#aab4cc;margin-bottom:6px;">Library Email</div>
            <a href="mailto:library@cvsu.edu.ph" style="display:inline-flex;align-items:center;gap:8px;font-size:0.88rem;font-weight:600;color:var(--rust);text-decoration:none;word-break:break-all;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
              </svg>
              library@cvsu.edu.ph
            </a>
            <div style="font-size:0.72rem;color:var(--muted);margin-top:6px;line-height:1.5;">Office hours: Mon–Fri, 8:00 AM – 5:00 PM</div>
          </div>
        </div>

      </div>
    </div>

  </main>
</div>

<div class="toast" id="toast"></div>

<script>
  /* ── Mobile sidebar toggle ── */
  function checkMobile() {
    const t = document.getElementById('menuToggle');
    t.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    if (window.innerWidth > 768) document.getElementById('sidebar').classList.remove('open');
  }
  checkMobile();
  window.addEventListener('resize', checkMobile);
  document.getElementById('menuToggle').addEventListener('click', () =>
    document.getElementById('sidebar').classList.toggle('open')
  );

  /* ── Edit toggle ── */
  let isEditing = false;

  function toggleEdit() {
    isEditing = !isEditing;
    const btn      = document.getElementById('editToggleBtn');
    const viewMode = document.getElementById('viewMode');
    const editForm = document.getElementById('editForm');

    if (isEditing) {
      btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancel`;
      btn.classList.add('editing');
      viewMode.classList.add('hidden');
      editForm.classList.add('active');
    } else {
      cancelEdit();
    }
  }

  function cancelEdit() {
    isEditing = false;
    const btn      = document.getElementById('editToggleBtn');
    const viewMode = document.getElementById('viewMode');
    const editForm = document.getElementById('editForm');
    btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit Profile`;
    btn.classList.remove('editing');
    viewMode.classList.remove('hidden');
    editForm.classList.remove('active');
  }

  /* ── Password strength ── */
  function checkStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    bar.className = 'password-strength-bar';
    if (!val) { label.textContent = ''; return; }
    if (val.length < 6) {
      bar.classList.add('strength-weak');
      label.textContent = 'Weak — too short';
      label.style.color = 'var(--rust)';
    } else if (val.length < 10 || !/[0-9]/.test(val)) {
      bar.classList.add('strength-medium');
      label.textContent = 'Medium — add numbers or symbols';
      label.style.color = 'var(--gold-dk)';
    } else {
      bar.classList.add('strength-strong');
      label.textContent = 'Strong password!';
      label.style.color = 'var(--sage)';
    }
  }

  /* ── Contact Library panel toggle ── */
  function toggleContactPanel() {
    const panel = document.getElementById('contactPanel');
    const btn   = document.getElementById('contactLibBtn');
    const open  = panel.style.display === 'none' || panel.style.display === '';
    panel.style.display = open ? 'block' : 'none';
    btn.innerHTML = open
      ? `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Close`
      : `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> Contact Library`;
  }

  /* ── Toast helper ── */
  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (type ? ' ' + type : '');
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  /* ── Show flash messages as toast on load ── */
  <?php if ($success_msg): ?>
    window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($success_msg) ?>, 'success'));
  <?php elseif ($error_msg): ?>
    window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error_msg) ?>, 'error'));
  <?php endif; ?>

  // ── Toggle password visibility ──
  function togglePwField(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
      input.type = 'text';
      btn.setAttribute('aria-label', 'Hide password');
      btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    } else {
      input.type = 'password';
      btn.setAttribute('aria-label', 'Show password');
      btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
  }
</script>
</body>
