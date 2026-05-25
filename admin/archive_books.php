<?php
session_start();
require 'library_data.php';

$pending_count = count(array_filter($_SESSION['borrow_requests'], function ($req) {
  return $req['status'] === 'pending';
}));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $book_id = (int)($_POST['book_id'] ?? 0);

  foreach ($_SESSION['archived_books'] as $index => $book) {
    if ((int)$book['id'] === $book_id) {
      $_SESSION['books'][] = $book;

      unset($_SESSION['archived_books'][$index]);

      $_SESSION['archived_books'] = array_values($_SESSION['archived_books']);

      header('Location: view_books.php?retrieved=1');
      exit;
    }
  }
}

$archived_books = $_SESSION['archived_books'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Archive Books</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">

    <span class="topbar-title">Archive Books</span>

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

      <h1>
        Archive <em style="color:var(--gold);font-style:italic;">Books</em>
      </h1>

      <p>
        Deleted books are stored here and can be retrieved anytime.
      </p>

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>

    </div>

    <div class="card">

      <div class="card-body">

        <div class="card-title">
          Archived Books
        </div>

        <div class="card-subtitle">
          Retrieve a book to return it to View Books.
        </div>

        <div class="table-wrap">

          <table>

            <thead>
              <tr>
                <th>ID #</th>
                <th>Book Title</th>
                <th>Author</th>
                <th>Genre</th>
                <th>Copies</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>

              <?php if (empty($archived_books)): ?>

                <tr>
                  <td colspan="6">
                    No archived books yet.
                  </td>
                </tr>

              <?php else: ?>

                <?php foreach ($archived_books as $book): ?>

                  <tr>
                    <td><?= htmlspecialchars($book['id']) ?></td>
                    <td><?= htmlspecialchars($book['title']) ?></td>
                    <td><?= htmlspecialchars($book['author']) ?></td>
                    <td><?= htmlspecialchars($book['genre']) ?></td>
                    <td><?= htmlspecialchars($book['copies']) ?></td>

                    <td>
                      <form method="POST" action="archive_books.php">

                        <input
                          type="hidden"
                          name="book_id"
                          value="<?= htmlspecialchars($book['id']) ?>"
                        >

                        <button type="submit" class="btn-primary">
                          Retrieve
                        </button>

                      </form>
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