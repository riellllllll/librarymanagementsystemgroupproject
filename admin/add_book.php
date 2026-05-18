<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Book</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/student.css">

</head>
<body>

<?php include "sidebar.php"; ?>

<div class="main-wrapper">

<header class="topbar">
  <span class="topbar-title">Add Book</span>
</header>

<main class="page-content">

<div class="page-header">
  <h1>Add Book</h1>
  <div class="gold-rule"><span></span><i>✦</i><span></span></div>
</div>

<div class="card">
<div class="card-body">

<form method="POST">

<div class="field">
  <label>Title</label>
  <div class="input-wrap"><input type="text" name="title"></div>
</div>

<div class="field">
  <label>Author</label>
  <div class="input-wrap"><input type="text" name="author"></div>
</div>

<div class="field">
  <label>Category</label>
  <div class="input-wrap"><input type="text" name="category"></div>
</div>

<div class="field">
  <label>ISBN</label>
  <div class="input-wrap"><input type="text" name="isbn"></div>
</div>

<div class="field">
  <label>Quantity</label>
  <div class="input-wrap"><input type="number" name="quantity"></div>
</div>

<button class="btn-primary">Save Book</button>

</form>

</div>
</div>

</main>
</div>

</body>
</html>