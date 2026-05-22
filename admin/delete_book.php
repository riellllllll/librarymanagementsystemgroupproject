<?php
session_start();
require 'library_data.php';

$pending_count = count(array_filter($_SESSION['borrow_requests'], function ($req) {
  return $req['status'] === 'pending';
}));

$book_id = (int)($_GET['id'] ?? $_POST['book_id'] ?? 0);

$selected_book = null;

foreach ($_SESSION['books'] as $book) {

  if ((int)$book['id'] === $book_id) {
    $selected_book = $book;
    break;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_book) {

  foreach ($_SESSION['books'] as $index => $book) {

    if ((int)$book['id'] === $book_id) {

      $_SESSION['archived_books'][] = $book;

      unset($_SESSION['books'][$index]);

      $_SESSION['books'] = array_values($_SESSION['books']);

      header('Location: archive_books.php?deleted=1');
      exit;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Delete Book</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>

<body>

<div class="container">

    <?php include "sidebar.php"; ?>

    <main class="main-content">

        <div class="header">
            <h1>Delete Book</h1>
        </div>

        <div class="panel">

            <div class="panel-header">
                <h2>Delete Confirmation</h2>
            </div>

          <form method="POST">

                <div class="form-group">
                    <label>Book ID</label>
                    <input type="text" name="book_id">
                </div>

                <button type="submit">Delete</button>

                <a href="view_books.php">
                    <button type="button">Cancel</button>
                </a>

          </form>

        </div>

  </main>

  </div>

</body>
</html>