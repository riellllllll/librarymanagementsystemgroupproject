<?php
/**
 * sidebar.php — Shared sidebar include for all student pages.
 *
 * Requires session_start() to already be called in the parent file.
 * Uses $_SESSION variables set at login:
 *   $_SESSION['student_name']    — e.g. "Juan Dela Cruz"
 *   $_SESSION['student_id']      — e.g. "2021-00123"
 *   $_SESSION['active_borrows']  — int count of currently borrowed books
 *   $_SESSION['has_fines']       — bool: true if any unpaid fines exist
 */

// ── Derive values from session ──────────────────────────────────────────────
$_s_name     = htmlspecialchars($_SESSION['student_name']  ?? 'Juan Dela Cruz');
$_s_borrows  = (int)($_SESSION['active_borrows']           ?? 0);

// Build initials from first + last word of name
$_s_parts    = explode(' ', trim($_SESSION['student_name'] ?? 'Juan Dela Cruz'));
$_s_initials = strtoupper(substr($_s_parts[0], 0, 1));
if (count($_s_parts) > 1) {
    $_s_initials .= strtoupper(substr(end($_s_parts), 0, 1));
}

// Current filename for active-link detection
$_s_page = basename($_SERVER['PHP_SELF']);

// Helper: returns ' active' if the given href matches the current page
function _nav_active(string $href, string $current): string {
    return $href === $current ? ' active' : '';
}
?>

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
    <a href="dashboard.php" class="nav-link<?= _nav_active('dashboard.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <div class="nav-section-label">Books</div>
    <a href="view_books.php" class="nav-link<?= _nav_active('view_books.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Browse Books
    </a>
    <a href="search_books.php" class="nav-link<?= _nav_active('search_books.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Search Books
    </a>
    <a href="request_borrow.php" class="nav-link<?= _nav_active('request_borrow.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 5v14M5 12l7-7 7 7"/>
      </svg>
      Request Borrow
    </a>

    <div class="nav-section-label">My Library</div>
    <a href="borrowed_books.php" class="nav-link<?= _nav_active('borrowed_books.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
      Borrowed Books
      <?php if ($_s_borrows > 0): ?>
        <span class="nav-badge"><?= $_s_borrows ?></span>
      <?php endif; ?>
    </a>
    <a href="borrow_history.php" class="nav-link<?= _nav_active('borrow_history.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/>
        <polyline points="3 3 3.05 11 11 10.94"/>
      </svg>
      Borrow History
    </a>
    <a href="return_book.php" class="nav-link<?= _nav_active('return_book.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
      </svg>
      Return a Book
    </a>
    <a href="view_fines.php" class="nav-link<?= _nav_active('view_fines.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      My Fines
    </a>

    <div class="nav-section-label">Account</div>
    <a href="profile.php" class="nav-link<?= _nav_active('profile.php', $_s_page) ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      My Profile
    </a>

  </nav>

  <!-- User info + Logout -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= $_s_initials ?></div>
      <div class="user-info">
        <div class="user-name"><?= $_s_name ?></div>
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