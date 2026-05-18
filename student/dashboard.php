<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — CvSU Library</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Student CSS -->
  <link rel="stylesheet" href="../assets/student.css">
</head>
<body>

<!-- ============================================================
     SIDEBAR
     ============================================================ -->
<aside class="sidebar" id="sidebar">

  <!-- Logo -->
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

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <div class="nav-section-label">Main</div>

    <!-- Dashboard -->
    <a href="dashboard.php" class="nav-link active">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <div class="nav-section-label">Books</div>

    <!-- View Books -->
    <a href="view_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Browse Books
    </a>

    <!-- Search Books -->
    <a href="search_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Search Books
    </a>

    <!-- Request Borrow -->
    <a href="request_borrow.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 5v14M5 12l7-7 7 7"/>
      </svg>
      Request Borrow
    </a>

    <div class="nav-section-label">My Library</div>

    <!-- Borrowed Books -->
    <a href="borrowed_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
      Borrowed Books
      <!-- Remove the nav-badge below if student has no active borrows -->
      <span class="nav-badge">2</span>
    </a>

    <!-- Borrow History -->
    <a href="borrow_history.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/>
        <polyline points="3 3 3.05 11 11 10.94"/>
      </svg>
      Borrow History
    </a>

    <!-- Return Book -->
    <a href="return_book.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
      </svg>
      Return a Book
    </a>

    <!-- View Fines -->
    <a href="view_fines.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      My Fines
    </a>

    <div class="nav-section-label">Account</div>

    <!-- Profile -->
    <a href="profile.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      My Profile
    </a>

  </nav>

  <!-- User info + Logout -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <!-- Replace "JD" and the name/id below with PHP session values -->
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


<!-- ============================================================
     MAIN WRAPPER
     ============================================================ -->
<div class="main-wrapper">

  <!-- TOP BAR -->
  <header class="topbar">
    <!-- Mobile hamburger (shows on small screens) -->
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>

    <!-- Page title — change this per page -->
    <span class="topbar-title">Dashboard</span>

    <div class="topbar-spacer"></div>

    <!-- Search bar -->
    <div class="topbar-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" placeholder="Search books…">
    </div>

    <!-- Notifications -->
    <a href="view_fines.php" class="topbar-icon-btn" title="Fines & Notifications">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <!-- Remove dot below if no active fines/notifications -->
      <span class="topbar-notif-dot"></span>
    </a>

    <!-- Profile shortcut -->
    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>


  <!-- PAGE CONTENT — members put their page content below this line -->
  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>Welcome back, <em style="font-style:italic;color:var(--gold)">Juan</em></h1>
      <div class="gold-rule">
        <span></span><i>✦</i><span></span>
      </div>
    </div>

    <!--
    ================================================================
      YOUR PAGE CONTENT GOES HERE
      Example placeholders below so you can see the layout working.
      Delete everything inside this comment block and replace with
      the actual page content for each .php file.
    ================================================================
    -->

    <!-- Example: Stat cards (dashboard.php would use these) -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
          </svg>
        </div>
        <div class="stat-value">2</div>
        <div class="stat-label">Books Borrowed</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="12 8 12 12 14 14"/>
            <path d="M3.05 11a9 9 0 1 0 .5-4"/>
            <polyline points="3 3 3.05 11 11 10.94"/>
          </svg>
        </div>
        <div class="stat-value">5</div>
        <div class="stat-label">Books Returned</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-danger">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <div class="stat-value">₱20</div>
        <div class="stat-label">Pending Fines</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-sage">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="stat-value">1</div>
        <div class="stat-label">Pending Requests</div>
      </div>
    </div>

    <!-- Example: a card with a table (borrowed_books.php would use this) -->
    <div class="card" style="margin-top: 8px;">
      <div class="card-body">
        <div class="card-title">Currently Borrowed</div>
        <div class="card-subtitle">Books you have checked out right now</div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Due Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>The Great Gatsby</td>
                <td>F. Scott Fitzgerald</td>
                <td>May 25, 2026</td>
                <td><span class="badge badge-sage">On Time</span></td>
              </tr>
              <tr>
                <td>To Kill a Mockingbird</td>
                <td>Harper Lee</td>
                <td>May 18, 2026</td>
                <td><span class="badge badge-rust">Due Today</span></td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
    <!--
    ================================================================
      END OF EXAMPLE CONTENT
    ================================================================
    -->

  </main>
  <!-- end .page-content -->

</div>
<!-- end .main-wrapper -->


<!-- Toast (for success/error messages) -->
<div class="toast" id="toast"></div>


<!-- ============================================================
     JS — Sidebar mobile toggle + active nav link
     ============================================================ -->
<script>
  // ── Mobile: show hamburger button on small screens ──
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

  // ── Mobile: toggle sidebar open/close ──
  document.getElementById('menuToggle').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('open');
  });

  // ── Auto-highlight the active nav link based on current page ──
  const currentPage = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-link').forEach(link => {
    link.classList.remove('active');
    const href = link.getAttribute('href');
    if (href === currentPage) {
      link.classList.add('active');
    }
  });

  // ── Toast helper (members can call this anywhere) ──
  // Usage: showToast('Book returned!', 'success')
  //        showToast('Something went wrong.', 'error')
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