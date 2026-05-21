<?php
session_start();

$categories = ['All', 'Fiction', 'Science', 'History', 'Technology', 'Literature', 'Mathematics'];
$selected_category = $_GET['category'] ?? 'All';
$per_page = 12;
$current_page_num = max(1, (int)($_GET['page'] ?? 1));

$all_books = [
  ['id'=>1,'title'=>'The Great Gatsby','author'=>'F. Scott Fitzgerald','category'=>'Fiction','copies'=>3,'available'=>2,'color'=>'color-a'],
  ['id'=>2,'title'=>'To Kill a Mockingbird','author'=>'Harper Lee','category'=>'Fiction','copies'=>4,'available'=>1,'color'=>'color-b'],
  ['id'=>3,'title'=>'A Brief History of Time','author'=>'Stephen Hawking','category'=>'Science','copies'=>2,'available'=>2,'color'=>'color-c'],
  ['id'=>4,'title'=>'Sapiens','author'=>'Yuval Noah Harari','category'=>'History','copies'=>3,'available'=>0,'color'=>'color-d'],
  ['id'=>5,'title'=>'Clean Code','author'=>'Robert C. Martin','category'=>'Technology','copies'=>5,'available'=>4,'color'=>'color-e'],
  ['id'=>6,'title'=>'1984','author'=>'George Orwell','category'=>'Fiction','copies'=>3,'available'=>2,'color'=>'color-a'],
  ['id'=>7,'title'=>'The Selfish Gene','author'=>'Richard Dawkins','category'=>'Science','copies'=>2,'available'=>1,'color'=>'color-b'],
  ['id'=>8,'title'=>'Calculus Made Easy','author'=>'Silvanus P. Thompson','category'=>'Mathematics','copies'=>4,'available'=>3,'color'=>'color-c'],
  ['id'=>9,'title'=>'Design Patterns','author'=>'GoF','category'=>'Technology','copies'=>3,'available'=>3,'color'=>'color-d'],
  ['id'=>10,'title'=>'Noli Me Tangere','author'=>'Jose Rizal','category'=>'Literature','copies'=>6,'available'=>5,'color'=>'color-e'],
  ['id'=>11,'title'=>'El Filibusterismo','author'=>'Jose Rizal','category'=>'Literature','copies'=>5,'available'=>4,'color'=>'color-a'],
  ['id'=>12,'title'=>'Guns, Germs, and Steel','author'=>'Jared Diamond','category'=>'History','copies'=>2,'available'=>2,'color'=>'color-b'],
  ['id'=>13,'title'=>'The Pragmatic Programmer','author'=>'Andrew Hunt','category'=>'Technology','copies'=>3,'available'=>2,'color'=>'color-c'],
  ['id'=>14,'title'=>'Pride and Prejudice','author'=>'Jane Austen','category'=>'Literature','copies'=>4,'available'=>3,'color'=>'color-d'],
  ['id'=>15,'title'=>'Cosmos','author'=>'Carl Sagan','category'=>'Science','copies'=>3,'available'=>1,'color'=>'color-e'],
  ['id'=>16,'title'=>'The Art of War','author'=>'Sun Tzu','category'=>'History','copies'=>4,'available'=>4,'color'=>'color-a'],
];

$filtered = $selected_category === 'All'
  ? $all_books
  : array_values(array_filter($all_books, fn($book) => $book['category'] === $selected_category));

$total = count($filtered);
$total_pages = max(1, (int)ceil($total / $per_page));
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
  <link rel="stylesheet" href="../student/style.css">
</head>

<body>

  <?php include "sidebar.php"; ?>

  <div class="main-wrapper">

    <!-- TOP BAR -->
    <header class="topbar">
      <span class="topbar-title">View Books</span>

      <div class="topbar-spacer"></div>

      <div class="topbar-search">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>

        <input type="text" placeholder="Search books...">
      </div>

      <a href="view_fines.php" class="topbar-icon-btn" title="Fines & Notifications">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>

        <span class="topbar-notif-dot"></span>
      </a>
    </header>

    <!-- PAGE CONTENT -->
    <main class="page-content">

      <!-- PAGE HEADER -->
      <div class="page-header">
        <h1>View Books</h1>
        <p>Explore and manage the complete library collection</p>

        <div class="gold-rule">
          <span></span>
          <i>✦</i>
          <span></span>
        </div>
      </div>

      <!-- CATEGORY PILLS -->
      <div class="category-pills">
        <?php foreach ($categories as $category): ?>
          <a
            href="?category=<?= urlencode($category) ?>"
            class="category-pill <?= $category === $selected_category ? 'active' : '' ?>"
          >
            <?= htmlspecialchars($category) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- BOOK COUNT AND ADD BUTTON -->
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
        <p class="text-muted" style="font-size:13px;">
          Showing
          <strong style="color:var(--ink)"><?= count($books) ?></strong>
          of
          <strong style="color:var(--ink)"><?= $total ?></strong>
          books

          <?php if ($selected_category !== 'All'): ?>
            in <strong style="color:var(--gold)"><?= htmlspecialchars($selected_category) ?></strong>
          <?php endif; ?>
        </p>

        <a href="add_book.php" class="btn-primary">
          Add Book
        </a>
      </div>

      <!-- BOOKS LIST -->
      <?php if (empty($books)): ?>

        <div class="card">
          <div class="empty-state">
            <div class="empty-icon">&#128218;</div>
            <h3>No books found</h3>
            <p>No books available in this category right now.</p>
          </div>
        </div>

      <?php else: ?>

        <div class="books-grid">
          <?php foreach ($books as $book): ?>

            <div class="book-card">

              <div class="book-cover <?= htmlspecialchars($book['color']) ?>">
                <span class="book-cover-icon">&#128214;</span>
                <div class="book-cover-accent"></div>
              </div>

              <div class="book-info">
                <div class="book-category">
                  <?= htmlspecialchars($book['category']) ?>
                </div>

                <div class="book-title">
                  <?= htmlspecialchars($book['title']) ?>
                </div>

                <div class="book-author">
                  <?= htmlspecialchars($book['author']) ?>
                </div>

                <div class="book-meta">
                  <?php if ($book['available'] > 0): ?>
                    <span class="badge badge-success">
                      <?= htmlspecialchars($book['available']) ?> Available
                    </span>
                  <?php else: ?>
                    <span class="badge badge-danger">
                      Unavailable
                    </span>
                  <?php endif; ?>
                </div>

                <div style="display:flex; gap:8px; margin-top:12px;">
                  <a href="edit_book.php?id=<?= urlencode($book['id']) ?>" class="btn-outline">
                    Edit
                  </a>

                  <a href="delete_book.php?id=<?= urlencode($book['id']) ?>" class="btn-danger">
                    Delete
                  </a>
                </div>
              </div>

            </div>

          <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>

          <div class="pagination">

            <?php if ($current_page_num > 1): ?>
              <a href="?category=<?= urlencode($selected_category) ?>&page=<?= $current_page_num - 1 ?>" class="page-btn">
                &#8592; Prev
              </a>
            <?php else: ?>
              <span class="page-btn" style="opacity:.4;pointer-events:none;">
                &#8592; Prev
              </span>
            <?php endif; ?>

            <?php for ($i = max(1, $current_page_num - 2); $i <= min($total_pages, $current_page_num + 2); $i++): ?>
              <a
                href="?category=<?= urlencode($selected_category) ?>&page=<?= $i ?>"
                class="page-btn <?= $i === $current_page_num ? 'active' : '' ?>"
              >
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <?php if ($current_page_num < $total_pages): ?>
              <a href="?category=<?= urlencode($selected_category) ?>&page=<?= $current_page_num + 1 ?>" class="page-btn">
                Next &#8594;
              </a>
            <?php else: ?>
              <span class="page-btn" style="opacity:.4;pointer-events:none;">
                Next &#8594;
              </span>
            <?php endif; ?>

          </div>

        <?php endif; ?>

      <?php endif; ?>

    </main>

  </div>

</body>
</html>