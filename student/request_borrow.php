<?php
// request_borrow.php
$active_page = 'request';

$all_books = [
  ['id'=>1, 'title'=>'The Great Gatsby',         'author'=>'F. Scott Fitzgerald','category'=>'Fiction',     'copies'=>3,'available'=>2,'color'=>'color-a'],
  ['id'=>2, 'title'=>'To Kill a Mockingbird',    'author'=>'Harper Lee',          'category'=>'Fiction',     'copies'=>4,'available'=>1,'color'=>'color-b'],
  ['id'=>3, 'title'=>'A Brief History of Time',  'author'=>'Stephen Hawking',     'category'=>'Science',     'copies'=>2,'available'=>2,'color'=>'color-c'],
  ['id'=>4, 'title'=>'Sapiens',                  'author'=>'Yuval Noah Harari',   'category'=>'History',     'copies'=>3,'available'=>0,'color'=>'color-d'],
  ['id'=>5, 'title'=>'Clean Code',               'author'=>'Robert C. Martin',    'category'=>'Technology',  'copies'=>5,'available'=>4,'color'=>'color-e'],
  ['id'=>6, 'title'=>'1984',                     'author'=>'George Orwell',       'category'=>'Fiction',     'copies'=>3,'available'=>2,'color'=>'color-a'],
  ['id'=>7, 'title'=>'The Selfish Gene',         'author'=>'Richard Dawkins',     'category'=>'Science',     'copies'=>2,'available'=>1,'color'=>'color-b'],
  ['id'=>8, 'title'=>'Calculus Made Easy',       'author'=>'Silvanus P. Thompson','category'=>'Mathematics', 'copies'=>4,'available'=>3,'color'=>'color-c'],
  ['id'=>9, 'title'=>'Design Patterns',          'author'=>'GoF',                 'category'=>'Technology',  'copies'=>3,'available'=>3,'color'=>'color-d'],
  ['id'=>10,'title'=>'Noli Me Tangere',          'author'=>'Jose Rizal',          'category'=>'Literature',  'copies'=>6,'available'=>5,'color'=>'color-e'],
  ['id'=>11,'title'=>'El Filibusterismo',        'author'=>'Jose Rizal',          'category'=>'Literature',  'copies'=>5,'available'=>4,'color'=>'color-a'],
  ['id'=>12,'title'=>'Guns, Germs, and Steel',   'author'=>'Jared Diamond',       'category'=>'History',     'copies'=>2,'available'=>2,'color'=>'color-b'],
  ['id'=>13,'title'=>'The Pragmatic Programmer', 'author'=>'Andrew Hunt',         'category'=>'Technology',  'copies'=>3,'available'=>2,'color'=>'color-c'],
  ['id'=>14,'title'=>'Pride and Prejudice',      'author'=>'Jane Austen',         'category'=>'Literature',  'copies'=>4,'available'=>3,'color'=>'color-d'],
  ['id'=>15,'title'=>'Cosmos',                   'author'=>'Carl Sagan',          'category'=>'Science',     'copies'=>3,'available'=>1,'color'=>'color-e'],
  ['id'=>16,'title'=>'The Art of War',           'author'=>'Sun Tzu',             'category'=>'History',     'copies'=>4,'available'=>4,'color'=>'color-a'],
];

$book_id = (int)($_GET['book_id'] ?? 0);
$selected_book = null;
foreach ($all_books as $b) {
  if ($b['id'] === $book_id) { $selected_book = $b; break; }
}

$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submitted = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Borrow — CvSU Library</title>
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
    <a href="view_books.php" class="nav-link ">
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
    <a href="request_borrow.php" class="nav-link active">
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
    <span class="topbar-title">Request Borrow</span>
    <div class="topbar-spacer"></div>
    <div class="topbar-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" placeholder="Search books…" onkeydown="if(event.key==='Enter') window.location='search_books.php?q='+this.value">
    </div>
    <a href="view_fines.php" class="topbar-icon-btn" title="Fines & Notifications">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <span class="topbar-notif-dot"></span>
    </a>
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
      <h1>Request to Borrow</h1>
      <p>Fill out the form below to request a book</p>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
    </div>

    <div class="form-container">
    <?php if ($submitted): ?>
    <div class="alert alert-sage" style="margin-bottom: 20px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Your borrow request has been submitted successfully! You will be notified once it is approved.
    </div>
    <?php endif; ?>

    <div class="request-card">
      <div class="card-body">
        <?php if ($selected_book): ?>
        <div class="book-preview">
          <div class="book-cover-mini">&#128214;</div>
          <div class="book-details">
            <h4><?= htmlspecialchars($selected_book['title']) ?></h4>
            <p>by <?= htmlspecialchars($selected_book['author']) ?> &middot; <?= htmlspecialchars($selected_book['category']) ?></p>
            <span class="badge badge-success"><?= $selected_book['available'] ?> of <?= $selected_book['copies'] ?> available</span>
          </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="request_borrow.php<?= $selected_book ? '?book_id='.$selected_book['id'] : '' ?>">
          <?php if (!$selected_book): ?>
          <div class="form-group">
            <label class="form-label">Select Book</label>
            <select name="book_id" class="form-control" required>
              <option value="">Choose a book...</option>
              <?php foreach ($all_books as $b): if ($b['available'] > 0): ?>
                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> — <?= htmlspecialchars($b['author']) ?></option>
              <?php endif; endforeach; ?>
            </select>
          </div>
          <?php else: ?>
          <input type="hidden" name="book_id" value="<?= $selected_book['id'] ?>">
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Borrow Date</label>
              <input type="date" name="borrow_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Return Date</label>
              <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Purpose / Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes for the librarian..."></textarea>
          </div>

          <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
              </svg>
              Submit Request
            </button>
            <a href="view_books.php" class="btn btn-outline">Cancel</a>
          </div>
        </form>
      </div>
    </div>
    </div>
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