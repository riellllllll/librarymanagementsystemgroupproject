<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Books</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/student.css">
</head>

<body>

<?php include "sideBar.php"; ?>

<div class="main-wrapper">

<header class="topbar">
  <span class="topbar-title">View Books</span>
</header>

<main class="page-content">

<div class="page-header">
  <h1>All Books</h1>
  <div class="gold-rule"><span></span><i>✦</i><span></span></div>
</div>

<div class="card">
<div class="card-body">

<div class="card-title">Library Collection</div>

<div class="table-wrap">
<table>

<thead>
<tr>
  <th>ID</th>
  <th>Title</th>
  <th>Author</th>
  <th>Category</th>
  <th>ISBN</th>
  <th>Qty</th>
  <th>Action</th>
</tr>
</thead>

<tbody>
<tr>
  <td>1</td>
  <td>Sample</td>
  <td>John</td>
  <td>Fiction</td>
  <td>12345</td>
  <td>3</td>
  <td>
    <a href="edit_book.php?id=1" class="btn-outline">Edit</a>
    <a href="delete_book.php?id=1" class="btn-danger">Delete</a>
  </td>
</tr>
</tbody>

</table>
</div>

</div>
</div>

</main>
</div>

</body>
</html>