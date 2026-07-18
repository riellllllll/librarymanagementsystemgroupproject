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
$usr->backfillQrTokens(); // students created before the QR feature existed get a token now

/**
 * Validates the fields submitted from the Add/Edit Student forms.
 * Returns an array of human-readable error messages (empty array = valid).
 *
 * @param array $data     The trimmed field data (student_number, first_name, last_name, middle_name, email, course, year_level)
 * @param bool  $isEdit   Whether this is an edit (affects wording only)
 */
function validate_student_fields(array $data, bool $isEdit = false): array {
    $errors = [];

    // ── Student Number: required, exactly 9 digits, numbers only ──
    $sno = $data['student_number'] ?? '';
    if ($sno === '') {
        $errors[] = 'Student ID is required.';
    } elseif (!preg_match('/^\d{9}$/', $sno)) {
        $errors[] = 'Student ID must be exactly 9 digits (numbers only).';
    }

    // ── Names: required (first/last), letters/spaces/hyphens/apostrophes only, max 50 chars ──
    $namePattern = "/^[A-Za-zÀ-ÖØ-öø-ÿ' -]+$/u";

    $first = $data['first_name'] ?? '';
    if ($first === '') {
        $errors[] = 'First name is required.';
    } elseif (mb_strlen($first) > 50) {
        $errors[] = 'First name must be 50 characters or fewer.';
    } elseif (!preg_match($namePattern, $first)) {
        $errors[] = 'First name can only contain letters, spaces, hyphens, and apostrophes.';
    }

    $last = $data['last_name'] ?? '';
    if ($last === '') {
        $errors[] = 'Last name is required.';
    } elseif (mb_strlen($last) > 50) {
        $errors[] = 'Last name must be 50 characters or fewer.';
    } elseif (!preg_match($namePattern, $last)) {
        $errors[] = 'Last name can only contain letters, spaces, hyphens, and apostrophes.';
    }

    // Middle name is optional, but if provided it must still be valid text
    $middle = $data['middle_name'] ?? '';
    if ($middle !== '') {
        if (mb_strlen($middle) > 50) {
            $errors[] = 'Middle name must be 50 characters or fewer.';
        } elseif (!preg_match($namePattern, $middle)) {
            $errors[] = 'Middle name can only contain letters, spaces, hyphens, and apostrophes.';
        }
    }

    // ── Email: required, valid format ──
    $email = $data['email'] ?? '';
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // ── Course / Year level: required ──
    if (($data['course'] ?? '') === '') {
        $errors[] = 'Please select a course.';
    }
    if (($data['year_level'] ?? '') === '') {
        $errors[] = 'Please select a year level.';
    }

    // ── Password: required on add only, minimum 8 characters, must match confirmation ──
    if (!$isEdit) {
        $password = $data['password'] ?? '';
        if ($password === '' || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        $confirmPassword = $data['confirm_password'] ?? '';
        if ($confirmPassword === '') {
            $errors[] = 'Please confirm the password.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
    }

    return $errors;
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);

$new_student_qr_id = $_SESSION['new_student_qr_id'] ?? null;
unset($_SESSION['new_student_qr_id']);

// ── Handle form submissions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_student') {
        $data = [
            'student_number' => trim($_POST['student_id']  ?? ''),
            'first_name'     => trim($_POST['first_name']  ?? ''),
            'last_name'      => trim($_POST['last_name']   ?? ''),
            'middle_name'    => trim($_POST['middle_name'] ?? ''),
            'email'          => strtolower(trim($_POST['email'] ?? '')),
            'course'         => $_POST['course']           ?? '',
            'year_level'     => $_POST['year']             ?? '',
            'password'       => $_POST['password']         ?? 'CvSU@2026',
            'confirm_password' => $_POST['confirm_password'] ?? '',
        ];

        $errors = validate_student_fields($data, false);

        if (!empty($errors)) {
            $_SESSION['toast'] = ['type' => 'error', 'message' => implode(' ', $errors)];
            header('Location: manage_students.php');
            exit;
        }

        $newId = $usr->addStudent($data);
        if ($newId) {
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Student account added successfully!'];
            $_SESSION['new_student_qr_id'] = $newId;
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to add student. Student number or email may already exist.'];
        }
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

        $errors = validate_student_fields($data, true);

        if (!empty($errors)) {
            $_SESSION['toast'] = ['type' => 'error', 'message' => implode(' ', $errors)];
            header('Location: manage_students.php');
            exit;
        }

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
        'qr'         => $s['qr_token']    ?? '',
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
<script src="../assets/vendor/qrcode.min.js"></script>
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

  /* ════════════════════════════════════════════
     ADD STUDENT VIEW — fills the content area only;
     sidebar + topbar stay visible/functional.
     ════════════════════════════════════════════ */
  #addStudentView { display: none; }
  #addStudentView.open { display: block; }

  /* BUGFIX: editStudentView had no default display:none, so the full
     edit form was always rendered in the page flow below the table —
     that's what made it look like you could "scroll down to edit". */
  #editStudentView { display: none; }
  #editStudentView.open { display: block; }

  .ms-fs-topbar {
    position: sticky;
    top: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    gap: 24px;
    margin: -28px -28px 24px;
    padding: 18px 28px;
    background: var(--card-bg);
    border-bottom: 1px solid var(--border);
    box-shadow: 0 2px 12px rgba(15,22,35,0.05);
  }
  .ms-fs-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid var(--border);
    background: var(--card-bg);
    color: var(--ink);
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 9px 16px;
    border-radius: 10px;
    cursor: pointer;
    transition: all .15s ease;
    white-space: nowrap;
  }
  .ms-fs-back:hover { border-color: var(--gold); color: var(--gold-dk); background: #fdf8ee; }
  .ms-fs-topbar-title h1 {
    font-family: 'Inter', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 2px;
    color: var(--ink);
  }
  .ms-fs-topbar-title p {
    font-size: 0.8rem;
    color: var(--muted);
    margin: 0;
  }
  .ms-fs-topbar-spacer { flex: 1; }

  .ms-fs-form-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 24px;
    align-items: start;
  }

  /* left column: preview + QR note */
  .ms-fs-preview-col {
    display: flex;
    flex-direction: column;
    gap: 18px;
    position: sticky;
    top: 96px;
  }
  .ms-fs-idcard {
    background: var(--card-bg);
    border-radius: var(--radius-card);
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(15,22,35,0.08), 0 0 0 1px rgba(201,151,58,0.08);
    text-align: center;
    padding-bottom: 22px;
  }
  .ms-fs-idcard-top {
    height: 60px;
    background: linear-gradient(120deg, var(--gold-dk), var(--gold-lt));
  }
  .ms-fs-idcard-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    border: 4px solid var(--card-bg);
    box-shadow: 0 2px 8px rgba(15,22,35,0.15);
    margin: -36px auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, var(--gold-dk), var(--gold-lt));
  }
  .ms-fs-idcard-sno {
    font-family: 'Inter', sans-serif;
    letter-spacing: 0.04em;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--ink);
  }
  .ms-fs-idcard-email {
    font-size: 0.78rem;
    color: var(--muted);
    margin-top: 2px;
    word-break: break-all;
    padding: 0 16px;
  }
  .ms-fs-idcard-name {
    font-size: 1.05rem;
    font-weight: 600;
    margin-top: 12px;
    color: var(--ink);
  }
  .ms-fs-idcard-meta {
    margin-top: 6px;
    font-size: 0.78rem;
    color: var(--gold-dk);
    font-weight: 600;
  }
  .ms-fs-idcard-meta .dot { margin: 0 6px; opacity: .5; }

  .ms-fs-qr-placeholder {
    background: var(--card-bg);
    border: 1.5px dashed rgba(201,151,58,0.4);
    border-radius: 16px;
    padding: 20px 18px;
    text-align: center;
    color: var(--muted);
  }
  .ms-fs-qr-icon { color: var(--gold); margin-bottom: 8px; }
  .ms-fs-qr-title {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--ink);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 6px;
  }
  .ms-fs-soon-badge {
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    background: #fdf8ee;
    color: var(--gold-dk);
    border: 1px solid rgba(201,151,58,0.3);
    padding: 3px 8px;
    border-radius: 20px;
  }
  .ms-fs-qr-placeholder p { font-size: 0.76rem; line-height: 1.45; margin: 0; }

  /* right column: form sections */
  .ms-fs-fields-col { display: flex; flex-direction: column; gap: 18px; }
  .ms-fs-section {
    background: var(--card-bg);
    border-radius: var(--radius-card);
    border: 1px solid var(--border);
    box-shadow: 0 2px 10px rgba(15,22,35,0.04);
    padding: 22px 24px;
  }
  .ms-fs-section-head { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
  .ms-fs-section-num {
    flex: 0 0 auto;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold-dk), var(--gold-lt));
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .ms-fs-section-head h3 { font-family: 'Inter', sans-serif; font-size: 0.96rem; font-weight: 700; margin: 0 0 2px; color: var(--ink); }
  .ms-fs-section-head p { font-size: 0.78rem; color: var(--muted); margin: 0; }

  .ms-fs-footer {
    position: sticky;
    bottom: 0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin: 24px -28px -28px;
    padding: 16px 28px;
    background: var(--card-bg);
    border-top: 1px solid var(--border);
  }

  @media (max-width: 860px) {
    .ms-fs-form-grid { grid-template-columns: 1fr; }
    .ms-fs-preview-col { position: static; }
    .ms-fs-topbar { margin: -20px -16px 20px; padding: 14px 16px; }
    .ms-fs-footer { margin: 20px -16px -20px; padding: 14px 16px; }
  }

  /* ── QR action button + modal ── */
  .ms-action-qr { border-color: rgba(201,151,58,0.3); color: var(--gold-dk); }
  .ms-action-qr:hover { border-color: var(--gold); color: var(--gold-dk); background: #fdf8ee; box-shadow: 0 2px 8px rgba(201,151,58,0.15); }
  .ms-qr-modal-body { text-align: center; }
  .ms-qr-box {
    width: 220px;
    height: 220px;
    margin: 4px auto 18px;
    padding: 14px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .ms-qr-box img, .ms-qr-box canvas { width: 100% !important; height: 100% !important; }
  .ms-qr-student-name { font-size: 1.05rem; font-weight: 700; color: var(--ink); margin-bottom: 2px; }
  .ms-qr-student-sub { font-size: 0.8rem; color: var(--muted); margin-bottom: 20px; }
  .ms-qr-just-added {
    background: #fdf8ee;
    color: var(--gold-dk);
    border: 1px solid rgba(201,151,58,0.3);
    font-size: 0.8rem;
    font-weight: 600;
    padding: 8px 14px;
    border-radius: 10px;
    margin-bottom: 16px;
    display: inline-block;
  }
  .ms-qr-thumb-wrap {
    width: 88px;
    height: 88px;
    padding: 6px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    margin-left: auto;
    flex-shrink: 0;
  }
  .ms-qr-thumb-wrap img, .ms-qr-thumb-wrap canvas { width: 100% !important; height: 100% !important; }
  /* ── Inline field validation ── */
  .field-error {
    display: none;
    color: #c0392b;
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 5px;
    line-height: 1.3;
  }
  .field-error.show { display: block; }
  .input-wrap.input-invalid {
    border-color: #c0392b !important;
    box-shadow: 0 0 0 1px rgba(192, 57, 43, 0.25);
  }
  .input-wrap.input-invalid input,
  .input-wrap.input-invalid select {
    color: #c0392b;
  }
  /* ── Password visibility toggle ── */
  .input-wrap.pw-wrap {
    position: relative;
  }
  .input-wrap.pw-wrap input[type="password"],
  .input-wrap.pw-wrap input[type="text"] {
    padding-right: 40px;
  }
  .pw-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    padding: 0;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--muted, #8a8078);
    border-radius: 6px;
    transition: color 0.15s ease, background 0.15s ease;
  }
  .pw-toggle:hover {
    color: var(--ink, #2b2420);
    background: rgba(0,0,0,0.05);
  }
  .pw-toggle:focus-visible {
    outline: 2px solid var(--gold, #c9973a);
    outline-offset: 1px;
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

    <div id="manageStudentsView">

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
                  <button class="ms-action-btn ms-action-qr" title="QR Code" onclick="openQrModal(<?php echo $student['id']; ?>)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><line x1="14" y1="14" x2="14" y2="21"/><line x1="21" y1="14" x2="21" y2="21"/><line x1="17.5" y1="14" x2="17.5" y2="17.5"/><line x1="14" y1="17.5" x2="21" y2="17.5"/></svg>
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

        <!-- Pagination — built and driven entirely by JS (see renderPaginationControls),
             so it always matches whatever the search/filters currently show. -->
        <div class="pagination" id="paginationControls"></div>
      </div>
    </div>

    </div> <!-- /#manageStudentsView -->

    <!-- ════════════════════════════════════════════
         ADD STUDENT VIEW — fills the content area only;
         sidebar + topbar stay visible/functional.
         ════════════════════════════════════════════ -->
    <div id="addStudentView">

      <div class="ms-fs-topbar">
        <button type="button" class="ms-fs-back" onclick="closeAddModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Manage Students
        </button>
        <div class="ms-fs-topbar-title">
          <h1>Add New Student</h1>
          <p>Register a new student account in the library system.</p>
        </div>
        <div class="ms-fs-topbar-spacer"></div>
      </div>

      <form method="POST" action="manage_students.php" id="addStudentForm" class="ms-fs-form-grid">
        <input type="hidden" name="action" value="add_student">

        <!-- LEFT: live ID preview + QR identity note -->
        <div class="ms-fs-preview-col">
          <div class="ms-fs-idcard">
            <div class="ms-fs-idcard-top"></div>
            <div class="ms-fs-idcard-avatar" id="fsAvatar">?</div>
            <div class="ms-fs-idcard-sno" id="fsPreviewSno">Student No.</div>
            <div class="ms-fs-idcard-email" id="fsPreviewEmail">email@cvsu.edu.ph</div>
            <div class="ms-fs-idcard-name" id="fsPreviewName">Full Name</div>
            <div class="ms-fs-idcard-meta">
              <span id="fsPreviewCourse">Course</span><span class="dot">•</span><span id="fsPreviewYear">Year</span>
            </div>
          </div>

          <!-- QR identity is generated automatically once the account is saved -->
          <div class="ms-fs-qr-placeholder">
            <svg class="ms-fs-qr-icon" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7" rx="1"/>
              <rect x="14" y="3" width="7" height="7" rx="1"/>
              <rect x="3" y="14" width="7" height="7" rx="1"/>
              <line x1="14" y1="14" x2="14" y2="21"/>
              <line x1="21" y1="14" x2="21" y2="21"/>
              <line x1="17.5" y1="14" x2="17.5" y2="17.5"/>
              <line x1="14" y1="17.5" x2="21" y2="17.5"/>
            </svg>
            <div class="ms-fs-qr-title">QR Identity Card <span class="ms-fs-soon-badge">Auto-generated</span></div>
            <p>A unique QR code will be created for this student the moment you save — it'll pop up right after so you can print it for attendance use.</p>
          </div>
        </div>

        <!-- RIGHT: form sections -->
        <div class="ms-fs-fields-col">

          <div class="ms-fs-section">
            <div class="ms-fs-section-head">
              <span class="ms-fs-section-num">1</span>
              <div>
                <h3>Identification</h3>
                <p>Student number and official school email</p>
              </div>
            </div>

            <div class="field">
              <label>Student Number <span>*</span></label>
              <div class="input-wrap" id="studentIdWrap">
                <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                <input type="text" name="student_id" id="studentIdInput" placeholder="e.g. 202400123" required
                       inputmode="numeric" pattern="\d{9}" maxlength="9" autocomplete="off"
                       title="Student ID must be exactly 9 digits (numbers only)"
                       oninput="sanitizeDigits(this); updateFsPreview(); validateStudentId(this, 'studentIdWrap', 'studentIdError');">
              </div>
              <p class="field-error" id="studentIdError"></p>
            </div>

            <div class="field">
              <label>Email Address <span>*</span></label>
              <div class="input-wrap" id="emailWrap">
                <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                <input type="email" name="email" id="emailInput" placeholder="e.g. juan.delacruz@cvsu.edu.ph" required maxlength="100" oninput="updateFsPreview(); validateEmailField(this, 'emailWrap', 'emailError');">
              </div>
              <p class="field-error" id="emailError"></p>
            </div>
          </div>

          <div class="ms-fs-section">
            <div class="ms-fs-section-head">
              <span class="ms-fs-section-num">2</span>
              <div>
                <h3>Personal Information</h3>
                <p>The student's legal first and last name</p>
              </div>
            </div>

            <div class="field-grid">
              <div class="field">
                <label>First Name <span>*</span></label>
                <div class="input-wrap" id="firstNameWrap">
                  <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                  <input type="text" name="first_name" id="firstNameInput" placeholder="e.g. Juan" required maxlength="50"
                         pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" title="Only letters, spaces, hyphens, and apostrophes are allowed"
                         oninput="sanitizeName(this); updateFsPreview(); validateNameField(this, 'firstNameWrap', 'firstNameError', 'First name', true);">
                </div>
                <p class="field-error" id="firstNameError"></p>
              </div>
              <div class="field">
                <label>Last Name <span>*</span></label>
                <div class="input-wrap" id="lastNameWrap">
                  <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                  <input type="text" name="last_name" id="lastNameInput" placeholder="e.g. Dela Cruz" required maxlength="50"
                         pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" title="Only letters, spaces, hyphens, and apostrophes are allowed"
                         oninput="sanitizeName(this); updateFsPreview(); validateNameField(this, 'lastNameWrap', 'lastNameError', 'Last name', true);">
                </div>
                <p class="field-error" id="lastNameError"></p>
              </div>
            </div>

            <div class="field">
              <label>Middle Name <small style="color:var(--muted);font-weight:400;">(optional)</small></label>
              <div class="input-wrap" id="middleNameWrap">
                <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <input type="text" name="middle_name" id="middleNameInput" placeholder="Middle name" maxlength="50"
                       pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]*" title="Only letters, spaces, hyphens, and apostrophes are allowed"
                       oninput="sanitizeName(this); updateFsPreview(); validateNameField(this, 'middleNameWrap', 'middleNameError', 'Middle name', false);">
              </div>
              <p class="field-error" id="middleNameError"></p>
            </div>
          </div>

          <div class="ms-fs-section">
            <div class="ms-fs-section-head">
              <span class="ms-fs-section-num">3</span>
              <div>
                <h3>Academic Details</h3>
                <p>Course and current year level</p>
              </div>
            </div>

            <div class="field-grid">
              <div class="field">
                <label>Course <span>*</span></label>
                <div class="input-wrap">
                  <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></span>
                  <select name="course" required onchange="updateFsPreview()">
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
                  <select name="year" required onchange="updateFsPreview()">
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
          </div>

          <div class="ms-fs-section">
            <div class="ms-fs-section-head">
              <span class="ms-fs-section-num">4</span>
              <div>
                <h3>Account Security</h3>
                <p>Login password for the student portal</p>
              </div>
            </div>

            <div class="field">
              <label>Password <span>*</span></label>
              <div class="input-wrap pw-wrap" id="passwordWrap">
                <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                <input type="password" name="password" id="passwordInput" placeholder="Create a password" required
                       oninput="validatePasswordField(this, 'passwordWrap', 'passwordError'); validateConfirmPasswordField(document.getElementById('confirmPasswordInput'), 'confirmPasswordWrap', 'confirmPasswordError');">
                <button type="button" class="pw-toggle" aria-label="Show password" onclick="togglePasswordVisibility('passwordInput', this)">
                  <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.8 21.8 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
              <p class="field-error" id="passwordError"></p>
              <p class="field-hint">Minimum 8 characters with letters and numbers.</p>
            </div>

            <div class="field">
              <label>Confirm Password <span>*</span></label>
              <div class="input-wrap pw-wrap" id="confirmPasswordWrap">
                <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                <input type="password" name="confirm_password" id="confirmPasswordInput" placeholder="Re-enter the password" required
                       oninput="validateConfirmPasswordField(this, 'confirmPasswordWrap', 'confirmPasswordError');">
                <button type="button" class="pw-toggle" aria-label="Show password" onclick="togglePasswordVisibility('confirmPasswordInput', this)">
                  <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.8 21.8 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
              <p class="field-error" id="confirmPasswordError"></p>
            </div>
          </div>

        </div>
      </form>

      <div class="ms-fs-footer">
        <button type="button" class="btn-outline" onclick="closeAddModal()">Cancel</button>
        <button type="submit" form="addStudentForm" class="btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Student
        </button>
      </div>

    </div>

    <!-- ════════════════════════════════════════════
         EDIT STUDENT VIEW — same layout as Add Student;
         sidebar + topbar stay visible/functional.
         ════════════════════════════════════════════ -->
    <div id="editStudentView">

      <div class="ms-fs-topbar">
        <button type="button" class="ms-fs-back" onclick="closeEditModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Manage Students
        </button>
        <div class="ms-fs-topbar-title">
          <h1>Edit Student</h1>
          <p>Update this student's information in the library system.</p>
        </div>
        <div class="ms-fs-topbar-spacer"></div>
      </div>

      <form method="POST" action="manage_students.php" id="editStudentForm" class="ms-fs-form-grid">
        <input type="hidden" name="action" value="edit_student">
        <input type="hidden" name="edit_id" id="editId">

        <!-- LEFT: live ID preview + QR identity -->
        <div class="ms-fs-preview-col">
          <div class="ms-fs-idcard">
            <div class="ms-fs-idcard-top"></div>
            <div class="ms-fs-idcard-avatar" id="editFsAvatar">?</div>
            <div class="ms-fs-idcard-sno" id="editFsPreviewSno">Student No.</div>
            <div class="ms-fs-idcard-email" id="editFsPreviewEmail">email@cvsu.edu.ph</div>
            <div class="ms-fs-idcard-name" id="editFsPreviewName">Full Name</div>
            <div class="ms-fs-idcard-meta">
              <span id="editFsPreviewCourse">Course</span><span class="dot">•</span><span id="editFsPreviewYear">Year</span>
            </div>
          </div>

          <div class="ms-fs-qr-placeholder" style="cursor:pointer;" onclick="openQrModalFromEdit()" title="Click to view full QR code">
            <div class="ms-qr-thumb-wrap" style="margin:0 auto 10px;" id="editQrThumbWrap">
              <div id="editQrThumb"></div>
            </div>
            <div class="ms-fs-qr-title">QR Identity Card</div>
            <p>This student's attendance QR code. Click to view, print, or download it.</p>
          </div>
        </div>

        <!-- RIGHT: form sections -->
        <div class="ms-fs-fields-col">

          <div class="ms-fs-section">
            <div class="ms-fs-section-head">
              <span class="ms-fs-section-num">1</span>
              <div>
                <h3>Identification</h3>
                <p>Student number and official school email</p>
              </div>
            </div>

            <div class="field">
              <label>Student Number <span>*</span></label>
              <div class="input-wrap" id="editStudentIdWrap">
                <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                <input type="text" name="edit_student_id" id="editStudentIdInput" required
                       inputmode="numeric" pattern="\d{9}" maxlength="9" autocomplete="off"
                       title="Student ID must be exactly 9 digits (numbers only)"
                       oninput="sanitizeDigits(this); updateFsEditPreview(); validateStudentId(this, 'editStudentIdWrap', 'editStudentIdError');">
              </div>
              <p class="field-error" id="editStudentIdError"></p>
            </div>

            <div class="field">
              <label>Email Address <span>*</span></label>
              <div class="input-wrap" id="editEmailWrap">
                <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                <input type="email" name="edit_email" id="editEmail" required maxlength="100" oninput="updateFsEditPreview(); validateEmailField(this, 'editEmailWrap', 'editEmailError');">
              </div>
              <p class="field-error" id="editEmailError"></p>
            </div>
          </div>

          <div class="ms-fs-section">
            <div class="ms-fs-section-head">
              <span class="ms-fs-section-num">2</span>
              <div>
                <h3>Personal Information</h3>
                <p>The student's legal name</p>
              </div>
            </div>

            <div class="field-grid">
              <div class="field">
                <label>First Name <span>*</span></label>
                <div class="input-wrap" id="editFirstNameWrap">
                  <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                  <input type="text" name="edit_first_name" id="editFirstName" required maxlength="50"
                         pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" title="Only letters, spaces, hyphens, and apostrophes are allowed"
                         oninput="sanitizeName(this); updateFsEditPreview(); validateNameField(this, 'editFirstNameWrap', 'editFirstNameError', 'First name', true);">
                </div>
                <p class="field-error" id="editFirstNameError"></p>
              </div>
              <div class="field">
                <label>Last Name <span>*</span></label>
                <div class="input-wrap" id="editLastNameWrap">
                  <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                  <input type="text" name="edit_last_name" id="editLastName" required maxlength="50"
                         pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" title="Only letters, spaces, hyphens, and apostrophes are allowed"
                         oninput="sanitizeName(this); updateFsEditPreview(); validateNameField(this, 'editLastNameWrap', 'editLastNameError', 'Last name', true);">
                </div>
                <p class="field-error" id="editLastNameError"></p>
              </div>
            </div>

            <div class="field">
              <label>Middle Name <small style="color:var(--muted);font-weight:400;">(optional)</small></label>
              <div class="input-wrap" id="editMiddleNameWrap">
                <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <input type="text" name="edit_middle_name" id="editMiddleName" placeholder="Middle name" maxlength="50"
                       pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]*" title="Only letters, spaces, hyphens, and apostrophes are allowed"
                       oninput="sanitizeName(this); updateFsEditPreview(); validateNameField(this, 'editMiddleNameWrap', 'editMiddleNameError', 'Middle name', false);">
              </div>
              <p class="field-error" id="editMiddleNameError"></p>
            </div>
          </div>

          <div class="ms-fs-section">
            <div class="ms-fs-section-head">
              <span class="ms-fs-section-num">3</span>
              <div>
                <h3>Academic Details</h3>
                <p>Course and current year level</p>
              </div>
            </div>

            <div class="field-grid">
              <div class="field">
                <label>Course <span>*</span></label>
                <div class="input-wrap">
                  <span class="ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></span>
                  <select name="edit_course" id="editCourse" required onchange="updateFsEditPreview()">
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
                  <select name="edit_year" id="editYear" required onchange="updateFsEditPreview()">
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                  </select>
                  <span class="select-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
                </div>
              </div>
            </div>
          </div>

          <div class="ms-fs-section">
            <div class="ms-fs-section-head">
              <span class="ms-fs-section-num">4</span>
              <div>
                <h3>Account Status</h3>
                <p>Whether this account can currently log in and borrow books</p>
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
          </div>

        </div>
      </form>

      <div class="ms-fs-footer">
        <button type="button" class="btn-outline" onclick="closeEditModal()">Cancel</button>
        <button type="submit" form="editStudentForm" class="btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Save Changes
        </button>
      </div>

    </div>

  </main>
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
        <div class="ms-qr-thumb-wrap" id="viewQrThumbWrap" title="Click to view full QR code" style="cursor:pointer;" onclick="openQrModalFromView()">
          <div id="viewQrThumb"></div>
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
     QR CODE MODAL
     ════════════════════════════════════════════ -->
<div class="modal-backdrop" id="qrModal">
  <div class="modal ms-modal" style="max-width:380px;">
    <div class="modal-top"></div>
    <button class="modal-close" onclick="closeQrModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="modal-body ms-qr-modal-body">
      <span class="ms-qr-just-added" id="qrJustAdded" style="display:none;">Student added — here's their QR code</span>
      <h2 class="modal-title">Student Identity QR</h2>
      <p class="modal-desc">Scan this code to identify the student's account for future attendance check-in.</p>

      <div class="ms-qr-box" id="qrModalBox"></div>

      <div class="ms-qr-student-name" id="qrStudentName"></div>
      <div class="ms-qr-student-sub" id="qrStudentSub"></div>

      <div class="ms-modal-actions">
        <button type="button" class="btn-outline" onclick="downloadQr()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download PNG
        </button>
        <button type="button" class="btn-primary" onclick="printQr()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ══════════════════════════════════════════════════════
// Field validation helpers (Add/Edit Student forms)
// ══════════════════════════════════════════════════════
const NAME_PATTERN = /^[A-Za-zÀ-ÖØ-öø-ÿ' -]*$/;

// Strip anything that isn't a digit, and cap length at 9 (Student ID).
function sanitizeDigits(el) {
  const caret = el.selectionStart;
  const before = el.value.length;
  el.value = el.value.replace(/\D/g, '').slice(0, 9);
  const after = el.value.length;
  if (caret !== null) {
    const newPos = Math.max(0, caret - (before - after));
    el.setSelectionRange(newPos, newPos);
  }
}

// Strip anything that isn't a letter, space, hyphen, or apostrophe (names).
function sanitizeName(el) {
  const caret = el.selectionStart;
  const before = el.value.length;
  el.value = el.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ' -]/g, '');
  const after = el.value.length;
  if (caret !== null) {
    const newPos = Math.max(0, caret - (before - after));
    el.setSelectionRange(newPos, newPos);
  }
}

function setFieldError(el, wrapId, errorId, message) {
  const wrap = wrapId ? document.getElementById(wrapId) : null;
  const errEl = errorId ? document.getElementById(errorId) : null;
  if (message) {
    if (wrap) wrap.classList.add('input-invalid');
    if (errEl) { errEl.textContent = message; errEl.classList.add('show'); }
    if (el) el.setCustomValidity(message);
  } else {
    if (wrap) wrap.classList.remove('input-invalid');
    if (errEl) { errEl.textContent = ''; errEl.classList.remove('show'); }
    if (el) el.setCustomValidity('');
  }
}

// Student ID: required, must be exactly 9 digits.
function validateStudentId(el, wrapId, errorId) {
  const v = el.value.trim();
  if (v === '') {
    setFieldError(el, wrapId, errorId, 'Student ID is required.');
    return false;
  }
  if (!/^\d{9}$/.test(v)) {
    setFieldError(el, wrapId, errorId, `Student ID must be exactly 9 digits (currently ${v.length}).`);
    return false;
  }
  setFieldError(el, wrapId, errorId, '');
  return true;
}

// Name fields: letters/spaces/hyphens/apostrophes only; required flag controls whether empty is allowed.
function validateNameField(el, wrapId, errorId, label, required) {
  const v = el.value.trim();
  if (required && v === '') {
    setFieldError(el, wrapId, errorId, `${label} is required.`);
    return false;
  }
  if (v !== '' && !NAME_PATTERN.test(v)) {
    setFieldError(el, wrapId, errorId, `${label} can only contain letters, spaces, hyphens, and apostrophes.`);
    return false;
  }
  setFieldError(el, wrapId, errorId, '');
  return true;
}

// Email: required, basic format check.
function validateEmailField(el, wrapId, errorId) {
  const v = el.value.trim();
  const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (v === '') {
    setFieldError(el, wrapId, errorId, 'Email address is required.');
    return false;
  }
  if (!EMAIL_PATTERN.test(v)) {
    setFieldError(el, wrapId, errorId, 'Please enter a valid email address.');
    return false;
  }
  setFieldError(el, wrapId, errorId, '');
  return true;
}

// Password: required, minimum 8 characters (add-student form only).
function validatePasswordField(el, wrapId, errorId) {
  const v = el.value;
  if (v === '') {
    setFieldError(el, wrapId, errorId, 'Password is required.');
    return false;
  }
  if (v.length < 8) {
    setFieldError(el, wrapId, errorId, 'Password must be at least 8 characters.');
    return false;
  }
  setFieldError(el, wrapId, errorId, '');
  return true;
}

// Confirm password: required, must match the password field.
function validateConfirmPasswordField(el, wrapId, errorId) {
  const passwordEl = document.getElementById('passwordInput');
  const v = el.value;
  if (v === '') {
    setFieldError(el, wrapId, errorId, 'Please confirm the password.');
    return false;
  }
  if (passwordEl && v !== passwordEl.value) {
    setFieldError(el, wrapId, errorId, 'Passwords do not match.');
    return false;
  }
  setFieldError(el, wrapId, errorId, '');
  return true;
}

// Toggles a password input between hidden and visible text, swapping the eye icon.
function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const showing = input.type === 'text';
  input.type = showing ? 'password' : 'text';
  btn.querySelector('.eye-open').style.display = showing ? '' : 'none';
  btn.querySelector('.eye-closed').style.display = showing ? 'none' : '';
  btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
}

// Run every check for a form; returns true only if all pass. Scrolls/focuses the first bad field.
function validateStudentForm(prefix) {
  const isEdit = prefix === 'edit';

  const idInput    = document.getElementById(isEdit ? 'editStudentIdInput' : 'studentIdInput');
  const emailInput = document.getElementById(isEdit ? 'editEmail' : 'emailInput');
  const firstInput = document.getElementById(isEdit ? 'editFirstName' : 'firstNameInput');
  const lastInput  = document.getElementById(isEdit ? 'editLastName' : 'lastNameInput');
  const midInput   = document.getElementById(isEdit ? 'editMiddleName' : 'middleNameInput');

  const idWrap    = isEdit ? 'editStudentIdWrap' : 'studentIdWrap';
  const emailWrap = isEdit ? 'editEmailWrap' : 'emailWrap';
  const firstWrap = isEdit ? 'editFirstNameWrap' : 'firstNameWrap';
  const lastWrap  = isEdit ? 'editLastNameWrap' : 'lastNameWrap';
  const midWrap   = isEdit ? 'editMiddleNameWrap' : 'middleNameWrap';

  const idErr    = isEdit ? 'editStudentIdError' : 'studentIdError';
  const emailErr = isEdit ? 'editEmailError' : 'emailError';
  const firstErr = isEdit ? 'editFirstNameError' : 'firstNameError';
  const lastErr  = isEdit ? 'editLastNameError' : 'lastNameError';
  const midErr   = isEdit ? 'editMiddleNameError' : 'middleNameError';

  const results = [
    { ok: validateStudentId(idInput, idWrap, idErr), el: idInput },
    { ok: validateEmailField(emailInput, emailWrap, emailErr), el: emailInput },
    { ok: validateNameField(firstInput, firstWrap, firstErr, 'First name', true), el: firstInput },
    { ok: validateNameField(lastInput, lastWrap, lastErr, 'Last name', true), el: lastInput },
    { ok: validateNameField(midInput, midWrap, midErr, 'Middle name', false), el: midInput },
  ];

  if (!isEdit) {
    const passwordInput = document.getElementById('passwordInput');
    const confirmInput  = document.getElementById('confirmPasswordInput');
    results.push({ ok: validatePasswordField(passwordInput, 'passwordWrap', 'passwordError'), el: passwordInput });
    results.push({ ok: validateConfirmPasswordField(confirmInput, 'confirmPasswordWrap', 'confirmPasswordError'), el: confirmInput });
  }

  const firstFailure = results.find(r => !r.ok);
  if (firstFailure) {
    firstFailure.el.focus();
    firstFailure.el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return false;
  }
  return true;
}

document.getElementById('addStudentForm').addEventListener('submit', function(e) {
  if (!validateStudentForm('add')) {
    e.preventDefault();
  }
});
document.getElementById('editStudentForm').addEventListener('submit', function(e) {
  if (!validateStudentForm('edit')) {
    e.preventDefault();
  }
});

// Clears every inline error message + invalid-state styling for a given form's fields.
function clearFieldErrors(errorIds, wrapIds) {
  errorIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.textContent = ''; el.classList.remove('show'); }
  });
  wrapIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('input-invalid');
  });
}
const ADD_ERROR_IDS = ['studentIdError', 'emailError', 'firstNameError', 'lastNameError', 'middleNameError', 'passwordError', 'confirmPasswordError'];
const ADD_WRAP_IDS  = ['studentIdWrap', 'emailWrap', 'firstNameWrap', 'lastNameWrap', 'middleNameWrap', 'passwordWrap', 'confirmPasswordWrap'];
const EDIT_ERROR_IDS = ['editStudentIdError', 'editEmailError', 'editFirstNameError', 'editLastNameError', 'editMiddleNameError'];
const EDIT_WRAP_IDS  = ['editStudentIdWrap', 'editEmailWrap', 'editFirstNameWrap', 'editLastNameWrap', 'editMiddleNameWrap'];

// ── Add Student view (fills the content area; sidebar/topbar stay visible) ──
function resetPasswordVisibility() {
  ['passwordInput', 'confirmPasswordInput'].forEach(id => {
    const input = document.getElementById(id);
    const btn = input ? input.parentElement.querySelector('.pw-toggle') : null;
    if (input) input.type = 'password';
    if (btn) {
      btn.querySelector('.eye-open').style.display = '';
      btn.querySelector('.eye-closed').style.display = 'none';
      btn.setAttribute('aria-label', 'Show password');
    }
  });
}
function openAddModal() {
  document.getElementById('manageStudentsView').style.display = 'none';
  document.getElementById('addStudentView').classList.add('open');
  window.scrollTo({ top: 0, behavior: 'instant' });
  resetFsPreview();
  clearFieldErrors(ADD_ERROR_IDS, ADD_WRAP_IDS);
  resetPasswordVisibility();
}
function closeAddModal() {
  document.getElementById('addStudentView').classList.remove('open');
  document.getElementById('manageStudentsView').style.display = '';
  document.getElementById('addStudentForm').reset();
  resetFsPreview();
  clearFieldErrors(ADD_ERROR_IDS, ADD_WRAP_IDS);
  resetPasswordVisibility();
}

// ── Fullscreen: live ID-card preview while filling the form ──
function resetFsPreview() {
  document.getElementById('fsAvatar').textContent = '?';
  document.getElementById('fsPreviewSno').textContent = 'Student No.';
  document.getElementById('fsPreviewEmail').textContent = 'email@cvsu.edu.ph';
  document.getElementById('fsPreviewName').textContent = 'Full Name';
  document.getElementById('fsPreviewCourse').textContent = 'Course';
  document.getElementById('fsPreviewYear').textContent = 'Year';
}
function updateFsPreview() {
  const form   = document.getElementById('addStudentForm');
  const sno    = form.student_id.value.trim();
  const email  = form.email.value.trim();
  const first  = form.first_name.value.trim();
  const middle = form.middle_name.value.trim();
  const last   = form.last_name.value.trim();
  const course = form.course.value;
  const year   = form.year.value;

  const fullName = [first, middle, last].filter(Boolean).join(' ');

  document.getElementById('fsPreviewSno').textContent   = sno || 'Student No.';
  document.getElementById('fsPreviewEmail').textContent = email || 'email@cvsu.edu.ph';
  document.getElementById('fsPreviewName').textContent  = fullName || 'Full Name';
  document.getElementById('fsPreviewCourse').textContent = course || 'Course';
  document.getElementById('fsPreviewYear').textContent   = year || 'Year';

  const initials = ((first[0] || '') + (last[0] || '')).toUpperCase();
  document.getElementById('fsAvatar').textContent = initials || '?';
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
    closeQrModal();
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

// ── Pagination state ──
const studentsPerPage = 10;
let studentsCurrentPage = 1;
let studentsFilteredRows = [];

function applyStudentFilters() {
  const term      = document.getElementById('searchStudents').value.toLowerCase();
  const course    = studentFilterState.course; // array
  const year      = studentFilterState.year;   // array
  const status    = studentFilterState.status;
  const borrowed  = studentFilterState.borrowed;
  const fines     = studentFilterState.fines;

  const rows = Array.from(document.querySelectorAll('#studentsTable tbody tr'));

  studentsFilteredRows = rows.filter(function(row) {
    const text       = row.textContent.toLowerCase();
    const rCourse    = row.dataset.course || '';
    const rYear      = row.dataset.year || '';
    const rStatus    = row.dataset.status || '';
    const rBorrowed  = parseInt(row.dataset.borrowed, 10) || 0;
    const rFines     = parseFloat(row.dataset.fines) || 0;

    if (term && !text.includes(term))               return false;
    if (course.length && !course.includes(rCourse)) return false;
    if (year.length && !year.includes(rYear))        return false;
    if (status && rStatus !== status)                return false;
    if (borrowed === 'has'  && rBorrowed <= 0)       return false;
    if (borrowed === 'none' && rBorrowed > 0)        return false;
    if (fines === 'has'  && rFines <= 0)             return false;
    if (fines === 'none' && rFines > 0)              return false;
    return true;
  });

  const countEl = document.getElementById('filterResultCount');
  const totalRows = rows.length;
  countEl.textContent = (studentsFilteredRows.length === totalRows)
    ? ''
    : `Showing ${studentsFilteredRows.length} of ${totalRows}`;

  studentsCurrentPage = 1;
  renderStudentsPage();
}

// Show only the current page's worth (10) of the filtered rows,
// hide everything else, and rebuild the pagination controls.
function renderStudentsPage() {
  const allRows = document.querySelectorAll('#studentsTable tbody tr');
  allRows.forEach(function(row) { row.style.display = 'none'; });

  const totalPages = Math.max(1, Math.ceil(studentsFilteredRows.length / studentsPerPage));
  if (studentsCurrentPage > totalPages) studentsCurrentPage = totalPages;
  if (studentsCurrentPage < 1) studentsCurrentPage = 1;

  const start = (studentsCurrentPage - 1) * studentsPerPage;
  const pageRows = studentsFilteredRows.slice(start, start + studentsPerPage);
  pageRows.forEach(function(row) { row.style.display = ''; });

  renderPaginationControls(totalPages);
}

function renderPaginationControls(totalPages) {
  const container = document.getElementById('paginationControls');
  if (!container) return;

  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }

  let html = '';
  html += '<button class="page-btn" aria-label="Previous"' +
    (studentsCurrentPage === 1 ? ' disabled' : '') +
    ' onclick="goToStudentsPage(' + (studentsCurrentPage - 1) + ')">' +
    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>' +
    '</button>';

  for (let p = 1; p <= totalPages; p++) {
    html += '<button class="page-btn' + (p === studentsCurrentPage ? ' active' : '') + '" onclick="goToStudentsPage(' + p + ')">' + p + '</button>';
  }

  html += '<button class="page-btn" aria-label="Next"' +
    (studentsCurrentPage === totalPages ? ' disabled' : '') +
    ' onclick="goToStudentsPage(' + (studentsCurrentPage + 1) + ')">' +
    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>' +
    '</button>';

  container.innerHTML = html;
}

function goToStudentsPage(page) {
  const totalPages = Math.max(1, Math.ceil(studentsFilteredRows.length / studentsPerPage));
  if (page < 1 || page > totalPages) return;
  studentsCurrentPage = page;
  renderStudentsPage();
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

  applyStudentFilters();
}

document.getElementById('sortStudents').addEventListener('change', function() {
  sortStudentsTable(this.value);
});

// Run filters once on page load so pagination is active from the start
// (only 10 rows shown, Next button works) even before the user touches
// search/filters/sort.
applyStudentFilters();

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

  renderQr('viewQrThumb', student.qr);

  document.getElementById('viewModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeViewModal() {
  document.getElementById('viewModal').classList.remove('open');
  document.body.style.overflow = '';
}

// ── QR Code: render, view, print, download ──
let currentQrStudent = null;

function renderQr(containerId, tokenText) {
  const el = document.getElementById(containerId);
  el.innerHTML = '';
  if (!tokenText) {
    el.innerHTML = '<span style="font-size:0.7rem;color:#8a8078;">No QR yet</span>';
    return;
  }
  new QRCode(el, {
    text: tokenText,
    width: 220,
    height: 220,
    colorDark: '#2b2420',
    colorLight: '#ffffff'
  });
}

function openQrModal(id, justAdded) {
  const student = studentsData.find(s => s.id == id);
  if (!student) return;
  currentQrStudent = student;

  document.getElementById('qrJustAdded').style.display = justAdded ? 'inline-block' : 'none';
  document.getElementById('qrStudentName').textContent = student.first_name + ' ' + student.last_name;
  document.getElementById('qrStudentSub').textContent = 'Student No. ' + student.student_id;

  renderQr('qrModalBox', student.qr);

  document.getElementById('qrModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function openQrModalFromView() {
  closeViewModal();
  const nameParts = document.getElementById('viewName').textContent;
  const student = studentsData.find(s => (s.first_name + ' ' + s.last_name) === nameParts);
  if (student) openQrModal(student.id, false);
}
function closeQrModal() {
  document.getElementById('qrModal').classList.remove('open');
  document.body.style.overflow = '';
  currentQrStudent = null;
}

function getQrImageSrc() {
  const box = document.getElementById('qrModalBox');
  const img = box.querySelector('img');
  if (img) return img.src;
  const canvas = box.querySelector('canvas');
  return canvas ? canvas.toDataURL('image/png') : null;
}

function downloadQr() {
  const src = getQrImageSrc();
  if (!src || !currentQrStudent) return;
  const a = document.createElement('a');
  a.href = src;
  a.download = 'QR_' + currentQrStudent.student_id + '.png';
  a.click();
}

function printQr() {
  const src = getQrImageSrc();
  if (!src || !currentQrStudent) return;
  const w = window.open('', '_blank', 'width=420,height=520');
  w.document.write(`
    <html>
      <head><title>Student QR — ${currentQrStudent.student_id}</title></head>
      <body style="font-family:Arial,sans-serif;text-align:center;padding:40px;">
        <img src="${src}" style="width:260px;height:260px;" />
        <h2 style="margin:16px 0 4px;">${currentQrStudent.first_name} ${currentQrStudent.last_name}</h2>
        <p style="color:#666;margin:0;">Student No. ${currentQrStudent.student_id}</p>
        <script>window.onload = function(){ window.print(); }</` + `script>
      </body>
    </html>
  `);
  w.document.close();
}

document.getElementById('qrModal').addEventListener('click', function(e) {
  if (e.target === this) closeQrModal();
});

// ── Modal: Edit Student ──
function openEditModal(id) {
  const student = studentsData.find(s => s.id == id);
  if (!student) return;
  currentEditStudent = student;

  document.getElementById('editId').value = student.id;
  document.getElementById('editFirstName').value = student.first_name;
  document.getElementById('editLastName').value = student.last_name;
  document.getElementById('editMiddleName').value = student.middle_name || '';
  document.getElementById('editStudentIdInput').value = student.student_id;
  document.getElementById('editEmail').value = student.email;
  document.getElementById('editCourse').value = student.course;
  document.getElementById('editYear').value = student.year;
  document.getElementById('editStatus').value = student.status;

  renderQr('editQrThumb', student.qr);
  updateFsEditPreview();
  clearFieldErrors(EDIT_ERROR_IDS, EDIT_WRAP_IDS);

  document.getElementById('manageStudentsView').style.display = 'none';
  document.getElementById('editStudentView').classList.add('open');
  window.scrollTo({ top: 0, behavior: 'instant' });
}
function closeEditModal() {
  document.getElementById('editStudentView').classList.remove('open');
  document.getElementById('manageStudentsView').style.display = '';
  currentEditStudent = null;
  clearFieldErrors(EDIT_ERROR_IDS, EDIT_WRAP_IDS);
}

// ── Edit Student: live ID-card preview ──
let currentEditStudent = null;
function updateFsEditPreview() {
  const form   = document.getElementById('editStudentForm');
  const sno    = form.edit_student_id.value.trim();
  const email  = form.edit_email.value.trim();
  const first  = form.edit_first_name.value.trim();
  const middle = form.edit_middle_name.value.trim();
  const last   = form.edit_last_name.value.trim();
  const course = form.edit_course.value;
  const year   = form.edit_year.value;

  const fullName = [first, middle, last].filter(Boolean).join(' ');

  document.getElementById('editFsPreviewSno').textContent   = sno || 'Student No.';
  document.getElementById('editFsPreviewEmail').textContent = email || 'email@cvsu.edu.ph';
  document.getElementById('editFsPreviewName').textContent  = fullName || 'Full Name';
  document.getElementById('editFsPreviewCourse').textContent = course || 'Course';
  document.getElementById('editFsPreviewYear').textContent   = year || 'Year';

  const initials = ((first[0] || '') + (last[0] || '')).toUpperCase();
  document.getElementById('editFsAvatar').textContent = initials || '?';
}
function openQrModalFromEdit() {
  if (currentEditStudent) openQrModal(currentEditStudent.id, false);
}

// ── Close modals on backdrop click ──
document.getElementById('viewModal').addEventListener('click', function(e) {
  if (e.target === this) closeViewModal();
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

// ── Show the new student's QR right after they're added ──
<?php if ($new_student_qr_id): ?>
openQrModal(<?php echo (int)$new_student_qr_id; ?>, true);
<?php endif; ?>
</script>

</body>
</html>