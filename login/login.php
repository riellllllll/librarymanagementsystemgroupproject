<?php
// ============================================================
// login/login.php — CvSU Library Unified Login
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

// ── Helper: preserve the identifier field after a failed submit ──
function old_val(string $field): string {
    return htmlspecialchars(trim($_POST[$field] ?? ''), ENT_QUOTES);
}

// ── Handle Unified Login ──────────────────────────────────────
// One field ("identifier") accepts EITHER a student number OR an admin
// username. We detect which one it is, then route to the matching
// login method — no separate tabs needed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['login_type'] ?? '') === 'unified') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $login_error = 'Please enter your student number/username and password.';
    } else {
        $db   = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            $login_error = 'Database connection failed. Please contact the administrator.';
        } else {
            $user = new User($conn);

            // Digits only  → treat as a student number
            // Anything else → treat as an admin username
            if (ctype_digit($identifier)) {
                $result = $user->loginStudent($identifier, $password);
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
            } else {
                $result = $user->loginAdmin($identifier, $password);
                if ($result) {
                    $_SESSION['user_id']      = $result['id'];
                    $_SESSION['username']     = $result['username'];
                    $_SESSION['admin_name']   = $result['full_name'];
                    $_SESSION['role']         = 'admin';
                    header('Location: ../admin/dashboard.php');
                    exit;
                }
                $login_error = 'Invalid username or password.';
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

  <!-- Card -->
  <div class="card" id="mainCard">

    <!-- ══════════════════════════════════════════════════════
         UNIFIED LOGIN PANEL
         (single form — auto-detects student number vs admin username)
         ══════════════════════════════════════════════════════ -->
    <div class="panel active" id="panel-login">
      <h2 class="panel-title">Welcome to<br><em style="font-style:italic;color:var(--gold)">CvSU Library</em></h2>
      <p class="panel-sub">Sign in with your student number or admin username.</p>

      <?php if ($login_error): ?>
        <div class="php-error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($login_error) ?>
        </div>
      <?php endif; ?>

      <form id="loginForm" method="POST" action="login.php" novalidate>
        <input type="hidden" name="login_type" value="unified">

        <div class="field" id="f-identifier">
          <label id="identifierLabel">Student Number or Username <span>*</span></label>
          <div class="input-wrap">
            <span class="ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input type="text" id="identifier" name="identifier"
              placeholder="Enter your student number or username"
              autocomplete="username"
              value="<?= old_val('identifier') ?>" />
          </div>
          <div class="field-err">Please enter your student number or username.</div>
        </div>

        <div class="field" id="f-pw">
          <label>Password <span>*</span></label>
          <div class="input-wrap">
            <span class="ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input type="password" id="pw" name="password"
              placeholder="Enter your password"
              autocomplete="current-password" />
            <button type="button" class="pw-toggle" onclick="togglePw('pw',this)" aria-label="Show password">
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

        <button type="submit" class="btn-submit">Sign In</button>
      </form>
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

// ── Dynamic label: switch between "Student Number" and "Username"
//    based on whether the identifier field has digits or letters ──
(function(){
  const identifierInput = document.getElementById('identifier');
  const identifierLabel = document.getElementById('identifierLabel');
  if (!identifierInput || !identifierLabel) return;

  function updateIdentifierLabel(){
    const val = identifierInput.value.trim();
    if (val === '') {
      identifierLabel.innerHTML = 'Student Number or Username <span>*</span>';
    } else if (/^\d+$/.test(val)) {
      identifierLabel.innerHTML = 'Student Number <span>*</span>';
    } else {
      identifierLabel.innerHTML = 'Username <span>*</span>';
    }
  }

  identifierInput.addEventListener('input', updateIdentifierLabel);
  updateIdentifierLabel(); // handle prefilled value after a failed submit
})();

// ── Unified login (auto-detects student vs admin server-side) ──
document.getElementById('loginForm').addEventListener('submit', function(e){
  const idf = document.getElementById('identifier').value.trim();
  const pw  = document.getElementById('pw').value;
  let ok = true;
  ok = validate('identifier', !!idf, 'Please enter your student number or username.') && ok;
  ok = validate('pw', !!pw, 'Password cannot be empty.') && ok;
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

</body>
</html>