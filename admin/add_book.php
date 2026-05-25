<?php
session_start();
require 'library_data.php';

$pending_count = count(array_filter($_SESSION['borrow_requests'], function ($req) {
  return $req['status'] === 'pending';
}));

$genres = [
  'Fiction',
  'Science',
  'History',
  'Technology',
  'Literature',
  'Mathematics'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $next_id = 1;

  foreach ($_SESSION['books'] as $book) {
    $next_id = max($next_id, (int)$book['id'] + 1);
  }

  $_SESSION['books'][] = [
    'id' => $next_id,
    'title' => trim($_POST['title'] ?? ''),
    'author' => trim($_POST['author'] ?? ''),
    'genre' => trim($_POST['genre'] ?? ''),
    'copies' => (int)($_POST['copies'] ?? 1),
    'available' => (int)($_POST['copies'] ?? 1),
    'color' => 'color-a'
  ];

  header('Location: view_books.php?added=1');
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Add Book</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">

    <span class="topbar-title">Add Book</span>

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

      <h1>Add Book</h1>

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>

    </div>

    <div class="card">

      <div class="card-body">

        <form method="POST">

          <div class="field">
            <label>Title</label>

            <div class="input-wrap">
              <input
                class="no-icon"
                type="text"
                name="title"
                required
              >
            </div>
          </div>

          <div class="field">
            <label>Author</label>

            <div class="input-wrap">
              <input
                class="no-icon"
                type="text"
                name="author"
                required
              >
            </div>
          </div>

          <div class="field">
            <label>Genre</label>

            <div class="input-wrap">
              <select class="no-icon" name="genre" required>

                <option value="">Select genre...</option>

                <?php foreach ($genres as $genre): ?>

                  <option value="<?= htmlspecialchars($genre) ?>">
                    <?= htmlspecialchars($genre) ?>
                  </option>

                <?php endforeach; ?>

              </select>
            </div>
          </div>

          <div class="field">
            <label>Copies</label>

            <div class="input-wrap">
              <input
                class="no-icon"
                type="number"
                name="copies"
                min="1"
                required
              >
            </div>
          </div>

          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <button class="btn-primary" type="submit">
              Save Book
            </button>

            <a href="view_books.php" class="btn-outline">
              Return
            </a>
          </div>

        </form>

      </div>

    </div>

  </main>

</div>

</body>
</html>