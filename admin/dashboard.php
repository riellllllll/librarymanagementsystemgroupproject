<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/student.css">

</head>
<body>

<?php include "sidebar.php"; ?>

<div class="main-wrapper">

<header class="topbar">
  <span class="topbar-title">Dashboard</span>
</header>

<main class="page-content">

<div class="page-header">
  <h1>Admin Dashboard</h1>
  <div class="gold-rule"><span></span><i>✦</i><span></span></div>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-value">2</div><div class="stat-label">Borrowed</div></div>
  <div class="stat-card"><div class="stat-value">5</div><div class="stat-label">Returned</div></div>
  <div class="stat-card stat-danger"><div class="stat-value">₱20</div><div class="stat-label">Fines</div></div>
  <div class="stat-card stat-sage"><div class="stat-value">1</div><div class="stat-label">Requests</div></div>
</div>

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
  <td>Sample</td>
  <td>Christine</td>
  <td>May 9</td>
  <td><span class="badge badge-blue">Borrowed</span></td>
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