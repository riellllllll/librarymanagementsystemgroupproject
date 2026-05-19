<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Return a Book — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/return_book.css">
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
    <a href="borrow_history.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/>
        <polyline points="3 3 3.05 11 11 10.94"/>
      </svg>
      Borrow History
    </a>
    <a href="return_book.php" class="nav-link active">
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
    <span class="topbar-title">Return a Book</span>
    <div class="topbar-spacer"></div>
    <div class="topbar-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" placeholder="Search books…">
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
      <h1>Return a <em style="font-style:italic;color:var(--gold)">Book</em></h1>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
      <p>Select the book you wish to return below.</p>
    </div>

    <!-- Alert for overdue -->
    <div class="alert alert-rust" style="margin-bottom:20px;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <span><strong>Overdue notice:</strong> "To Kill a Mockingbird" was due on May 18, 2026. A fine of ₱5/day will be applied upon return.</span>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 360px; gap: 20px; align-items: start;">

      <!-- LEFT: Book selection -->
      <div class="card">
        <div class="card-body">
          <div class="card-title">Your Borrowed Books</div>
          <div class="card-subtitle">Choose a book to return — click to select</div>

          <div class="return-list" id="returnList">

            <!-- Book 1 — on time -->
            <div class="return-book-item" id="item-1"
                 data-id="1"
                 data-title="The Great Gatsby"
                 data-author="F. Scott Fitzgerald"
                 data-due="May 25, 2026"
                 data-borrowed="May 10, 2026"
                 data-overdue="false"
                 data-fine="0">
              <div class="return-radio"></div>
              <div class="return-spine">📖</div>
              <div class="return-info">
                <div class="return-title">The Great Gatsby</div>
                <div class="return-author">F. Scott Fitzgerald</div>
                <div class="return-meta">
                  <span>Borrowed: <strong>May 10, 2026</strong></span>
                  <span>Due: <strong>May 25, 2026</strong></span>
                </div>
              </div>
              <div class="return-right">
                <span class="badge badge-sage">On Time</span>
              </div>
            </div>

            <!-- Book 2 — overdue -->
            <div class="return-book-item overdue" id="item-2"
                 data-id="2"
                 data-title="To Kill a Mockingbird"
                 data-author="Harper Lee"
                 data-due="May 18, 2026"
                 data-borrowed="May 3, 2026"
                 data-overdue="true"
                 data-fine="5">
              <div class="return-radio"></div>
              <div class="return-spine">📚</div>
              <div class="return-info">
                <div class="return-title">To Kill a Mockingbird</div>
                <div class="return-author">Harper Lee</div>
                <div class="return-meta">
                  <span>Borrowed: <strong>May 3, 2026</strong></span>
                  <span>Due: <strong style="color:var(--rust)">May 18, 2026</strong></span>
                </div>
                <div class="overdue-chip" style="margin-top:6px;">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                  Overdue — 1 day
                </div>
              </div>
              <div class="return-right">
                <span class="badge badge-rust">Overdue</span><br>
                <span style="font-size:0.75rem;color:var(--rust);margin-top:4px;display:block;">+₱5 fine</span>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- RIGHT: Confirm panel -->
      <div>
        <div class="confirm-panel" id="confirmPanel">
          <div class="confirm-panel-header">
            <h3>Confirm Return</h3>
            <p>Review the details before confirming.</p>
          </div>
          <div class="confirm-body">
            <div class="confirm-row">
              <span class="cr-label">Book</span>
              <span class="cr-val" id="cp-title">—</span>
            </div>
            <div class="confirm-row">
              <span class="cr-label">Author</span>
              <span class="cr-val" id="cp-author">—</span>
            </div>
            <div class="confirm-row">
              <span class="cr-label">Borrowed</span>
              <span class="cr-val" id="cp-borrowed">—</span>
            </div>
            <div class="confirm-row">
              <span class="cr-label">Due Date</span>
              <span class="cr-val" id="cp-due">—</span>
            </div>
            <div class="confirm-row">
              <span class="cr-label">Return Date</span>
              <span class="cr-val" id="cp-return" style="color:var(--gold-dk)">Today</span>
            </div>
            <div class="confirm-row" id="cp-fine-row">
              <span class="cr-label">Fine Incurred</span>
              <span class="cr-val bad" id="cp-fine">—</span>
            </div>
          </div>
          <div class="confirm-actions">
            <button class="btn-outline" id="btnCancel" style="flex:1;">Cancel</button>
            <button class="btn-primary" id="btnConfirm" style="flex:1;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
              </svg>
              Confirm Return
            </button>
          </div>
        </div>

        <!-- Placeholder when nothing selected -->
        <div class="no-borrow-hint" id="selectHint">
          <div class="nb-icon">👈</div>
          <h3>Select a book</h3>
          <p>Click on one of your borrowed books to see return details here.</p>
        </div>
      </div>

    </div>

  </main>
</div>


<!-- ── Return Success Modal ── -->
<div class="modal-backdrop" id="successModal">
  <div class="modal">
    <div class="modal-top"></div>
    <button class="modal-close" id="modalClose" aria-label="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
    <div class="modal-body">
      <div style="text-align:center;margin-bottom:18px;">
        <div style="font-size:2.8rem;margin-bottom:10px;">✅</div>
        <div class="modal-title" style="text-align:center;">Book Returned!</div>
        <div class="modal-desc" style="text-align:center;">Your return has been recorded. Here's your receipt.</div>
      </div>
      <div class="receipt-lines" id="receiptLines"></div>
      <div style="display:flex;gap:10px;margin-top:16px;">
        <a href="borrow_history.php" class="btn-outline" style="flex:1;text-align:center;">View History</a>
        <button class="btn-primary" style="flex:1;" id="modalCloseBtn">Done</button>
      </div>
    </div>
  </div>
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

  /* ── Book selection ── */
  let selectedItem = null;

  document.querySelectorAll('.return-book-item').forEach(item => {
    item.addEventListener('click', function() {
      document.querySelectorAll('.return-book-item').forEach(i => i.classList.remove('selected'));
      this.classList.add('selected');
      selectedItem = this;
      updateConfirmPanel(this);
    });
  });

  function updateConfirmPanel(item) {
    const isOverdue = item.dataset.overdue === 'true';
    const fine      = parseInt(item.dataset.fine);

    document.getElementById('cp-title').textContent   = item.dataset.title;
    document.getElementById('cp-author').textContent  = item.dataset.author;
    document.getElementById('cp-borrowed').textContent = item.dataset.borrowed;

    const dueEl = document.getElementById('cp-due');
    dueEl.textContent = item.dataset.due;
    dueEl.className   = isOverdue ? 'cr-val bad' : 'cr-val';

    const fineRow = document.getElementById('cp-fine-row');
    if (isOverdue && fine > 0) {
      fineRow.style.display = 'flex';
      document.getElementById('cp-fine').textContent = '₱' + fine + '/day';
    } else {
      fineRow.style.display = 'none';
    }

    document.getElementById('confirmPanel').classList.add('visible');
    document.getElementById('selectHint').style.display = 'none';
  }

  /* ── Cancel ── */
  document.getElementById('btnCancel').addEventListener('click', () => {
    document.querySelectorAll('.return-book-item').forEach(i => i.classList.remove('selected'));
    selectedItem = null;
    document.getElementById('confirmPanel').classList.remove('visible');
    document.getElementById('selectHint').style.display = 'block';
  });

  /* ── Confirm Return ── */
  document.getElementById('btnConfirm').addEventListener('click', () => {
    if (!selectedItem) return;
    const isOverdue = selectedItem.dataset.overdue === 'true';
    const fine      = parseInt(selectedItem.dataset.fine);
    const today     = new Date().toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });

    // Build receipt
    let lines = `
      <div class="receipt-line"><span class="rl-label">Book</span><span>${selectedItem.dataset.title}</span></div>
      <div class="receipt-line"><span class="rl-label">Author</span><span>${selectedItem.dataset.author}</span></div>
      <div class="receipt-line"><span class="rl-label">Return Date</span><span>${today}</span></div>
      <div class="receipt-line"><span class="rl-label">Status</span><span>${isOverdue ? '<span style="color:var(--rust)">Overdue</span>' : '<span style="color:var(--sage)">On Time</span>'}</span></div>
    `;
    if (isOverdue) {
      lines += `<div class="receipt-line"><span class="rl-label">Fine (per day)</span><span style="color:var(--rust)">₱${fine}</span></div>`;
    }

    document.getElementById('receiptLines').innerHTML = lines;

    const totalEl = document.getElementById('receiptLines');
    const totalDiv = document.createElement('div');
    totalDiv.className = 'receipt-total' + (isOverdue ? '' : ' no-fine');
    totalDiv.innerHTML = `<span>Total Fine Due</span><span>${isOverdue ? '₱' + fine + ' (to be settled)' : 'No fine — returned on time!'}</span>`;
    totalEl.appendChild(totalDiv);

    // Open modal
    document.getElementById('successModal').classList.add('open');

    // Remove item from list
    selectedItem.style.opacity = '0.4';
    selectedItem.style.pointerEvents = 'none';
    document.getElementById('confirmPanel').classList.remove('visible');
    document.getElementById('selectHint').style.display = 'block';
    selectedItem = null;
  });

  /* ── Close modal ── */
  ['modalClose', 'modalCloseBtn'].forEach(id => {
    document.getElementById(id).addEventListener('click', () =>
      document.getElementById('successModal').classList.remove('open')
    );
  });
  document.getElementById('successModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });

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