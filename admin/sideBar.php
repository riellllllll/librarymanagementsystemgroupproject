<?php
$current_page = basename($_SERVER['PHP_SELF']);
$request_badge = $pending_count ?? 0;
$archive_badge = isset($_SESSION['archived_books']) ? count($_SESSION['archived_books']) : 0;
?>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <svg viewBox="0 0 48 48" aria-hidden="true">
        <rect x="6" y="8" width="8" height="32" rx="1.5" fill="#c9973a"/>
        <rect x="16" y="10" width="6" height="30" rx="1.5" fill="#e8c26a"/>
        <rect x="24" y="6" width="10" height="36" rx="1.5" fill="#c9973a"/>
        <rect x="36" y="9" width="6" height="31" rx="1.5" fill="#a07830"/>
        <rect x="5" y="38" width="38" height="2.5" rx="1.25" fill="#7a6030"/>
      </svg>
    </div>

    <div>
      <h2>Cv<em>SU</em></h2>
      <div class="sidebar-subtitle">Admin Panel</div>
    </div>
  </div>

  <nav class="sidebar-nav" aria-label="Admin navigation">
    <div class="nav-section-label">Main</div>

    <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <div class="nav-section-label">Books</div>

    <a href="add_book.php" class="nav-link <?= $current_page === 'add_book.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M12 5v14M5 12h14"/>
      </svg>
      Add Book
    </a>

    <a href="edit_book.php" class="nav-link <?= $current_page === 'edit_book.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M12 20h9"/>
        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
      </svg>
      Edit Book
    </a>

    <a href="delete_book.php" class="nav-link <?= $current_page === 'delete_book.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <polyline points="3 6 5 6 21 6"/>
        <path d="M8 6V4h8v2"/>
        <path d="M19 6l-1 14H6L5 6"/>
      </svg>
      Delete Book
    </a>

    <a href="view_books.php" class="nav-link <?= $current_page === 'view_books.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      View Books
    </a>

    <a href="archive_books.php" class="nav-link <?= $current_page === 'archive_books.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <rect x="3" y="4" width="18" height="4" rx="1"/>
        <path d="M5 8v11h14V8"/>
        <path d="M10 12h4"/>
      </svg>
      Archive Books

      <?php if ($archive_badge > 0): ?>
        <span class="nav-badge"><?= $archive_badge ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section-label">Borrowing</div>

    <a href="student_req.php" class="nav-link <?= $current_page === 'student_req.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
        <path d="M8 9h8M8 13h5"/>
      </svg>
      Student Requests

      <?php if ($request_badge > 0): ?>
        <span class="nav-badge"><?= $request_badge ?></span>
      <?php endif; ?>
    </a>

    <a href="borrowed_books.php" class="nav-link <?= $current_page === 'borrowed_books.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
      Borrowed Books
    </a>

    <a href="issue_book.php" class="nav-link <?= $current_page === 'issue_book.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M12 5v14M5 12l7-7 7 7"/>
      </svg>
      Issue Book
    </a>

    <a href="return_book.php" class="nav-link <?= $current_page === 'return_book.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M9 14l-4-4 4-4"/>
        <path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
      </svg>
      Return Book
    </a>

    <div class="nav-section-label">Students</div>

    <a href="view_students.php" class="nav-link <?= $current_page === 'view_students.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
      </svg>
      Students
    </a>

    <a href="manage_students.php" class="nav-link <?= $current_page === 'manage_students.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06"/>
        <path d="M4.27 7.11l.06.06A1.65 1.65 0 0 0 6.15 7.5"/>
      </svg>
      Manage Students
    </a>

    <div class="nav-section-label">Fines</div>

    <a href="view_fines.php" class="nav-link <?= $current_page === 'view_fines.php' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      Fines
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="admin_profile.php" class="sidebar-user sidebar-user-link <?= $current_page === 'admin_profile.php' ? 'active' : '' ?>">
      <div class="user-avatar">AD</div>

      <div class="user-info">
        <div class="user-name">Admin</div>
        <div class="user-role">Administrator</div>
      </div>
    </a>

    <a href="logout.php" class="btn-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Log Out
    </a>
  </div>
</aside>