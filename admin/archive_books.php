<?php
// ============================================================
// admin/archive_books.php — DB-powered (UI unchanged)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../classes/Book.php';

// Session guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

if (!function_exists('format_book_id')) {
  function format_book_id($id) {
    return str_pad((string)(int)$id, 2, '0', STR_PAD_LEFT);
  }
}

$pending_count = pending_request_count();

$db   = new Database();
$book = new Book($db->getConnection());

$flash_msg = $_SESSION['archive_msg'] ?? '';
$flash_err = $_SESSION['archive_err'] ?? '';
unset($_SESSION['archive_msg'], $_SESSION['archive_err']);

// ── Handle POST (Retrieve / Permanently Delete) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $book_id = (int)($_POST['book_id'] ?? 0);
  $action  = $_POST['action']        ?? 'retrieve';

  if ($book_id) {
    if ($action === 'delete_permanent') {
      $result = $book->delete($book_id);
      if ($result === true) {
        $_SESSION['archive_msg'] = 'Book permanently deleted from the database.';
      } else {
        $_SESSION['archive_err'] = is_string($result) ? $result : 'Failed to delete book.';
      }
      header('Location: archive_books.php');
      exit;
    } else {
      $book->restore($book_id);
      header('Location: view_books.php?retrieved=1');
      exit;
    }
  }
}

// ── Load archived books from DB & map to UI shape ──
$db_archived = $book->getArchived();
$archived_books = [];
foreach ($db_archived as $b) {
  $archived_books[] = [
    'id'     => (int)$b['id'],
    'title'  => $b['title'],
    'author' => $b['author'],
    'genre'  => $b['category'],
    'copies' => (int)$b['total_copies'],
  ];
}
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

    <?php if ($flash_msg): ?>
      <div class="alert alert-sage" style="margin-bottom:1rem;"><?= htmlspecialchars($flash_msg) ?></div>
    <?php endif; ?>
    <?php if ($flash_err): ?>
      <div class="alert alert-rust" style="margin-bottom:1rem;"><?= htmlspecialchars($flash_err) ?></div>
    <?php endif; ?>

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
                    <td><?= htmlspecialchars(format_book_id($book['id'])) ?></td>
                    <td><?= htmlspecialchars($book['title']) ?></td>
                    <td><?= htmlspecialchars($book['author']) ?></td>
                    <td><?= htmlspecialchars($book['genre']) ?></td>
                    <td><?= htmlspecialchars($book['copies']) ?></td>

                    <td>
                      <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <form method="POST" action="archive_books.php" style="margin:0;">
                          <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['id']) ?>">
                          <input type="hidden" name="action"  value="retrieve">
                          <button type="submit" class="btn-primary">Retrieve</button>
                        </form>

                        <form method="POST" action="archive_books.php" style="margin:0;"
                          onsubmit="return confirm('Permanently delete this book from the database? This cannot be undone.');">
                          <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['id']) ?>">
                          <input type="hidden" name="action"  value="delete_permanent">
                          <button type="submit" class="btn-danger">Delete Forever</button>
                        </form>
                      </div>
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