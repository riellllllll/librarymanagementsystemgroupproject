<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>

<body>

<div class="container">

    <?php include "sidebar.php"; ?>

    <main class="main-content">

        <div class="header">
            <h1>Dashboard</h1>
        </div>

        <div class="statistics">
            <div class="card"><h2>2</h2><p>Borrowed</p></div>
            <div class="card"><h2>5</h2><p>Returned</p></div>
            <div class="card"><h2>₱20</h2><p>Fines</p></div>
            <div class="card"><h2>1</h2><p>Requests</p></div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Recently Borrowed</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Student Name</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Sample Book</td>
                        <td>Christine</td>
                        <td>May 9</td>
                        <td>Borrowed</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="panel">

            <div class="panel-header">
                <h2>Quick Actions</h2>
            </div>

            <div class="actions">
                <a href="add_book.php">
                    <button type="button">Add Book</button>
                </a>

                <a href="issue_book.php">
                    <button type="button">Issue Book</button>
                </a>

                <a href="return_book.php">
                    <button type="button">Return Book</button>
                </a>
            </div>

        </div>

    </main>

</div>

</body>
</html>