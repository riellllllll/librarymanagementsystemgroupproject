<?php
// ============================================================
// login/login.php — CvSU Library Login & Registration
// ============================================================
session_start();

// ── Load OOP classes ─────────────────────────────────────────
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';

// ── Guard: redirect if already logged in ─────────────────────
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] === 'admin')
        ? '../admin/dashboard.php'
        : '../student/dashboard.php';
    header('Location: ' . $redirect);
    exit;
}

// Safe defaults
$login_error = null;
$admin_error = null;
$reg_success = false;
$reg_error   = null;
$active_tab  = 'student'; // which tab to show on page load

// ── Helper: preserve form input values after failed submit ──
function old_val(string $field): string {
    return htmlspecialchars(trim($_POST[$field] ?? ''), ENT_QUOTES);
}
function old_selected(string $field, string $option): string {
    return (trim($_POST[$field] ?? '') === $option) ? 'selected' : '';
}
function old_checked(string $field): string {
    return !empty($_POST[$field]) ? 'checked' : '';
}

// ── Handle Student Login ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['login_type'] ?? '') === 'student') {
    $active_tab = 'student';
    $db         = new Database();
    $conn       = $db->getConnection();

    if (!$conn) {
        $login_error = 'Database connection failed. Please contact the administrator.';
    } else {
        $user   = new User($conn);
        $result = $user->loginStudent(
            trim($_POST['student_number'] ?? ''),
            $_POST['password'] ?? ''
        );
        if ($result) {
            $_SESSION['user_id']        = $result['id'];
            $_SESSION['student_name']   = $result['full_name'];
            $_SESSION['student_id']     = $result['student_number'];
            $_SESSION['role']           = 'student';
            $_SESSION['active_borrows'] = (int)$result['active_borrows'];
            $_SESSION['has_fines']      = (bool)$result['has_fines'];
            header('Location: ../student/dashboard.php');
            exit;
        }
        $login_error = 'Invalid student number or password.';
    }
}

// ── Handle Admin Login ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['login_type'] ?? '') === 'admin') {
    $active_tab = 'admin';
    $db         = new Database();
    $conn       = $db->getConnection();

    if (!$conn) {
        $admin_error = 'Database connection failed. Please contact the administrator.';
    } else {
        $user   = new User($conn);
        $result = $user->loginAdmin(
            trim($_POST['username'] ?? ''),
            $_POST['password'] ?? ''
        );
        if ($result) {
            $_SESSION['user_id']      = $result['id'];
            $_SESSION['username']     = $result['username'];
            $_SESSION['admin_name']   = $result['full_name'];
            $_SESSION['role']         = 'admin';
            header('Location: ../admin/dashboard.php');
            exit;
        }
        $admin_error = 'Invalid username or password.';
    }
}

// ── Handle Registration ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['login_type'] ?? '') === 'register') {
    $active_tab = 'register';

    // ── Server-side terms check (can't be bypassed by disabling JS) ──
    if (empty($_POST['terms'])) {
        $reg_error = 'You must agree to the Terms of Service to register.';
    } else {
        // ── Server-side field validation ──────────────────────────────
        $first_name     = trim($_POST['first_name']     ?? '');
        $last_name      = trim($_POST['last_name']      ?? '');
        $gender         = trim($_POST['gender']          ?? '');
        $student_number = trim($_POST['student_number']  ?? '');
        $course         = trim($_POST['course']          ?? '');
        $year_level     = trim($_POST['year_level']      ?? '');
        $email          = strtolower(trim($_POST['email'] ?? ''));
        $password       = $_POST['password']             ?? '';
        $confirm_pw     = $_POST['confirm_password']     ?? '';

        if (empty($first_name)) {
            $reg_error = 'First name is required.';
        } elseif (empty($last_name)) {
            $reg_error = 'Last name is required.';
        } elseif (empty($gender)) {
            $reg_error = 'Please select your gender.';
        } elseif (!preg_match('/^\d{3,12}$/', $student_number)) {
            $reg_error = 'Student number must be digits only (3–12 digits).';
        } elseif (empty($course)) {
            $reg_error = 'Please select your course.';
        } elseif (empty($year_level)) {
            $reg_error = 'Please select your year level.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reg_error = 'Enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $reg_error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm_pw) {
            $reg_error = 'Passwords do not match.';
        } else {
            $db   = new Database();
            $conn = $db->getConnection();

            if (!$conn) {
                $reg_error = 'Database connection failed. Please contact the administrator.';
            } else {
                $user = new User($conn);
                $data = [
                    'first_name'     => $first_name,
                    'last_name'      => $last_name,
                    'middle_name'    => trim($_POST['middle_name'] ?? ''),
                    'gender'         => $gender,
                    'student_number' => $student_number,
                    'course'         => $course,
                    'year_level'     => $year_level,
                    'email'          => $email,
                    'phone'          => trim($_POST['phone'] ?? ''),
                    'password'       => password_hash($password, PASSWORD_DEFAULT),
                ];

                if ($user->register($data)) {
                    $reg_success = true;
                } else {
                    $reg_error = 'Registration failed. Student number or email may already be in use.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>CvSU — Library Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/login.css">
</head>
<body>

<!-- Floating books bg -->
<div class="bg-books" id="bgBooks"></div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- Stage -->
<div class="stage">

  <!-- Header -->
  <div class="header">
    <div class="logo-mark">
      <div class="logo-icon">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="6" y="8" width="8" height="32" rx="1.5" fill="#c9973a"/>
          <rect x="16" y="10" width="6" height="30" rx="1.5" fill="#e8c26a"/>
          <rect x="24" y="6" width="10" height="36" rx="1.5" fill="#c9973a"/>
          <rect x="36" y="9" width="6" height="31" rx="1.5" fill="#a07830"/>
          <rect x="5" y="38" width="38" height="2.5" rx="1.25" fill="#7a6030"/>
        </svg>
      </div>
      <h1>Cv<em>SU</em></h1>
    </div>
    <p>Library Management System</p>
    <div class="gold-rule">
      <span></span>
      <i>✦</i>
      <span></span>
    </div>
  </div>

  <!-- Tab bar -->
  <div class="tab-bar" role="tablist">
    <button class="tab-btn active" onclick="switchTab('student')" id="tab-student">Student</button>
    <button class="tab-btn" onclick="switchTab('admin')" id="tab-admin">Admin</button>
    <button class="tab-btn" onclick="switchTab('register')" id="tab-register">Create Account</button>
  </div>

  <!-- Card -->
  <div class="card" id="mainCard">

    <!-- ══════════════════════════════════════════════════════
         STUDENT LOGIN PANEL
         ══════════════════════════════════════════════════════ -->
    <div class="panel active" id="panel-student">
      <h2 class="panel-title">Welcome back,<br><em style="font-style:italic;color:var(--gold)">Student</em></h2>
      <p class="panel-sub">Sign in with your student credentials to access the library portal.</p>

      <?php if ($login_error): ?>
        <div class="php-error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($login_error) ?>
        </div>
      <?php endif; ?>

      <!-- method="POST" + action + hidden login_type added -->
      <!-- name attributes added to all inputs for PHP $_POST -->
      <!-- JS e.preventDefault() kept for now; remove it when DB is ready -->
      <form id="studentForm" method="POST" action="login.php" novalidate>
        <input type="hidden" name="login_type" value="student">

        <div class="field" id="f-sno">
          <label>Student Number <span>*</span></label>
          <div class="input-wrap">
            <span class="ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <input type="text" id="sno" name="student_number"
              placeholder="e.g. 101"
              autocomplete="username"
              inputmode="numeric"
              maxlength="12"
              oninput="this.value=this.value.replace(/\D/g,'')" />
          </div>
          <div class="field-err">Please enter your student number.</div>
        </div>

        <div class="field" id="f-spw">
          <label>Password <span>*</span></label>
          <div class="input-wrap">
            <span class="ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input type="password" id="spw" name="password"
              placeholder="Enter your password"
              autocomplete="current-password" />
            <button type="button" class="pw-toggle" onclick="togglePw('spw',this)" aria-label="Show password">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="field-err">Password cannot be empty.</div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
          <label style="display:flex;align-items:center;gap:7px;font-size:0.8rem;color:#7a6e5e;cursor:pointer;">
            <input type="checkbox" name="remember" value="1" style="accent-color:var(--gold);width:14px;height:14px;"> Remember me
          </label>
          <a href="#" style="font-size:0.8rem;color:var(--gold);text-decoration:none;" onclick="openForgotModal();return false;">Forgot password?</a>
        </div>

        <button type="submit" class="btn-submit">Sign In to Library</button>
      </form>

      <div class="link-row">
        Don't have an account? <a href="#" onclick="switchTab('register');return false;">Create one here</a>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         ADMIN LOGIN PANEL
         ══════════════════════════════════════════════════════ -->
    <div class="panel" id="panel-admin">
      <h2 class="panel-title">Admin <em style="font-style:italic;color:var(--rust)">Portal</em></h2>
      <p class="panel-sub">Restricted access. Authorized library staff only.</p>

      <div style="background:#fdf5ec;border:1px solid #e8c26a;border-radius:10px;padding:11px 14px;margin-bottom:22px;display:flex;gap:10px;align-items:center;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9973a" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="font-size:0.78rem;color:#8a6a30;line-height:1.4;">This portal is for authorized library administrators and staff only.</span>
      </div>

      <?php if ($admin_error): ?>
        <div class="php-error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($admin_error) ?>
        </div>
      <?php endif; ?>

      <!-- method="POST" + action + hidden login_type added -->
      <form id="adminForm" method="POST" action="login.php" novalidate>
        <input type="hidden" name="login_type" value="admin">

        <div class="field" id="f-aun">
          <label>Username <span>*</span></label>
          <div class="input-wrap">
            <span class="ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input type="text" id="aun" name="username"
              placeholder="Admin username"
              autocomplete="username" />
          </div>
          <div class="field-err">Please enter your username.</div>
        </div>

        <div class="field" id="f-apw">
          <label>Password <span>*</span></label>
          <div class="input-wrap">
            <span class="ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input type="password" id="apw" name="password"
              placeholder="Enter your password"
              autocomplete="current-password" />
            <button type="button" class="pw-toggle" onclick="togglePw('apw',this)" aria-label="Show password">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="field-err">Password cannot be empty.</div>
        </div>

        <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#8b3a2a,#c06040);">Sign In as Admin</button>
      </form>
    </div>

    <!-- ══════════════════════════════════════════════════════
         REGISTER PANEL
         ══════════════════════════════════════════════════════ -->
    <div class="panel" id="panel-register">
      <!-- Success state — shown by JS (or by PHP when $reg_success is true) -->
      <div class="success-overlay<?= $reg_success ? ' show' : '' ?>" id="regSuccess">
        <div class="check-circle">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h2>Account Created!</h2>
        <p>Your student account has been successfully registered. You can now sign in to access the library system.</p>
        <button class="btn-submit" onclick="switchTab('student')" style="max-width:200px;">Go to Sign In</button>
      </div>

      <!-- Form state — hide if registration was successful -->
      <div id="regForm"<?= $reg_success ? ' style="display:none;"' : '' ?>>
        <h2 class="panel-title">Create <em style="font-style:italic;color:var(--gold)">Account</em></h2>
        <p class="panel-sub">Register as a student to borrow books, track due dates, and more.</p>

        <?php if ($reg_error): ?>
          <div class="php-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($reg_error) ?>
          </div>
        <?php endif; ?>

        <!-- method="POST" + action + hidden login_type added -->
        <!-- All inputs now have name attributes for PHP $_POST -->
        <form id="registerForm" method="POST" action="login.php" novalidate>
          <input type="hidden" name="login_type" value="register">

          <div class="section-head">Personal Information</div>

          <div class="field-grid">
            <div class="field" id="f-rfn">
              <label>First Name <span>*</span></label>
              <div class="input-wrap">
                <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <input type="text" id="rfn" name="first_name" placeholder="Juan" value="<?= old_val('first_name') ?>" />
              </div>
              <div class="field-err">Required.</div>
            </div>
            <div class="field" id="f-rln">
              <label>Last Name <span>*</span></label>
              <div class="input-wrap">
                <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <input type="text" id="rln" name="last_name" placeholder="Dela Cruz" value="<?= old_val('last_name') ?>" />
              </div>
              <div class="field-err">Required.</div>
            </div>
          </div>

          <div class="field" id="f-rmn">
            <label>Middle Name</label>
            <div class="input-wrap">
              <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <input type="text" id="rmn" name="middle_name" placeholder="Santos (optional)" value="<?= old_val('middle_name') ?>" />
            </div>
          </div>

          <div class="field-grid">
            <div class="field" id="f-rgender">
              <label>Gender <span>*</span></label>
              <div class="input-wrap">
                <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M17 16h-2v2M7 16h2v2"/></svg></span>
                <select id="rgender" name="gender">
                  <option value="">Select…</option>
                  <option <?= old_selected('gender','Male') ?>>Male</option>
                  <option <?= old_selected('gender','Female') ?>>Female</option>
                  <option <?= old_selected('gender','Prefer not to say') ?>>Prefer not to say</option>
                </select>
                <span class="select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
              </div>
              <div class="field-err">Required.</div>
            </div>
          </div>

          <div class="section-head">Academic Information</div>

          <div class="field" id="f-rsno">
            <label>Student Number <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></span>
              <input type="text" id="rsno" name="student_number"
                placeholder="e.g. 101"
                inputmode="numeric"
                maxlength="12"
                value="<?= old_val('student_number') ?>"
                oninput="this.value=this.value.replace(/\D/g,'')" />
            </div>
            <div class="field-hint">Numbers only — no dashes</div>
            <div class="field-err">Please enter a valid student number.</div>
          </div>

          <div class="field-grid">
            <div class="field" id="f-rcourse">
              <label>Course / Program <span>*</span></label>
              <div class="input-wrap">
                <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></span>
                <select id="rcourse" name="course">
                  <option value="">Select…</option>
                  <option <?= old_selected('course','BS Computer Science') ?>>BS Computer Science</option>
                  <option <?= old_selected('course','BS Information Technology') ?>>BS Information Technology</option>
                  <option <?= old_selected('course','BS Education') ?>>BS Education</option>
                  <option <?= old_selected('course','BS Nursing') ?>>BS Nursing</option>
                  <option <?= old_selected('course','BS Engineering') ?>>BS Engineering</option>
                  <option <?= old_selected('course','BS Business Administration') ?>>BS Business Administration</option>
                  <option <?= old_selected('course','BS Accountancy') ?>>BS Accountancy</option>
                  <option <?= old_selected('course','AB Communication') ?>>AB Communication</option>
                </select>
                <span class="select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
              </div>
              <div class="field-err">Required.</div>
            </div>
            <div class="field" id="f-ryear">
              <label>Year Level <span>*</span></label>
              <div class="input-wrap">
                <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg></span>
                <select id="ryear" name="year_level">
                  <option value="">Select…</option>
                  <option <?= old_selected('year_level','1st Year') ?>>1st Year</option>
                  <option <?= old_selected('year_level','2nd Year') ?>>2nd Year</option>
                  <option <?= old_selected('year_level','3rd Year') ?>>3rd Year</option>
                  <option <?= old_selected('year_level','4th Year') ?>>4th Year</option>
                </select>
                <span class="select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
              </div>
              <div class="field-err">Required.</div>
            </div>
          </div>

          <div class="section-head">Contact &amp; Account</div>

          <div class="field" id="f-remail">
            <label>Email Address <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
              <input type="email" id="remail" name="email" placeholder="juandelacruz@school.edu.ph" value="<?= old_val('email') ?>" />
            </div>
            <div class="field-err">Enter a valid email address.</div>
          </div>

          <div class="field" id="f-rphone">
            <label>Contact Number</label>
            <div class="input-wrap">
              <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 9.5a19.79 19.79 0 0 1-3-8.59A2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
              <input type="tel" id="rphone" name="phone" placeholder="09XX-XXX-XXXX" value="<?= old_val('phone') ?>" />
            </div>
          </div>

          <div class="field" id="f-rpw">
            <label>Password <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <input type="password" id="rpw" name="password"
                placeholder="Create a strong password"
                autocomplete="new-password"
                oninput="checkStrength(this.value)" />
              <button type="button" class="pw-toggle" onclick="togglePw('rpw',this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="strength-wrap" id="strengthWrap" style="display:none;">
              <div class="strength-bars">
                <div class="strength-bar" id="sb1"></div>
                <div class="strength-bar" id="sb2"></div>
                <div class="strength-bar" id="sb3"></div>
                <div class="strength-bar" id="sb4"></div>
              </div>
              <span class="strength-label" id="strengthLabel"></span>
            </div>
            <div class="field-err">Password must be at least 8 characters.</div>
          </div>

          <div class="field" id="f-rcpw">
            <label>Confirm Password <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <input type="password" id="rcpw" name="confirm_password"
                placeholder="Re-enter password"
                autocomplete="new-password" />
              <button type="button" class="pw-toggle" onclick="togglePw('rcpw',this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="field-err">Passwords do not match.</div>
          </div>

          <div class="field">
            <div class="check-field">
              <input type="checkbox" id="rterms" name="terms" value="1" <?= old_checked('terms') ?> />
              <label for="rterms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a> of the Library Management System.</label>
            </div>
          </div>

          <button type="submit" class="btn-submit" style="margin-top:14px;">Create My Account</button>
        </form>

        <div class="link-row">
          Already have an account? <a href="#" onclick="switchTab('student');return false;">Sign in here</a>
        </div>
      </div>
    </div>

  </div><!-- /card -->
</div><!-- /stage -->

<script>
// ── Floating books ──
(function(){
  const container = document.getElementById('bgBooks');
  const colors = ['#c9973a','#8b3a2a','#4a6050','#6b5020','#3a4a5a'];
  for(let i=0;i<18;i++){
    const el = document.createElement('div');
    el.className = 'book-spine';
    const w = 10+Math.random()*14, h = 60+Math.random()*80;
    const x = Math.random()*100, dur = 15+Math.random()*20, delay = -Math.random()*20;
    const r = (Math.random()-0.5)*15;
    el.style.cssText = `
      width:${w}px;height:${h}px;left:${x}%;
      background:${colors[Math.floor(Math.random()*colors.length)]};
      --r:${r}deg;
      animation-duration:${dur}s;
      animation-delay:${delay}s;
    `;
    container.appendChild(el);
  }
})();

// ── Tab switching ──
function switchTab(name){
  ['student','admin','register'].forEach(t=>{
    document.getElementById('panel-'+t).classList.toggle('active', t===name);
    document.getElementById('tab-'+t).classList.toggle('active', t===name);
  });
}

// ── Toggle password visibility ──
function togglePw(id, btn){
  const input = document.getElementById(id);
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.innerHTML = show
    ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
    : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
}

// ── Toast ──
let toastTimer;
function showToast(msg, type=''){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show' + (type ? ' '+type : '');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=>{ t.classList.remove('show'); }, 3200);
}

// ── Password strength ──
function checkStrength(pw){
  const wrap = document.getElementById('strengthWrap');
  const label = document.getElementById('strengthLabel');
  if(!pw){ wrap.style.display='none'; return; }
  wrap.style.display='block';
  let score = 0;
  if(pw.length >= 8) score++;
  if(/[A-Z]/.test(pw)) score++;
  if(/[0-9]/.test(pw)) score++;
  if(/[^A-Za-z0-9]/.test(pw)) score++;
  const colors = ['#e05050','#e09030','#c9973a','#4a9060'];
  const labels = ['Weak','Fair','Good','Strong'];
  for(let i=1;i<=4;i++){
    const bar = document.getElementById('sb'+i);
    bar.style.background = i<=score ? colors[score-1] : '#ddd5c5';
  }
  label.textContent = labels[score-1] || '';
  label.style.color = colors[score-1] || '#9a8e7e';
}

// ── Validate field ──
function validate(fieldId, condition, errMsg){
  const wrap = document.getElementById('f-'+fieldId);
  if(!wrap) return condition;
  const err = wrap.querySelector('.field-err');
  if(!condition){
    wrap.classList.add('has-error');
    if(err && errMsg) err.textContent = errMsg;
    return false;
  }
  wrap.classList.remove('has-error');
  return true;
}

// ── Student login ──
document.getElementById('studentForm').addEventListener('submit', function(e){
  const sno = document.getElementById('sno').value.trim();
  const spw = document.getElementById('spw').value;
  let ok = true;
  ok = validate('sno', !!sno, 'Please enter your student number.') && ok;
  ok = validate('spw', !!spw, 'Password cannot be empty.') && ok;
  if(!ok){ e.preventDefault(); return; }
  const btn = this.querySelector('.btn-submit');
  btn.classList.add('loading');
});

// ── Admin login ──
document.getElementById('adminForm').addEventListener('submit', function(e){
  const aun = document.getElementById('aun').value.trim();
  const apw = document.getElementById('apw').value;
  let ok = true;
  ok = validate('aun', !!aun, 'Please enter your username.') && ok;
  ok = validate('apw', !!apw, 'Password cannot be empty.') && ok;
  if(!ok){ e.preventDefault(); return; }
  const btn = this.querySelector('.btn-submit');
  btn.classList.add('loading');
});

// ── Forgot Password Modal ──
let fpMode = 'email';

function openForgotModal(){
  document.getElementById('forgotModal').classList.add('open');
  backToStep1();
}
function closeForgotModal(){
  document.getElementById('forgotModal').classList.remove('open');
}
function switchLookup(mode){
  fpMode = mode;
  document.getElementById('lookup-email-wrap').style.display = mode==='email' ? 'block' : 'none';
  document.getElementById('lookup-sno-wrap').style.display   = mode==='sno'   ? 'block' : 'none';
  document.getElementById('lt-email').classList.toggle('active', mode==='email');
  document.getElementById('lt-sno').classList.toggle('active',   mode==='sno');
  ['f-fpemail','f-fpsno'].forEach(id=>{
    const el = document.getElementById(id);
    if(el) el.classList.remove('has-error');
  });
}
function backToStep1(){
  document.getElementById('fp-step1').classList.add('active');
  document.getElementById('fp-step2').classList.remove('active');
  document.getElementById('fpemail').value = '';
  document.getElementById('fpsno').value = '';
  switchLookup('email');
}
function submitForgot(){
  let ok = true, sentTo = '';
  if(fpMode === 'email'){
    const val = document.getElementById('fpemail').value.trim();
    ok = validate('fpemail', /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val), 'Enter a valid email address.');
    sentTo = val;
  } else {
    const val = document.getElementById('fpsno').value.trim();
    ok = validate('fpsno', /^\d{3,12}$/.test(val), 'Please enter a valid student number.');
    sentTo = val;
  }
  if(!ok) return;
  const btn = document.getElementById('fpSendBtn');
  btn.classList.add('loading');
  setTimeout(()=>{
    btn.classList.remove('loading');
    document.getElementById('fpSentTo').textContent = sentTo;
    document.getElementById('fp-step1').classList.remove('active');
    document.getElementById('fp-step2').classList.add('active');
  }, 1500);
}
document.getElementById('forgotModal').addEventListener('click', function(e){
  if(e.target === this) closeForgotModal();
});

// ── Register ──
document.getElementById('registerForm').addEventListener('submit', function(e){
  const get = id => document.getElementById(id).value.trim();
  let ok = true;
  ok = validate('rfn',  !!get('rfn'), 'First name is required.') && ok;
  ok = validate('rln',  !!get('rln'), 'Last name is required.') && ok;
  ok = validate('rgender', !!get('rgender'), 'Please select your gender.') && ok;
  ok = validate('rsno', /^\d{3,12}$/.test(get('rsno')), 'Student number must be digits only (3-12 digits).') && ok;
  ok = validate('rcourse', !!get('rcourse'), 'Please select your course.') && ok;
  ok = validate('ryear',   !!get('ryear'), 'Please select your year level.') && ok;
  ok = validate('remail',  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(get('remail')), 'Enter a valid email address.') && ok;
  const pw  = document.getElementById('rpw').value;
  const cpw = document.getElementById('rcpw').value;
  ok = validate('rpw',  pw.length >= 8, 'Password must be at least 8 characters.') && ok;
  ok = validate('rcpw', pw === cpw, 'Passwords do not match.') && ok;
  if(!document.getElementById('rterms').checked){
    showToast('Please agree to the Terms of Service.','error');
    ok = false;
  }
  if(!ok){ e.preventDefault(); return; }
  const btn = this.querySelector('.btn-submit');
  btn.classList.add('loading');
});
</script>

<!-- ── Forgot Password Modal ── -->
<div class="modal-backdrop" id="forgotModal">
  <div class="modal">
    <div class="modal-top"></div>
    <button class="modal-close" onclick="closeForgotModal()" aria-label="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="modal-body">
      <div class="modal-step active" id="fp-step1">
        <div class="modal-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c9973a" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16" r="1" fill="#c9973a" stroke="none"/></svg>
        </div>
        <h3 class="modal-title">Forgot Password?</h3>
        <p class="modal-desc">Enter your registered email address or student number and we'll send you a reset link.</p>
        <div class="lookup-toggle">
          <button class="lookup-btn active" id="lt-email" onclick="switchLookup('email')">Email Address</button>
          <button class="lookup-btn" id="lt-sno" onclick="switchLookup('sno')">Student Number</button>
        </div>
        <div id="lookup-email-wrap">
          <div class="field" id="f-fpemail">
            <label>Email Address <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
              <input type="email" id="fpemail" placeholder="your@email.com" />
            </div>
            <div class="field-err">Enter a valid email address.</div>
          </div>
        </div>
        <div id="lookup-sno-wrap" style="display:none;">
          <div class="field" id="f-fpsno">
            <label>Student Number <span>*</span></label>
            <div class="input-wrap">
              <span class="ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></span>
              <input type="text" id="fpsno" placeholder="e.g. 101" inputmode="numeric" maxlength="12" oninput="this.value=this.value.replace(/\D/g,'')" />
            </div>
            <div class="field-err">Please enter your student number.</div>
          </div>
        </div>
        <button class="btn-submit" id="fpSendBtn" onclick="submitForgot()">Send Reset Link</button>
        <p style="text-align:center;font-size:0.78rem;color:#b0a898;margin-top:14px;">
          Remember your password? <a href="#" style="color:var(--gold);" onclick="closeForgotModal();return false;">Back to sign in</a>
        </p>
      </div>
      <div class="modal-step" id="fp-step2">
        <div class="sent-circle">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h3 class="modal-sent-title">Reset Link Sent!</h3>
        <p class="modal-sent-desc">
          A password reset link has been sent to<br>
          <span class="highlight-val" id="fpSentTo">—</span><br><br>
          Please check your inbox and follow the instructions. The link expires in <strong>30 minutes</strong>.
        </p>
        <button class="btn-submit" onclick="closeForgotModal()">Done</button>
        <p style="text-align:center;font-size:0.78rem;color:#b0a898;margin-top:12px;">
          Didn't receive it? <a href="#" style="color:var(--gold);" onclick="backToStep1();return false;">Try again</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php if ($active_tab !== 'student'): ?>
<script>
  document.addEventListener('DOMContentLoaded', function(){
    switchTab('<?= $active_tab ?>');
  });
</script>
<?php endif; ?>

</body>
</html>