<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<!-- SAME FONT -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- USE SAME CSS -->
<link rel="stylesheet" href="../assets/student.css">

</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">

  <div class="sidebar-logo">
    <div class="logo-icon">
      <!-- SAME ICON -->
      <svg viewBox="0 0 48 48">
        <rect x="6" y="8" width="8" height="32" rx="1.5" fill="#c9973a"/>
        <rect x="16" y="10" width="6" height="30" rx="1.5" fill="#e8c26a"/>
        <rect x="24" y="6" width="10" height="36" rx="1.5" fill="#c9973a"/>
        <rect x="36" y="9" width="6" height="31" rx="1.5" fill="#a07830"/>
      </svg>
    </div>

    <div>
      <h2>Cv<em>SU</em></h2>
      <div class="sidebar-subtitle">Admin Panel</div>
    </div>
  </div>

  <nav class="sidebar-nav">

    <div class="nav-section-label">Main</div>
    <a href="dashboard.php" class="nav-link active">Dashboard</a>

    <div class="nav-section-label">Books</div>
    <a href="add_book.php" class="nav-link">Add Book</a>
    <a href="edit_book.php" class="nav-link">Edit Book</a>
    <a href="view_books.php" class="nav-link">View Books</a>
    <a href="delete_book.php" class="nav-link">Delete Book</a>

    <div class="nav-section-label">Borrowing</div>
    <a href="borrowed_books.php" class="nav-link">Borrowed Books</a>
    <a href="issue_book.php" class="nav-link">Issue Book</a>
    <a href="return_book.php" class="nav-link">Return Book</a>

    <div class="nav-section-label">Students</div>
    <a href="view_students.php" class="nav-link">View Students</a>

    <div class="nav-section-label">Fines</div>
    <a href="view_fines.php" class="nav-link">Fines</a>

  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">AD</div>
      <div class="user-info">
        <div class="user-name">Admin</div>
        <div class="user-role">Administrator</div>
      </div>
    </div>

    <a href="logout.php" class="btn-logout">Log Out</a>
  </div>

</aside>


<!-- MAIN -->
<div class="main-wrapper">

<header class="topbar">
  <span class="topbar-title">Admin Dashboard</span>
</header>

<main class="page-content">

<div class="page-header">
  <h1>Welcome, Admin</h1>
  <div class="gold-rule"><span></span><i>✦</i><span></span></div>
</div>

<!-- STATS -->
<div class="stats-grid">

  <div class="stat-card">
    <div class="stat-value">2</div>
    <div class="stat-label">Borrowed</div>
  </div>

  <div class="stat-card">
    <div class="stat-value">5</div>
    <div class="stat-label">Returned</div>
  </div>

  <div class="stat-card stat-danger">
    <div class="stat-value">₱20</div>
    <div class="stat-label">Fines</div>
  </div>

  <div class="stat-card stat-sage">
    <div class="stat-value">1</div>
    <div class="stat-label">Requests</div>
  </div>

</div>


<!-- TABLE -->
<div class="card">
  <div class="card-body">

    <div class="card-title">Recently Borrowed</div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Book</th>
            <th>Student</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>She's Dating the Gangster</td>
            <td>Christine</td>
            <td>May 9</td>
            <td><span class="badge badge-blue">Borrowed</span></td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</div>


<!-- ACTIONS -->
<div class="card" style="margin-top:20px;">
  <div class="card-body">

    <div class="card-title">Quick Actions</div>

    <a href="add_book.php" class="btn-primary">Add Book</a>
    <a href="issue_book.php" class="btn-outline">Issue Book</a>
    <a href="return_book.php" class="btn-danger">Return Book</a>

  </div>
</div>

</main>
</div>

</body>
</html>