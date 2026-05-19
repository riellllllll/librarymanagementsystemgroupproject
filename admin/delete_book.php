<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Book</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/student.css">
</head>

<body>

<?php include "sideBar.php"; ?>

<div class="main-wrapper">

<header class="topbar">
  <span class="topbar-title">Delete Book</span>
</header>

<main class="page-content">

<div class="page-header">
  <h1>Delete Book</h1>
  <div class="gold-rule"><span></span><i>✦</i><span></span></div>
</div>

<div class="card">
<div class="card-body">

<div class="card-title">Delete Confirmation</div>

<form method="POST">

<div class="field">
  <label>Book ID</label>
  <div class="input-wrap">
    <input type="text" name="book_id" required>
  </div>
</div>

<div style="display:flex; gap:10px;">
  <button class="btn-danger">Delete</button>
  <a href="view_books.php" class="btn-outline">Cancel</a>
</div>

</form>

</div>
</div>

</main>
</div>

</body>
</html>