<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>

<body>

<div class="container">

    <?php include "sidebar.php"; ?>

    <main class="main-content">

        <div class="header">
            <h1>Edit Book</h1>
        </div>

        <div class="panel form-panel">

            <div class="panel-header">
                <h2>Edit Book</h2>
            </div>

            <form method="POST">

                <div class="form-group">
                    <label>Book ID</label>
                    <input type="text" name="book_id">
                </div>

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title">
                </div>

                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author">
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category">
                </div>

                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn">
                </div>

                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity">
                </div>

                <button type="submit">Update Book</button>

            </form>

        </div>

    </main>

</div>

</body>
</html>