<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Borrow History — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/borrow_history.css">
</head>
<body>
  
<!-- ============================================================
     SIDEBAR
     ============================================================ -->
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
    <a href="dashboard.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <div class="nav-section-label">Books</div>
    <a href="view_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Browse Books
    </a>
    <a href="search_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Search Books
    </a>
    <a href="request_borrow.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 5v14M5 12l7-7 7 7"/>
      </svg>
      Request Borrow
    </a>

    <div class="nav-section-label">My Library</div>
    <a href="borrowed_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
      Borrowed Books
      <span class="nav-badge">2</span>
    </a>
    <a href="borrow_history.php" class="nav-link active">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/>
        <polyline points="3 3 3.05 11 11 10.94"/>
      </svg>
      Borrow History
    </a>
    <a href="return_book.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
      </svg>
      Return a Book
    </a>
    <a href="view_fines.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      My Fines
    </a>

    <div class="nav-section-label">Account</div>
    <a href="profile.php" class="nav-link">
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


<!-- ============================================================
     MAIN WRAPPER
     ============================================================ -->
<div class="main-wrapper">

  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">Borrow History</span>
    <div class="topbar-spacer"></div>
    <div class="topbar-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" id="historySearch" placeholder="Search title or author…">
    </div>
    <a href="view_fines.php" class="topbar-icon-btn" title="Fines &amp; Notifications">
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


  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>Borrow <em style="font-style:italic;color:var(--gold)">History</em></h1>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
      <p>A complete record of all your borrowing activity.</p>
    </div>

    <!-- Summary Strip -->
    <div class="summary-strip">
      <div class="summary-item">
        <span class="s-val">7</span>
        <span class="s-lbl">Total Borrowed</span>
      </div>
      <div class="summary-item">
        <span class="s-val">5</span>
        <span class="s-lbl">Returned</span>
      </div>
      <div class="summary-item">
        <span class="s-val">2</span>
        <span class="s-lbl">Active</span>
      </div>
      <div class="summary-item">
        <span class="s-val" style="color:var(--rust)">1</span>
        <span class="s-lbl">With Fines</span>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <button class="filter-tab active" data-filter="all">
        All <span class="tab-count">7</span>
      </button>
      <button class="filter-tab" data-filter="active">
        Active <span class="tab-count">2</span>
      </button>
      <button class="filter-tab" data-filter="returned">
        Returned <span class="tab-count">5</span>
      </button>
      <button class="filter-tab" data-filter="overdue">
        Overdue / Fined <span class="tab-count">1</span>
      </button>
      <div class="filter-spacer"></div>
      <div class="filter-select-wrap">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <select id="yearFilter">
          <option value="">All Years</option>
          <option value="2026" selected>2026</option>
          <option value="2025">2025</option>
        </select>
      </div>
    </div>

    <!-- History Timeline -->
    <div class="history-timeline" id="historyList">

      <!-- ── May 2026 ── -->
      <div class="history-group" data-month="May 2026">
        <div class="history-group-label">May 2026</div>

        <!-- Active borrow -->
        <div class="history-entry" data-status="active" data-title="The Great Gatsby" data-author="F. Scott Fitzgerald">
          <div class="entry-dot dot-active"><span></span></div>
          <div class="entry-card">
            <div class="entry-book-spine">📖</div>
            <div class="entry-main">
              <div class="entry-title">The Great Gatsby</div>
              <div class="entry-author">F. Scott Fitzgerald</div>
              <div class="entry-dates">
                <span>Borrowed: <strong>May 10, 2026</strong></span>
                <span>Due: <strong>May 25, 2026</strong></span>
              </div>
            </div>
            <div class="entry-meta">
              <span class="badge badge-gold">Active</span>
            </div>
          </div>
        </div>

        <!-- Active borrow – due today -->
        <div class="history-entry" data-status="active" data-title="To Kill a Mockingbird" data-author="Harper Lee">
          <div class="entry-dot dot-active"><span></span></div>
          <div class="entry-card">
            <div class="entry-book-spine">📚</div>
            <div class="entry-main">
              <div class="entry-title">To Kill a Mockingbird</div>
              <div class="entry-author">Harper Lee</div>
              <div class="entry-dates">
                <span>Borrowed: <strong>May 3, 2026</strong></span>
                <span>Due: <strong style="color:var(--rust)">May 18, 2026</strong></span>
              </div>
            </div>
            <div class="entry-meta">
              <span class="badge badge-rust">Due Today</span>
            </div>
          </div>
        </div>

      </div>

      <!-- ── April 2026 ── -->
      <div class="history-group" data-month="April 2026">
        <div class="history-group-label">April 2026</div>

        <div class="history-entry" data-status="returned" data-title="1984" data-author="George Orwell">
          <div class="entry-dot dot-returned"><span></span></div>
          <div class="entry-card">
            <div class="entry-book-spine">📗</div>
            <div class="entry-main">
              <div class="entry-title">1984</div>
              <div class="entry-author">George Orwell</div>
              <div class="entry-dates">
                <span>Borrowed: <strong>Apr 5, 2026</strong></span>
                <span>Returned: <strong>Apr 19, 2026</strong></span>
              </div>
            </div>
            <div class="entry-meta">
              <span class="badge badge-sage">Returned</span>
            </div>
          </div>
        </div>

        <div class="history-entry" data-status="overdue" data-title="Brave New World" data-author="Aldous Huxley">
          <div class="entry-dot dot-overdue"><span></span></div>
          <div class="entry-card">
            <div class="entry-book-spine">📕</div>
            <div class="entry-main">
              <div class="entry-title">Brave New World</div>
              <div class="entry-author">Aldous Huxley</div>
              <div class="entry-dates">
                <span>Borrowed: <strong>Apr 1, 2026</strong></span>
                <span>Returned: <strong>Apr 22, 2026</strong></span>
                <span style="color:var(--rust)">4 days late</span>
              </div>
              <div class="fine-chip">₱20 fine — unpaid</div>
            </div>
            <div class="entry-meta">
              <span class="badge badge-rust">Overdue</span>
            </div>
          </div>
        </div>

      </div>

      <!-- ── March 2026 ── -->
      <div class="history-group" data-month="March 2026">
        <div class="history-group-label">March 2026</div>

        <div class="history-entry" data-status="returned" data-title="The Alchemist" data-author="Paulo Coelho">
          <div class="entry-dot dot-returned"><span></span></div>
          <div class="entry-card">
            <div class="entry-book-spine">📘</div>
            <div class="entry-main">
              <div class="entry-title">The Alchemist</div>
              <div class="entry-author">Paulo Coelho</div>
              <div class="entry-dates">
                <span>Borrowed: <strong>Mar 14, 2026</strong></span>
                <span>Returned: <strong>Mar 28, 2026</strong></span>
              </div>
            </div>
            <div class="entry-meta">
              <span class="badge badge-sage">Returned</span>
            </div>
          </div>
        </div>

        <div class="history-entry" data-status="returned" data-title="Of Mice and Men" data-author="John Steinbeck">
          <div class="entry-dot dot-returned"><span></span></div>
          <div class="entry-card">
            <div class="entry-book-spine">📙</div>
            <div class="entry-main">
              <div class="entry-title">Of Mice and Men</div>
              <div class="entry-author">John Steinbeck</div>
              <div class="entry-dates">
                <span>Borrowed: <strong>Mar 2, 2026</strong></span>
                <span>Returned: <strong>Mar 16, 2026</strong></span>
              </div>
            </div>
            <div class="entry-meta">
              <span class="badge badge-sage">Returned</span>
            </div>
          </div>
        </div>

      </div>

      <!-- ── February 2026 ── -->
      <div class="history-group" data-month="February 2026">
        <div class="history-group-label">February 2026</div>

        <div class="history-entry" data-status="returned" data-title="Animal Farm" data-author="George Orwell">
          <div class="entry-dot dot-returned"><span></span></div>
          <div class="entry-card">
            <div class="entry-book-spine">📗</div>
            <div class="entry-main">
              <div class="entry-title">Animal Farm</div>
              <div class="entry-author">George Orwell</div>
              <div class="entry-dates">
                <span>Borrowed: <strong>Feb 10, 2026</strong></span>
                <span>Returned: <strong>Feb 24, 2026</strong></span>
              </div>
              <div class="fine-chip paid">₱10 fine — paid</div>
            </div>
            <div class="entry-meta">
              <span class="badge badge-sage">Returned</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Empty state (hidden by default) -->
      <div class="empty-state" id="emptyState" style="display:none;">
        <div class="empty-icon">📭</div>
        <h3>No records found</h3>
        <p>Try adjusting your filters or search query.</p>
      </div>

    </div>
    <!-- end timeline -->

    <!-- Pagination -->
    <div class="pagination">
      <button class="page-btn" disabled>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
      </button>
      <button class="page-btn active">1</button>
      <button class="page-btn">2</button>
      <button class="page-btn">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>

  </main>
</div>

<div class="toast" id="toast"></div>

<script>
  /* ── Mobile ── */
  function checkMobile() {
    const t = document.getElementById('menuToggle');
    t.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    if (window.innerWidth > 768) document.getElementById('sidebar').classList.remove('open');
  }
  checkMobile();
  window.addEventListener('resize', checkMobile);
  document.getElementById('menuToggle').addEventListener('click', () =>
    document.getElementById('sidebar').classList.toggle('open')
  );

  /* ── Filter Tabs ── */
  const tabs = document.querySelectorAll('.filter-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      tabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      applyFilters();
    });
  });

  /* ── Search ── */
  document.getElementById('historySearch').addEventListener('input', applyFilters);

  function applyFilters() {
    const activeFilter = document.querySelector('.filter-tab.active')?.dataset.filter || 'all';
    const query = document.getElementById('historySearch').value.toLowerCase();
    const entries = document.querySelectorAll('.history-entry');
    let visibleCount = 0;

    entries.forEach(entry => {
      const status = entry.dataset.status;
      const title  = entry.dataset.title?.toLowerCase() || '';
      const author = entry.dataset.author?.toLowerCase() || '';
      const matchFilter = activeFilter === 'all' || status === activeFilter;
      const matchSearch = !query || title.includes(query) || author.includes(query);
      const show = matchFilter && matchSearch;
      entry.style.display = show ? 'flex' : 'none';
      if (show) visibleCount++;
    });

    /* Hide empty group headings */
    document.querySelectorAll('.history-group').forEach(group => {
      const visible = group.querySelectorAll('.history-entry[style=""],.history-entry:not([style])');
      const hasVisible = [...group.querySelectorAll('.history-entry')].some(e => e.style.display !== 'none');
      group.style.display = hasVisible ? '' : 'none';
    });

    document.getElementById('emptyState').style.display = visibleCount === 0 ? 'block' : 'none';
  }

  /* ── Toast ── */
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