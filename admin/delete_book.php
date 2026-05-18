<?php session_start(); ?>

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