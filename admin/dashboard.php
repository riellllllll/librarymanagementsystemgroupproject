<?php
// ============================================================
// admin/dashboard.php — DB-powered (UI unchanged)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../classes/BorrowRecord.php';

// Session guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

$pending_count = pending_request_count();

// Update overdue statuses
$db     = new Database();
$borrow = new BorrowRecord($db->getConnection());
$borrow->updateOverdueStatuses();

// ── Fetch recent borrowed records from DB ───────────────────
$recent_db = get_recent_borrowed(5);
$recent_borrowed = [];
foreach ($recent_db as $r) {
    $recent_borrowed[] = [
        'book_title' => $r['book_title'],
        'student'    => $r['student'],
        'date'       => date('M j, Y', strtotime($r['date'])),
        'status'     => $r['status'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Admin Dashboard</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">

    <span class="topbar-title">Dashboard</span>

    <div class="topbar-spacer"></div>

    <a href="student_req.php" class="topbar-icon-btn" title="Student Requests">
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
      <h1>Admin Dashboard</h1>

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>
    </div>

    <div class="stats-grid">

      <div class="stat-card">
        <div class="stat-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
          </svg>
        </div>

        <div class="stat-value"><?= active_book_count() ?></div>
        <div class="stat-label">ACTIVE BOOKS</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="4" rx="1"></rect>
            <path d="M5 8v11h14V8"></path>
            <path d="M10 12h4"></path>
          </svg>
        </div>

        <div class="stat-value"><?= archived_book_count() ?></div>
        <div class="stat-label">ARCHIVED BOOKS</div>
      </div>

      <div class="stat-card stat-danger">
        <div class="stat-icon icon-danger">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>

        <div class="stat-value">PHP <?= pending_fines_total() ?></div>
        <div class="stat-label">PENDING FINES</div>
      </div>

      <div class="stat-card stat-sage">
        <div class="stat-icon icon-sage">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5"></path>
          </svg>
        </div>

        <div class="stat-value"><?= $pending_count ?></div>
        <div class="stat-label"><?= $pending_count === 0 ? 'NO PENDING REQUESTS' : 'PENDING REQUESTS' ?></div>
      </div>

    </div>

    <div class="card">

      <div class="card-body">

        <div class="card-title">Recently Borrowed</div>

        <div class="table-wrap">

          <table>
            <thead>
              <tr>
                <th>BOOK</th>
                <th>STUDENT</th>
                <th>DATE</th>
                <th>STATUS</th>
              </tr>
            </thead>

            <tbody>

              <?php foreach ($recent_borrowed as $borrow): ?>

                <tr>
                  <td><?= htmlspecialchars($borrow['book_title']) ?></td>
                  <td><?= htmlspecialchars($borrow['student']) ?></td>
                  <td><?= htmlspecialchars($borrow['date']) ?></td>
                  <td>
                    <?php
                      $s = $borrow['status'] ?? 'active';
                      $label = strtoupper($s === 'pending_return' ? 'PENDING RETURN' : $s);
                      $cls   = match($s) {
                        'returned'       => 'badge-sage',
                        'overdue'        => 'badge-rust',
                        'pending_return' => 'badge-gold',
                        default          => 'badge-gold',
                      };
                    ?>
                    <span class="badge <?= $cls ?>"><?= htmlspecialchars($label) ?></span>
                  </td>
                </tr>

              <?php endforeach; ?>

            </tbody>
          </table>

        </div>

      </div>

    </div>

  </main>

</div>

</body>
</html>