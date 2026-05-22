<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Books</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>

<body>

<div class="container">

    <?php include "sidebar.php"; ?>

    <main class="main-content">

        <div class="header">
            <h1>View Books</h1>
        </div>

        <div class="panel">

            <div class="panel-header">
                <h2>All Books</h2>
            </div>

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
                        <td>Stay Awake, Agatha</td>
                        <td>Josh</td>
                        <td>Fiction</td>
                        <td>1111</td>
                        <td>3</td>
                        <td>
                            <a href="edit_book.php?id=1">Edit</a> |
                            <a href="delete_book.php?id=1">Delete</a>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>

    </main>

  </div>

</body>
</html>