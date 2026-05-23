<?php
session_start();
require 'library_data.php';

$pending_count = count(array_filter($_SESSION['borrow_requests'], function ($req) {
  return $req['status'] === 'pending';
}));

$book_id = (int)($_GET['id'] ?? $_POST['book_id'] ?? 0);

$selected_book = null;

foreach ($_SESSION['books'] as $book) {
  if ((int)$book['id'] === $book_id) {
    $selected_book = $book;
    break;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_book) {
  foreach ($_SESSION['books'] as $index => $book) {
    if ((int)$book['id'] === $book_id) {
      $_SESSION['archived_books'][] = $book;
      unset($_SESSION['books'][$index]);
      $_SESSION['books'] = array_values($_SESSION['books']);

      header('Location: archive_books.php?deleted=1');
      exit;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Delete Book</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">

    <span class="topbar-title">Delete Book</span>

    <div class="topbar-spacer"></div>

    <a href="student_req.php" class="topbar-icon-btn" title="Student Borrow Requests">

      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>

      <?php if ($pending_count > 0): ?>
        <span class="topbar-notif-dot"></span>
      <?php endif; ?>

    </a>

    <a href="admin_profile.php" class="topbar-icon-btn" title="Admin Profile">

      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>

    </a>

  </header>

  <main class="page-content">

    <div class="page-header">

      <h1>Delete Book</h1>

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>

    </div>

    <div class="card">

      <div class="card-body">

        <div class="card-title">
          Delete Confirmation
        </div>

        <?php if ($selected_book): ?>

          <div class="alert alert-gold" style="margin-bottom:18px;">
            This book will be moved to archive:

            <strong>
              <?= htmlspecialchars($selected_book['title']) ?>
            </strong>

            by <?= htmlspecialchars($selected_book['author']) ?>.
          </div>

          <form method="POST">

            <input
              type="hidden"
              name="book_id"
              value="<?= htmlspecialchars($selected_book['id']) ?>"
            >

            <div style="display:flex; gap:10px;">

              <button type="submit" class="btn-danger">
                Move to Archive
              </button>

              <a href="view_books.php" class="btn-outline">
                Cancel
              </a>

            </div>

          </form>

        <?php else: ?>

          <div class="alert alert-gold">
            No book selected. Please go to View Books and click Delete.
          </div>

        <?php endif; ?>

      </div>

    </div>

  </main>

</div>

</body>
</html>