<?php
// ============================================================
// admin/student_req.php — DB-powered (UI unchanged)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../classes/BookRequest.php';

// Session guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

$db     = new Database();
$conn   = $db->getConnection();
$reqObj = new BookRequest($conn);

if (!function_exists('format_book_id')) {
  function format_book_id($id) {
    return str_pad((string)(int)$id, 2, '0', STR_PAD_LEFT);
  }
}

if (!function_exists('format_student_number')) {
  function format_student_number($id) {
    $number = preg_replace('/\D/', '', (string)$id);
    if ($number === '') return '';
    return $number;  // show full student number, not just last 3 digits
  }
}

// ── Handle Approve / Reject ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $request_id = (int)($_POST['request_id'] ?? 0);
  $action     = $_POST['action'] ?? '';

  if ($action === 'approve') {
    $reqObj->approve($request_id, (int)$_SESSION['user_id']);
  } elseif ($action === 'reject') {
    $reqObj->reject($request_id, (int)$_SESSION['user_id']);
  }

  header('Location: student_req.php?updated=1');
  exit;
}

// ── Load requests from DB & map to UI shape ──
$db_requests = $reqObj->getAll();
$requests    = [];
foreach ($db_requests as $r) {
  $requests[] = [
    'id'           => (int)$r['id'],
    'student'      => $r['student_name'],
    'student_id'   => $r['student_number'],
    'book_id'      => (int)$r['book_id'],
    'book_title'   => $r['book_title'],
    'date'         => date('M d, Y', strtotime($r['request_date'])),
    'request_date' => $r['request_date'],
    'status'       => $r['status'],
    // Dates the student picked for borrow + return
    'student_borrow_date' => $r['requested_borrow_date'] ?? null,
    'student_due_date'    => $r['requested_due_date']    ?? null,
    // Stash availability so the UI can show it without calling find_book_index
    '_available'   => (int)$r['copies_available'],
  ];
}

// Stub for find_book_index — not used since we pass _available directly
if (!function_exists('find_book_index')) {
    function find_book_index($id) { return null; }
}

$pending_count = pending_request_count();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Student Requests</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">

    <span class="topbar-title">Student Requests</span>

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
      <h1>Student Borrow Requests</h1>

      <p>
        <?= $pending_count === 0 ? 'No pending requests right now.' : $pending_count . ' pending request(s) waiting for approval.' ?>
      </p>

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-sage">
        Request updated successfully.
      </div>
    <?php endif; ?>

    <div class="card">

      <div class="card-body">

        <div class="card-title">Borrow Requests</div>
        <p class="card-subtitle">Review student requests and approve available books</p>

        <div class="table-wrap">

          <table>

            <thead>
              <tr>
                <th>Student</th>
                <th>Book</th>
                <th>Requested On</th>
                <th>Borrow Period</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>

              <?php if (empty($requests)): ?>

                <tr>
                  <td colspan="6">
                    <div class="empty-state">
                      <h3>No borrow requests yet</h3>
                      <p>Student borrow requests will appear here</p>
                    </div>
                  </td>
                </tr>

              <?php else: ?>

                <?php foreach ($requests as $request): ?>

                  <?php
                    $request_id = $request['id'] ?? 0;
                    $student_name = $request['student'] ?? $request['student_name'] ?? 'Unknown Student';
                    $student_id = format_student_number($request['student_id'] ?? $request['id_number'] ?? '');
                    $book_id = format_book_id($request['book_id'] ?? 0);
                    $book_index = find_book_index($book_id);
                    $book_title = $request['book_title'] ?? $request['book'] ?? $request['title'] ?? 'Unknown Book';
                    $request_date = $request['date'] ?? $request['request_date'] ?? $request['borrow_date'] ?? '';
                    $status = $request['status'] ?? 'pending';
                    $available = $request['_available'] ?? 0;
                  ?>

                  <tr>

                    <td>
                      <?= htmlspecialchars($student_name) ?>

                      <?php if ($student_id !== ''): ?>
                        <br>
                        <small class="text-muted">
                          Student No: <?= htmlspecialchars($student_id) ?>
                        </small>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?= htmlspecialchars($book_title) ?>
                      <br>
                      <small class="text-muted">
                        Book ID: <?= htmlspecialchars($book_id) ?> · Available: <?= htmlspecialchars($available) ?>
                      </small>
                    </td>

                    <td><?= htmlspecialchars($request_date) ?></td>

                    <td>
                      <?php
                        $sb = $request['student_borrow_date'] ?? null;
                        $sd = $request['student_due_date']    ?? null;
                      ?>
                      <?php if ($sb && $sd): ?>
                        <span style="white-space:nowrap;font-size:0.82rem;">
                          <?= date('M j, Y', strtotime($sb)) ?>
                          &nbsp;→&nbsp;
                          <strong style="color:var(--gold);"><?= date('M j, Y', strtotime($sd)) ?></strong>
                        </span>
                        <br>
                        <small class="text-muted">
                          <?= max(1, (int)((strtotime($sd) - strtotime($sb)) / 86400)) ?> day(s)
                        </small>
                      <?php else: ?>
                        <small class="text-muted">Not specified</small>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($status === 'pending'): ?>
                        <span class="badge badge-gold">PENDING</span>
                      <?php elseif ($status === 'approved'): ?>
                        <span class="badge badge-sage">APPROVED</span>
                      <?php else: ?>
                        <span class="badge badge-rust">REJECTED</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($status === 'pending'): ?>

                        <form method="POST" action="student_req.php" style="display:flex; gap:8px; flex-wrap:wrap;">

                          <input
                            type="hidden"
                            name="request_id"
                            value="<?= htmlspecialchars($request_id) ?>"
                          >

                          <button
                            class="btn-primary"
                            type="submit"
                            name="action"
                            value="approve"
                            <?= $available <= 0 ? 'disabled' : '' ?>
                          >
                            Approve
                          </button>

                          <button
                            class="btn-danger"
                            type="submit"
                            name="action"
                            value="reject"
                          >
                            Reject
                          </button>

                        </form>

                        <?php if ($available <= 0): ?>
                          <small class="text-muted">No available copies</small>
                        <?php endif; ?>

                      <?php else: ?>

                        <span class="text-muted">Done</span>

                      <?php endif; ?>
                    </td>

                  </tr>

                <?php endforeach; ?>

              <?php endif; ?>

            </tbody>

          </table>

        </div>

      </div>

    </div>

  </main>

</div>

</body>
</html>