<?php
require 'library_data.php';

if (!function_exists('format_book_id')) {
  function format_book_id($id) {
    return str_pad((string)(int)$id, 2, '0', STR_PAD_LEFT);
  }
}

if (!function_exists('format_student_number')) {
  function format_student_number($id) {
    $number = preg_replace('/\D/', '', (string)$id);

    if ($number === '') {
      return '';
    }

    $last_three = substr($number, -3);

    return str_pad($last_three, 3, '0', STR_PAD_LEFT);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $request_id = (int)($_POST['request_id'] ?? 0);
  $action = $_POST['action'] ?? '';

  foreach ($_SESSION['borrow_requests'] as $index => $request) {
    if ((int)($request['id'] ?? 0) === $request_id && ($request['status'] ?? '') === 'pending') {
      $book_id = format_book_id($request['book_id'] ?? 0);
      $book_index = find_book_index($book_id);

      if ($action === 'approve') {
        if ($book_index !== null && (int)($_SESSION['books'][$book_index]['available'] ?? 0) > 0) {
          $_SESSION['books'][$book_index]['available']--;

          $_SESSION['borrow_requests'][$index]['status'] = 'approved';
          $_SESSION['borrow_requests'][$index]['approved_at'] = date('M d, Y');

          $_SESSION['borrowed_books'][] = [
            'id' => uniqid('BRW-'),
            'request_id' => $request['id'] ?? $request_id,
            'student' => $request['student'] ?? $request['student_name'] ?? 'Unknown Student',
            'student_id' => format_student_number($request['student_id'] ?? $request['id_number'] ?? ''),
            'book_id' => $book_id,
            'book_title' => $request['book_title'] ?? $request['book'] ?? $request['title'] ?? 'Unknown Book',
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'return_date' => '',
            'date' => date('M d, Y'),
            'status' => 'borrowed'
          ];
        }
      } elseif ($action === 'reject') {
        $_SESSION['borrow_requests'][$index]['status'] = 'rejected';
      }

      break;
    }
  }

  header('Location: student_req.php?updated=1');
  exit;
}

$pending_count = pending_request_count();
$requests = $_SESSION['borrow_requests'];
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
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>

              <?php if (empty($requests)): ?>

                <tr>
                  <td colspan="5">
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
                    $available = $book_index !== null ? (int)($_SESSION['books'][$book_index]['available'] ?? 0) : 0;
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