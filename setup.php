<?php
// ============================================================
// setup.php — Run this ONCE in your browser to create the admin account
// URL: http://localhost/YOUR_PROJECT_FOLDER/setup.php
// DELETE this file after running it!
// ============================================================

// ── Step 1: Load DB config ───────────────────────────────────
require_once 'config/Database.php';

$db   = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die('<p style="color:red;font-family:sans-serif;">❌ Database connection failed. Check config/Database.php</p>');
}

// ── Step 2: Check if admin already exists ───────────────────
$check = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo '<p style="color:orange;font-family:sans-serif;">⚠️ Admin account already exists. Setup not needed.</p>';
    echo '<p><a href="login/login.php">Go to Login</a></p>';
    exit;
}
$check->close();

// ── Step 3: Create admin account ────────────────────────────
$username   = 'admin';
$password   = password_hash('Admin@123', PASSWORD_DEFAULT);
$first_name = 'Library';
$last_name  = 'Admin';
$full_name  = 'Library Admin';
$email      = 'admin@cvsu.edu.ph';
$role       = 'admin';

$stmt = $conn->prepare(
    "INSERT INTO users (role, username, first_name, last_name, full_name, email, password)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssssss', $role, $username, $first_name, $last_name, $full_name, $email, $password);

if ($stmt->execute()) {
    // ── Step 4: Create sample students (IDs 101, 102, 103) ──
    $students = [
        ['101', 'Juan',  'Dela Cruz', 'Juan Dela Cruz',  'juan.delacruz@cvsu.edu.ph', 'BSIT', '3rd Year'],
        ['102', 'Maria', 'Santos',    'Maria Santos',     'maria.santos@cvsu.edu.ph',  'BSCS', '2nd Year'],
        ['103', 'Pedro', 'Reyes',     'Pedro Reyes',      'pedro.reyes@cvsu.edu.ph',   'BSIT', '4th Year'],
    ];
    $spwd = password_hash('Student@123', PASSWORD_DEFAULT);
    $srole = 'student';
    $sstmt = $conn->prepare(
        "INSERT INTO users (role, student_number, first_name, last_name, full_name, email, password, course, year_level)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($students as $s) {
        $sstmt->bind_param('sssssssss', $srole, $s[0], $s[1], $s[2], $s[3], $s[4], $spwd, $s[5], $s[6]);
        @$sstmt->execute(); // ignore duplicates
    }
    $sstmt->close();

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>
      body { font-family: sans-serif; max-width: 500px; margin: 60px auto; padding: 20px; }
      .box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px; padding: 24px; }
      h2 { color: #166534; } p { color: #333; } code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
      a { color: #16a34a; } .warn { color: #b91c1c; font-weight: bold; }
      hr { border:none; border-top:1px solid #d1d5db; margin:16px 0; }
    </style></head><body>
    <div class="box">
      <h2>✅ Accounts Created!</h2>
      <p><strong>ADMIN LOGIN</strong></p>
      <p>Username: <code>admin</code> &nbsp; Password: <code>Admin@123</code></p>
      <hr>
      <p><strong>SAMPLE STUDENT LOGINS</strong> (password: <code>Student@123</code>)</p>
      <p>Student No: <code>101</code> — Juan Dela Cruz</p>
      <p>Student No: <code>102</code> — Maria Santos</p>
      <p>Student No: <code>103</code> — Pedro Reyes</p>
      <hr>
      <p class="warn">⚠️ DELETE this file (setup.php) now for security!</p>
      <p><a href="login/login.php">→ Go to Login Page</a></p>
    </div>
    </body></html>';
} else {
    echo '<p style="color:red;font-family:sans-serif;">❌ Failed to create admin: ' . htmlspecialchars($stmt->error) . '</p>';
}

$stmt->close();
$conn->close();
?>
