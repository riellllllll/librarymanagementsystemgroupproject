<?php
session_start();
require 'library_data.php';

$pending_count = count(array_filter($_SESSION['borrow_requests'], function ($req) {
  return $req['status'] === 'pending';
}));

$genres = ['Fiction', 'Science', 'History', 'Technology', 'Literature', 'Mathematics'];

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
      $_SESSION['books'][$index]['title'] = trim($_POST['title'] ?? '');
      $_SESSION['books'][$index]['author'] = trim($_POST['author'] ?? '');
      $_SESSION['books'][$index]['genre'] = trim($_POST['genre'] ?? '');
      $_SESSION['books'][$index]['copies'] = (int)($_POST['copies'] ?? 1);

      $_SESSION['books'][$index]['available'] = min(
        $_SESSION['books'][$index]['available'],
        $_SESSION['books'][$index]['copies']
      );

      header('Location: view_books.php?updated=1');
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

  <title>Edit Book</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">

    <span class="topbar-title">Edit Book</span>

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

      <h1>Edit Book</h1>

      <p>Select a book from View Books to edit its information.</p>

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>

    </div>

    <div class="card">

      <div class="card-body">

        <?php if ($selected_book): ?>

          <form method="POST">

            <input type="hidden" name="book_id" value="<?= htmlspecialchars($selected_book['id']) ?>">

            <div class="field">
              <label>Book ID</label>

              <div class="input-wrap">
                <input
                  class="no-icon"
                  type="text"
                  value="<?= htmlspecialchars($selected_book['id']) ?>"
                  readonly
                >
              </div>
            </div>

            <div class="field">
              <label>Title</label>

              <div class="input-wrap">
                <input
                  class="no-icon"
                  type="text"
                  name="title"
                  value="<?= htmlspecialchars($selected_book['title']) ?>"
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
                  value="<?= htmlspecialchars($selected_book['author']) ?>"
                  required
                >
              </div>
            </div>

            <div class="field">
              <label>Genre</label>

              <div class="input-wrap">
                <select class="no-icon" name="genre" required>

                  <?php foreach ($genres as $genre): ?>

                    <option
                      value="<?= htmlspecialchars($genre) ?>"
                      <?= $genre === $selected_book['genre'] ? 'selected' : '' ?>
                    >
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
                  value="<?= htmlspecialchars($selected_book['copies']) ?>"
                  required
                >
              </div>
            </div>

            <button class="btn-primary" type="submit">
              Update Book
            </button>

          </form>

        <?php else: ?>

          <div class="alert alert-gold">
            No book selected. Please go to View Books and click Edit.
          </div>

        <?php endif; ?>

      </div>

    </div>

  </main>

</div>

</body>
</html>