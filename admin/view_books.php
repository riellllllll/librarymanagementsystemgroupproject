<?php
session_start();
require 'library_data.php';

$pending_count = count(array_filter($_SESSION['borrow_requests'], function ($req) {
  return $req['status'] === 'pending';
}));

$genres = [
  'All',
  'Fiction',
  'Science',
  'History',
  'Technology',
  'Literature',
  'Mathematics'
];

function format_book_id($id) {
  return str_pad((string)$id, 2, '0', STR_PAD_LEFT);
}

$selected_genre = $_GET['genre'] ?? 'All';
$search_query = strtolower(trim($_GET['q'] ?? ''));

$per_page = 12;
$current_page_num = max(1, (int)($_GET['page'] ?? 1));

$filtered = array_values(array_filter($_SESSION['books'], function ($book) use ($selected_genre, $search_query) {
  $matches_genre =
    $selected_genre === 'All' ||
    $book['genre'] === $selected_genre;

  $formatted_id = format_book_id($book['id']);

  $book_text = strtolower(
    $book['id'] . ' ' .
    $formatted_id . ' ' .
    $book['title'] . ' ' .
    $book['author'] . ' ' .
    $book['genre']
  );

  $matches_search =
    $search_query === '' ||
    strpos($book_text, $search_query) !== false;

  return $matches_genre && $matches_search;
}));

$total = count($filtered);
$total_pages = max(1, ceil($total / $per_page));
$offset = ($current_page_num - 1) * $per_page;
$books = array_slice($filtered, $offset, $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>View Books</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
  <link rel="stylesheet" href="../assets/adminView_books.css">
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">

    <span class="topbar-title">View Books</span>

    <div class="topbar-spacer"></div>

    <form class="topbar-search" method="GET" action="view_books.php">

      <?php if ($selected_genre !== 'All'): ?>
        <input
          type="hidden"
          name="genre"
          value="<?= htmlspecialchars($selected_genre) ?>"
        >
      <?php endif; ?>

      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>

      <input
        type="text"
        name="q"
        placeholder="Search by book title, author, or book number..."
        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
      >

    </form>

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

      <h1>View Books</h1>

      <p>
        Explore and manage the complete library collection
      </p>

      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>

    </div>

    <div class="books-filter-section">

      <div class="category-pills">

        <?php foreach ($genres as $genre): ?>

          <a
            href="view_books.php?genre=<?= urlencode($genre) ?><?= $search_query !== '' ? '&q=' . urlencode($_GET['q']) : '' ?>"
            class="category-pill <?= $genre === $selected_genre ? 'active' : '' ?>"
          >
            <?= htmlspecialchars($genre) ?>
          </a>

        <?php endforeach; ?>

      </div>

      <div class="view-books-toolbar">

        <p class="view-books-count">
          Showing

          <strong>
            <?= count($books) ?>
          </strong>

          of

          <strong>
            <?= $total ?>
          </strong>

          books

          <?php if ($selected_genre !== 'All'): ?>
            in <span><?= htmlspecialchars($selected_genre) ?></span>
          <?php endif; ?>
        </p>

        <a href="add_book.php" class="view-books-add-btn">
          + Add Book
        </a>

      </div>

    </div>

    <?php if (empty($books)): ?>

      <div class="card">

        <div class="empty-state">

          <div class="empty-icon">
            &#128218;
          </div>

          <h3>No books found</h3>

          <p>
            No books matched your selected genre or search term.
          </p>

        </div>

      </div>

    <?php else: ?>

      <div class="books-grid">

        <?php foreach ($books as $book): ?>

          <div class="book-card">

            <div class="book-cover <?= htmlspecialchars($book['color']) ?>">

              <span class="book-cover-icon">
                &#128214;
              </span>

              <div class="book-cover-accent"></div>

            </div>

            <div class="book-info">

              <div class="book-category">
                ID #<?= htmlspecialchars(format_book_id($book['id'])) ?>
                &middot;
                <?= htmlspecialchars($book['genre']) ?>
              </div>

              <div class="book-title">
                <?= htmlspecialchars($book['title']) ?>
              </div>

              <div class="book-author">
                <?= htmlspecialchars($book['author']) ?>
              </div>

              <div class="book-meta">

                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                  <?php if ($book['available'] > 0): ?>

                    <span class="badge badge-success">
                      <?= htmlspecialchars($book['available']) ?> Available
                    </span>

                  <?php else: ?>

                    <span class="badge badge-danger">
                      Unavailable
                    </span>

                  <?php endif; ?>

                  <span class="badge badge-muted">
                    <?= htmlspecialchars($book['copies']) ?> Copies
                  </span>
                </div>

              </div>

              <div class="book-actions">

                <a
                  href="edit_book.php?id=<?= urlencode($book['id']) ?>"
                  class="btn-outline"
                >
                  Edit
                </a>

                <a
                  href="delete_book.php?id=<?= urlencode($book['id']) ?>"
                  class="btn-danger"
                >
                  Delete
                </a>

              </div>

            </div>

          </div>

        <?php endforeach; ?>

      </div>

      <?php if ($total_pages > 1): ?>

        <div class="pagination">

          <?php for ($i = 1; $i <= $total_pages; $i++): ?>

            <a
              href="view_books.php?genre=<?= urlencode($selected_genre) ?>&q=<?= urlencode($_GET['q'] ?? '') ?>&page=<?= $i ?>"
              class="page-btn <?= $i === $current_page_num ? 'active' : '' ?>"
            >
              <?= $i ?>
            </a>

          <?php endfor; ?>

        </div>

      <?php endif; ?>

    <?php endif; ?>

  </main>

</div>

</body>
</html>