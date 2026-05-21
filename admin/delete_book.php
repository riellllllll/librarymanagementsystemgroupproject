<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Delete Book</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/admin-extra.css">
</head>

<body>

  <?php include "sidebar.php"; ?>

  <div class="main-wrapper">

    <!-- TOP BAR -->
    <header class="topbar">
      <span class="topbar-title">Delete Book</span>

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
        <h1>Delete Book</h1>

        <div class="gold-rule">
          <span></span>
          <i>✦</i>
          <span></span>
        </div>
      </div>

      <!-- DELETE CONFIRMATION CARD -->
      <div class="card">
        <div class="card-body">

          <div class="card-title">Delete Confirmation</div>

          <form method="POST">

            <div class="field">
              <label>Book ID</label>
              <div class="input-wrap">
                <input class="no-icon" type="text" name="book_id">
              </div>
            </div>

            <div class="admin-actions">
              <button type="submit" class="btn-danger">Delete</button>
              <a href="view_books.php" class="btn-outline">Cancel</a>
            </div>

          </form>

        </div>
      </div>

    </main>

  </div>

</body>
</html>