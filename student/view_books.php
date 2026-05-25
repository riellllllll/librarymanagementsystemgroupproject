<?php
// view_books.php — Browse all books
session_start();
$active_page = 'browse';

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
  : array_values(array_filter($all_books, fn($b) => $b['category'] === $selected_category));

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
  <title>Browse Books — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="6"  y="8"  width="8"  height="32" rx="1.5" fill="#c9973a"/>
        <rect x="16" y="10" width="6"  height="30" rx="1.5" fill="#e8c26a"/>
        <rect x="24" y="6"  width="10" height="36" rx="1.5" fill="#c9973a"/>
        <rect x="36" y="9"  width="6"  height="31" rx="1.5" fill="#a07830"/>
        <rect x="5"  y="38" width="38" height="2.5" rx="1.25" fill="#7a6030"/>
      </svg>
    </div>
    <div>
      <h2>Cv<em>SU</em></h2>
      <div class="sidebar-subtitle">Library System</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="dashboard.php" class="nav-link ">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>
    <div class="nav-section-label">Books</div>
    <a href="view_books.php" class="nav-link active">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Browse Books
    </a>
    <a href="search_books.php" class="nav-link ">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Search Books
    </a>
    <a href="request_borrow.php" class="nav-link ">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 5v14M5 12l7-7 7 7"/>
      </svg>
      Request Borrow
    </a>

    <div class="nav-section-label">My Library</div>
    <a href="borrow_history.php" class="nav-link ">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/>
        <polyline points="3 3 3.05 11 11 10.94"/>
      </svg>
      Borrow History
    </a>
    <a href="return_book.php" class="nav-link ">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
      </svg>
      Return a Book
    </a>
    <a href="view_fines.php" class="nav-link ">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      My Fines
    </a>

    <div class="nav-section-label">Account</div>
    <a href="profile.php" class="nav-link ">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      My Profile
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">JD</div>
      <div class="user-info">
        <div class="user-name">Juan Dela Cruz</div>
        <div class="user-role">Student</div>
      </div>
    </div>
    <a href="../includes/logout.php" class="btn-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Log Out
    </a>
  </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">

  <!-- TOP BAR -->
  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">Browse Books</span>
    <div class="topbar-spacer"></div>
    <?php require_once '../includes/student_notifications.php'; ?>
   
    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>

  <!-- PAGE CONTENT -->
  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>Browse Books</h1>
      <p>Explore our complete library collection</p>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
    </div>

    <!-- Category Pills -->
    <div class="category-pills">
      <?php foreach ($categories as $cat): ?>
        <a href="?category=<?= urlencode($cat) ?>" class="category-pill <?= $cat===$selected_category?'active':'' ?>">
          <?= htmlspecialchars($cat) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
      <p class="text-muted" style="font-size:13px;">
        Showing <strong style="color:var(--ink)"><?= count($books) ?></strong> of
        <strong style="color:var(--ink)"><?= $total ?></strong> books
        <?= $selected_category !== 'All' ? 'in <strong style="color:var(--gold)">'.htmlspecialchars($selected_category).'</strong>' : '' ?>
      </p>
      <a href="request_borrow.php" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Request Borrow
      </a>
    </div>

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
        <div class="book-cover <?= $book['color'] ?>">
          <span class="book-cover-icon">&#128214;</span>
          <div class="book-cover-accent"></div>
        </div>
        <div class="book-info">
          <div class="book-category"><?= htmlspecialchars($book['category']) ?></div>
          <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
          <div class="book-author"><?= htmlspecialchars($book['author']) ?></div>
          <div class="book-meta">
            <?php if ($book['available'] > 0): ?>
              <span class="badge badge-success"><?= $book['available'] ?> Available</span>
              <a href="request_borrow.php?book_id=<?= $book['id'] ?>" class="btn btn-primary btn-sm">Borrow</a>
            <?php else: ?>
              <span class="badge badge-danger">Unavailable</span>
              <button class="btn btn-outline btn-sm" disabled style="opacity:.5;cursor:not-allowed;">Borrow</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($current_page_num > 1): ?>
        <a href="?category=<?= urlencode($selected_category) ?>&page=<?= $current_page_num-1 ?>" class="page-btn">&#8592; Prev</a>
      <?php else: ?>
        <span class="page-btn disabled">&#8592; Prev</span>
      <?php endif; ?>

      <?php for ($i = max(1,$current_page_num-2); $i <= min($total_pages,$current_page_num+2); $i++): ?>
        <a href="?category=<?= urlencode($selected_category) ?>&page=<?= $i ?>" class="page-btn <?= $i===$current_page_num?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>

      <?php if ($current_page_num < $total_pages): ?>
        <a href="?category=<?= urlencode($selected_category) ?>&page=<?= $current_page_num+1 ?>" class="page-btn">Next &#8594;</a>
      <?php else: ?>
        <span class="page-btn disabled">Next &#8594;</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

  </main>

</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
  function checkMobile() {
    const toggle = document.getElementById('menuToggle');
    if (window.innerWidth <= 768) {
      toggle.style.display = 'flex';
    } else {
      toggle.style.display = 'none';
      document.getElementById('sidebar').classList.remove('open');
    }
  }
  checkMobile();
  window.addEventListener('resize', checkMobile);
  document.getElementById('menuToggle').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('open');
  });
  const currentPage = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-link').forEach(link => {
    link.classList.remove('active');
    const href = link.getAttribute('href');
    if (href === currentPage) {
      link.classList.add('active');
    }
  });
  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (type ? ' ' + type : '');
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }
</script>

</body>
</html>
