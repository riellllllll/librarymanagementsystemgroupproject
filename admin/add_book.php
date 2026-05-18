<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book</title>
</head>
<body>

    <div class="container">

        <!-- sidebar -->
        <aside class="sidebar">

            <h2>CvSU Library System</h2>

            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>

                <li><a href="add_book.php">Add Book</a></li>

                <li><a href="edit_book.php">Edit Book</a></li>

                <li><a href="delete_book.php">Delete Book</a></li>

                <li><a href="view_books.php">View Books</a></li>

                <li><a href="borrowed_books.php">Borrowed Books</a></li>

                <li><a href="issue_book.php">Issue Book</a></li>

                <li><a href="return_book.php">Return Book</a></li>

                <li><a href="view_students.php">View Students</a></li>

                <li><a href="manage_students.php">Manage Students</a></li>

                <li><a href="view_fines.php">View Fines</a></li>
            </ul>

        </aside>

        <!-- main -->
        <main class="main-content">

            <h1>Add New Book</h1>

            <hr>

            <form action="" method="POST">

                <div>
                    <label>Book Title</label><br>
                    <input type="text" name="book_title">
                </div>

                <br>

                <div>
                    <label>Author</label><br>
                    <input type="text" name="author">
                </div>

                <br>

                <div>
                    <label>Category</label><br>
                    <input type="text" name="category">
                </div>

                <br>

                <div>
                    <label>ISBN</label><br>
                    <input type="text" name="isbn">
                </div>

                <br>

                <div>
                    <label>Quantity</label><br>
                    <input type="number" name="quantity">
                </div>

                <br>

                <button type="submit" name="add_book">
                    Add Book
                </button>

            </form>

        </main>

    </div>

</body>
</html>