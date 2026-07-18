<?php
// search_books.php
session_start();
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../classes/Book.php';

$active_page = 'search';

$bookObj = new Book($conn);

// Load from DB
$db_books = $bookObj->getAll();
$colors   = ['color-a','color-b','color-c','color-d','color-e'];
$all_books = [];
foreach ($db_books as $i => $b) {
    $all_books[] = [
        'id'        => (int)$b['id'],
        'title'     => $b['title'],
        'author'    => $b['author'],
        'category'  => $b['category'],
        'year'      => 0,  // year not in DB, kept for UI compatibility
        'copies'    => (int)$b['total_copies'],
        'available' => (int)$b['copies_available'],
        'color'     => $colors[$i % count($colors)],
    ];
}

$categories = ['All','Fiction','Science','History','Technology','Literature','Mathematics'];

$q        = trim($_GET['q']        ?? '');
$cat      = trim($_GET['category'] ?? 'All');
$filter   = trim($_GET['filter']   ?? 'all');
$searched = $q !== '' || $cat !== 'All' || $filter !== 'all';

function books_match(array $book, string $q, string $cat, string $filter): bool {
  if ($cat !== 'All' && $book['category'] !== $cat) return false;
  if ($filter === 'available' && $book['available'] <= 0) return false;
  if ($q === '') return true;
  $q = mb_strtolower($q);
  return str_contains(mb_strtolower($book['title']),    $q)
      || str_contains(mb_strtolower($book['author']),   $q)
      || str_contains(mb_strtolower($book['category']), $q);
}

$results = $searched
  ? array_values(array_filter($all_books, fn($b) => books_match($b, $q, $cat, $filter)))
  : [];

function hl(string $text, string $q): string {
  if ($q === '') return htmlspecialchars($text);
  return preg_replace('/('.preg_quote(htmlspecialchars($q),'/').')/i',
    '<span class="highlight">$1</span>', htmlspecialchars($text));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Books — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php require_once "../includes/sidebar.php"; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">

  <!-- TOP BAR -->
  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">Search Books</span>
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
      <h1>Search Books</h1>
      <p>Find books by title, author, category, or year</p>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
    </div>

    <!-- Search Form -->
    <form method="GET" action="search_books.php">
      <div class="search-card">
        <div class="search-card-header">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <span>Search Filters</span>
        </div>
        <div class="search-bar">
        <div class="form-group search-query">
          <label class="form-label">Search Query</label>
          <div class="search-input-wrap">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="q" class="form-control" placeholder="Title, author, category, year..." value="<?= htmlspecialchars($q) ?>">
          </div>
        </div>

        <div class="form-group search-category">
          <label class="form-label">Category</label>
          <select name="category" class="form-control">
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c ?>" <?= $c===$cat?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group search-availability">
          <label class="form-label">Availability</label>
          <select name="filter" class="form-control">
            <option value="all"       <?= $filter==='all'?'selected':'' ?>>All Books</option>
            <option value="available" <?= $filter==='available'?'selected':'' ?>>Available Only</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary" style="height:40px;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          Search
        </button>
        <?php if ($searched): ?>
        <a href="search_books.php" class="btn btn-outline" style="height:40px;">Clear</a>
        <?php endif; ?>
      </div>
      </div>
    </form>

    <?php if (!$searched): ?>
    <div class="card">
      <div class="empty-state">
        <div class="empty-icon">&#128269;</div>
        <h3>Start your search</h3>
        <p>Enter a title, author name, category, or publication year above.</p>
      </div>
    </div>

    <?php elseif (empty($results)): ?>
    <div class="card">
      <div class="empty-state">
        <div class="empty-icon">&#128533;</div>
        <h3>No results found</h3>
        <p>Try a different keyword or adjust your filters.</p>
      </div>
    </div>

    <?php else: ?>
    <p class="result-count">
      Found <strong><?= count($results) ?></strong>
      <?= count($results)===1?'book':'books' ?>
      <?= $q !== '' ? 'for "<strong>'.htmlspecialchars($q).'</strong>"' : '' ?>
      <?= $cat !== 'All' ? 'in <strong>'.htmlspecialchars($cat).'</strong>' : '' ?>
    </p>

    <div class="card" style="padding:0; overflow:hidden;">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Author</th>
              <th>Category</th>
              <th>Year</th>
              <th>Copies</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $book): ?>
            <tr>
              <td>
                <div style="font-weight:600; font-size:13px;"><?= hl($book['title'],$q) ?></div>
              </td>
              <td><?= hl($book['author'],$q) ?></td>
              <td>
                <span class="badge badge-info"><?= htmlspecialchars($book['category']) ?></span>
              </td>
              <td class="text-muted"><?= $book['year'] ?></td>
              <td>
                <span style="font-size:12px;">
                  <strong><?= $book['available'] ?></strong>
                  <span class="text-muted">/ <?= $book['copies'] ?></span>
                </span>
              </td>
              <td>
                <?php if ($book['available'] > 0): ?>
                  <span class="badge badge-success">Available</span>
                <?php else: ?>
                  <span class="badge badge-danger">Unavailable</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($book['available'] > 0): ?>
                  <a href="request_borrow.php?book_id=<?= $book['id'] ?>" class="btn btn-primary btn-sm">Borrow</a>
                <?php else: ?>
                  <button class="btn btn-outline btn-sm" disabled style="opacity:.5;cursor:not-allowed;">Borrow</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
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