<?php
// ============================================================
// admin/attendance.php — Student library attendance check-in
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

$conn = get_db();

// The table is created automatically so existing installations can use the
// attendance screen without a separate database migration step.
$conn->query("CREATE TABLE IF NOT EXISTS student_attendance (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    time_in DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT UNSIGNED DEFAULT NULL,
    entry_method VARCHAR(20) NOT NULL DEFAULT 'student_id',
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance_student_date (student_id, attendance_date),
    KEY idx_attendance_time_in (time_in),
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_admin FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$flash = $_SESSION['attendance_flash'] ?? null;
unset($_SESSION['attendance_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['attendance_code'] ?? '');
    $student = false;
    $method = 'student_id';

    if ($code === '') {
        $_SESSION['attendance_flash'] = ['type' => 'error', 'message' => 'Scan a QR code or enter a student ID.'];
    } else {
        // QR codes use a random 32-byte hexadecimal token. A handheld QR
        // scanner simply types this value into the field then presses Enter.
        if (preg_match('/^[a-f0-9]{32}$/i', $code)) {
            $method = 'qr';
            $stmt = $conn->prepare("SELECT id, student_number, full_name, email, course, year_level, status FROM users WHERE qr_token = ? AND role = 'student' LIMIT 1");
            $stmt->bind_param('s', $code);
        } else {
            $stmt = $conn->prepare("SELECT id, student_number, full_name, email, course, year_level, status FROM users WHERE student_number = ? AND role = 'student' LIMIT 1");
            $stmt->bind_param('s', $code);
        }
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student) {
            $_SESSION['attendance_flash'] = ['type' => 'error', 'message' => 'Student not found. Check the student ID or QR code.'];
        } elseif (($student['status'] ?? '') !== 'active') {
            $_SESSION['attendance_flash'] = ['type' => 'error', 'message' => $student['full_name'] . ' has an inactive account and cannot check in.'];
        } else {
            $admin_id = (int)$_SESSION['user_id'];
            $insert = $conn->prepare("INSERT INTO student_attendance (student_id, attendance_date, recorded_by, entry_method) VALUES (?, CURDATE(), ?, ?) ON DUPLICATE KEY UPDATE time_in = time_in");
            $insert->bind_param('iis', $student['id'], $admin_id, $method);
            $insert->execute();
            $new_entry = $insert->affected_rows === 1;
            $insert->close();

            $time_stmt = $conn->prepare("SELECT time_in FROM student_attendance WHERE student_id = ? AND attendance_date = CURDATE() LIMIT 1");
            $time_stmt->bind_param('i', $student['id']);
            $time_stmt->execute();
            $time_in = $time_stmt->get_result()->fetch_assoc()['time_in'] ?? '';
            $time_stmt->close();

            $_SESSION['attendance_flash'] = [
                'type' => $new_entry ? 'success' : 'warning',
                'message' => $new_entry ? $student['full_name'] . ' checked in successfully.' : $student['full_name'] . ' is already checked in today.',
                'student' => $student,
                'time_in' => $time_in,
            ];
        }
    }
    header('Location: attendance.php');
    exit;
}

$today = date('Y-m-d');
$attendance = [];
$result = $conn->query("SELECT a.time_in, a.entry_method, u.student_number, u.full_name, u.email, u.course, u.year_level
                        FROM student_attendance a JOIN users u ON u.id = a.student_id
                        WHERE a.attendance_date = CURDATE() ORDER BY a.time_in DESC");
if ($result) $attendance = $result->fetch_all(MYSQLI_ASSOC);

$pending_count = pending_request_count();
$archive_badge = archived_book_count();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Attendance — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .attendance-wrap { max-width:1120px; margin:0 auto; }
    .attendance-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e5e7eb); border-radius:16px; padding:24px; box-shadow:0 3px 14px rgba(15,22,35,.05); }
    .attendance-entry { display:flex; gap:12px; align-items:end; margin-top:20px; }
    .attendance-entry label { display:block; font-size:.78rem; font-weight:700; color:var(--muted,#64748b); margin-bottom:7px; }
    .attendance-entry input { width:100%; box-sizing:border-box; border:1px solid var(--border,#d8dee8); border-radius:10px; padding:13px 14px; font:500 .95rem Inter,sans-serif; }
    .attendance-entry input:focus { outline:none; border-color:var(--gold,#c9973a); box-shadow:0 0 0 3px rgba(201,151,58,.12); }
    .attendance-submit { border:0; border-radius:10px; padding:13px 20px; background:var(--gold-dk,#8b6a26); color:#fff; font:600 .9rem Inter,sans-serif; cursor:pointer; white-space:nowrap; }
    .attendance-help { margin:12px 0 0; color:var(--muted,#64748b); font-size:.8rem; }
    .attendance-alert { border-radius:12px; padding:16px; margin:20px 0; }
    .attendance-alert.success { background:#eef8f0; color:#27613a; border:1px solid #cce8d1; }
    .attendance-alert.warning { background:#fff8e8; color:#805b12; border:1px solid #f2dc9e; }
    .attendance-alert.error { background:#fff0f0; color:#9f3333; border:1px solid #f1c8c8; }
    .attendance-student { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:13px; font-size:.84rem; }
    .attendance-student span { display:block; color:inherit; opacity:.78; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px; }
    .attendance-table { width:100%; border-collapse:collapse; margin-top:18px; }
    .attendance-table th,.attendance-table td { padding:14px 12px; text-align:left; border-bottom:1px solid var(--border,#e5e7eb); font-size:.84rem; }
    .attendance-table th { color:var(--muted,#64748b); font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; }
    .attendance-count { display:inline-flex; padding:5px 10px; border-radius:20px; background:#fdf8ee; color:var(--gold-dk,#8b6a26); font-size:.78rem; font-weight:700; }
    @media(max-width:720px){ .attendance-entry,.attendance-student{display:block}.attendance-submit{margin-top:10px;width:100%}.attendance-table{display:block;overflow-x:auto} }
  </style>
  <!-- Load the same shared styles, in the same order as the other admin pages. -->
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <style>
    .attendance-camera-btn { border:1px solid var(--gold,#c9973a); border-radius:10px; padding:13px 16px; background:#fff; color:var(--gold-dk,#8b6a26); font:600 .9rem Inter,sans-serif; cursor:pointer; white-space:nowrap; }
    .attendance-camera-modal { display:none; position:fixed; inset:0; z-index:2000; padding:20px; align-items:center; justify-content:center; background:rgba(11,18,31,.68); }
    .attendance-camera-modal.open { display:flex; }
    .attendance-camera-dialog { position:relative; width:min(560px,100%); overflow:hidden; border-radius:16px; background:#fff; padding:20px; box-shadow:0 20px 60px rgba(0,0,0,.32); }
    .attendance-camera-dialog h3 { margin:0; color:var(--ink); }.attendance-camera-dialog p { margin:7px 0 15px; color:var(--muted); font-size:.83rem; }
    #qrCameraReader { width:100%; overflow:hidden; border-radius:12px; background:#101827; }.attendance-camera-status { min-height:20px; margin:12px 0 0; color:var(--muted); font-size:.82rem; }.attendance-camera-close { position:absolute; z-index:5; top:10px; right:13px; border:0; background:transparent; color:#64748b; font-size:1.55rem; cursor:pointer; }.attendance-camera-cancel { margin-top:14px; padding:9px 14px; border:1px solid #d9dee7; border-radius:8px; background:#fff; color:#465267; font:600 .82rem Inter,sans-serif; cursor:pointer; }
  </style>
</head>
<body>
<?php include __DIR__ . '/sideBar.php'; ?>
<div class="main-wrapper">
  <header class="topbar"><h1 class="topbar-title">Student Attendance</h1><div class="topbar-spacer"></div></header>
  <main class="page-content"><div class="attendance-wrap">
    <div class="page-header"><h1>Library Attendance</h1><p>Scan a student's QR code or enter their student ID to record today's check-in.</p><div class="gold-rule"><span></span><i>✦</i><span></span></div></div>
    <section class="attendance-card">
      <h2 style="margin:0;color:var(--ink,#172033);font-size:1.15rem;">Check in a student</h2>
      <form method="POST" class="attendance-entry" id="attendanceForm" autocomplete="off">
        <div style="flex:1"><label for="attendance_code">QR code or Student ID</label><input id="attendance_code" name="attendance_code" placeholder="Scan QR code or type student ID" autofocus required></div>
        <button class="attendance-camera-btn" type="button" onclick="openCameraScanner()">Use Camera</button>
        <button class="attendance-submit" type="submit">Record Check-in</button>
      </form>
      <p class="attendance-help">Use the camera button for a phone/laptop camera, or scan with a USB QR scanner—it will type into this field like a keyboard.</p>
    </section>

    <?php if ($flash): ?>
      <section class="attendance-alert <?= htmlspecialchars($flash['type']) ?>">
        <strong><?= htmlspecialchars($flash['message']) ?></strong>
        <?php if (!empty($flash['student'])): $s = $flash['student']; ?>
          <div class="attendance-student">
            <div><span>Student ID</span><?= htmlspecialchars($s['student_number']) ?></div>
            <div><span>Course / Year</span><?= htmlspecialchars(trim(($s['course'] ?? '') . ' ' . ($s['year_level'] ?? ''))) ?></div>
            <div><span>Time in</span><?= !empty($flash['time_in']) ? date('g:i A', strtotime($flash['time_in'])) : '—' ?></div>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="attendance-card" style="margin-top:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;"><h2 style="margin:0;color:var(--ink,#172033);font-size:1.15rem;">Today's attendance</h2><span class="attendance-count"><?= count($attendance) ?> checked in</span></div>
      <table class="attendance-table"><thead><tr><th>Student</th><th>Student ID</th><th>Course / Year</th><th>Time in</th><th>Method</th></tr></thead>
      <tbody><?php if ($attendance): foreach ($attendance as $row): ?><tr><td><strong><?= htmlspecialchars($row['full_name']) ?></strong><br><span style="color:var(--muted,#64748b);font-size:.76rem;"><?= htmlspecialchars($row['email']) ?></span></td><td><?= htmlspecialchars($row['student_number']) ?></td><td><?= htmlspecialchars(trim(($row['course'] ?? '') . ' ' . ($row['year_level'] ?? ''))) ?></td><td><?= date('g:i A', strtotime($row['time_in'])) ?></td><td><?= $row['entry_method'] === 'qr' ? 'QR Code' : 'Student ID' ?></td></tr><?php endforeach; else: ?><tr><td colspan="5" style="text-align:center;color:var(--muted,#64748b);padding:28px;">No students have checked in today.</td></tr><?php endif; ?></tbody></table>
    </section>
  </div></main>
</div>
<div class="attendance-camera-modal" id="cameraModal" role="dialog" aria-modal="true" aria-labelledby="cameraTitle">
  <div class="attendance-camera-dialog">
    <button type="button" class="attendance-camera-close" onclick="closeCameraScanner()" aria-label="Close camera">&times;</button>
    <h3 id="cameraTitle">Scan student QR code</h3>
    <p>Point the camera at the student's QR code. It will check in automatically after a successful scan.</p>
    <div id="qrCameraReader"></div>
    <div class="attendance-camera-status" id="cameraStatus">Opening camera…</div>
    <button type="button" class="attendance-camera-cancel" onclick="closeCameraScanner()">Close camera</button>
  </div>
</div>
<script>
  let qrScanner = null;
  let cameraOpen = false;

  async function openCameraScanner() {
    const modal = document.getElementById('cameraModal');
    const status = document.getElementById('cameraStatus');
    if (!window.Html5Qrcode) {
      status.textContent = 'The camera scanner is still loading. Check your internet connection, wait a moment, then try again.';
      modal.classList.add('open');
      return;
    }
    try {
      modal.classList.add('open');
      status.textContent = 'Opening camera…';
      qrScanner = new Html5Qrcode('qrCameraReader');
      const cameras = await Html5Qrcode.getCameras();
      if (!cameras || cameras.length === 0) {
        throw new DOMException('No camera device was found.', 'NotFoundError');
      }
      // Use a real detected device ID. This works with USB webcams as well as
      // phone cameras, unlike asking only for a rear-facing camera.
      const preferredCamera = cameras.find(camera => /back|rear|environment/i.test(camera.label)) || cameras[0];
      await qrScanner.start(
        preferredCamera.id,
        { fps: 10, qrbox: { width: 250, height: 250 } },
        decodedText => {
          document.getElementById('attendance_code').value = decodedText;
          document.getElementById('cameraStatus').textContent = 'QR code found. Checking in…';
          closeCameraScanner(true);
        },
        () => {}
      );
      cameraOpen = true;
      status.textContent = 'Camera is ready. Point it at the student QR code.';
    } catch (error) {
      if (error && error.name === 'NotAllowedError') {
        status.textContent = 'Camera permission is blocked. Click the camera icon in the browser address bar, choose Allow, then try again.';
      } else if (error && error.name === 'NotFoundError') {
        status.textContent = 'No camera was found. Connect or enable a camera, then try again.';
      } else {
        status.textContent = 'Camera could not open: ' + (error && error.message ? error.message : 'unknown camera error') + '. Close apps using the camera, then try again.';
      }
    }
  }

  async function closeCameraScanner(submitForm = false) {
    document.getElementById('cameraModal').classList.remove('open');
    const scannerToClose = qrScanner;
    qrScanner = null;
    const wasCameraOpen = cameraOpen;
    cameraOpen = false;
    if (scannerToClose && wasCameraOpen) await scannerToClose.stop().catch(() => {});
    if (scannerToClose) await scannerToClose.clear().catch(() => {});
    if (submitForm) document.getElementById('attendanceForm').submit();
  }

  document.getElementById('cameraModal').addEventListener('click', event => {
    if (event.target.id === 'cameraModal') closeCameraScanner();
  });
</script>
</body>
</html>
