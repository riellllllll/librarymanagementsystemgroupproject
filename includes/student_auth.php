<?php
// ============================================================
// includes/student_auth.php
// Shared bootstrap for all student pages.
// Include AFTER session_start().
// Provides: $conn, $student_id (DB id), $student_no, $student_name
// ============================================================

require_once __DIR__ . '/../config/Database.php';

// ── Guard: must be logged in as student ──────────────────────
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../login/login.php');
    exit;
}

$GLOBALS['__db'] = new Database();
$conn            = $GLOBALS['__db']->getConnection();

if (!$conn) {
    die('<p style="font-family:sans-serif;color:#c0392b;">Database connection failed. Check config/Database.php</p>');
}

$student_id   = (int)$_SESSION['user_id'];
$student_no   = $_SESSION['student_id']   ?? '';
$student_name = $_SESSION['student_name'] ?? 'Student';

// ── Handle "Mark all notifications as read" (must run BEFORE any HTML) ──
if (isset($_GET['notifications_read'])) {
    require_once __DIR__ . '/../classes/Notification.php';
    $__notif = new Notification($conn);
    $__notif->markAllRead($student_id);
    $clean = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $clean);
    exit;
}