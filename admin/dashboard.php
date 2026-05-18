<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

    <!-- main -->
    <div class="container">

        <!-- sidebar -->
        <aside class="sidebar">

            <div class="logo">
                <h2>CvSU Library System</h2>
            </div>

            <hr>

            <!-- admin dashboard -->
            <div class="menu-section">
                <h4>MAIN</h4>

                <ul>
                    <li>
                        <a href="dashboard.php">Dashboard</a>
                    </li>
                </ul>
            </div>

            <!-- books -->
            <div class="menu-section">
                <h4>BOOKS</h4>

                <ul>
                    <li>
                        <a href="add_book.php">Add Book</a>
                    </li>

                    <li>
                        <a href="edit_book.php">Edit Book</a>
                    </li>

                    <!-- other functions -->
                    <li>
                        <a href="delete_book.php">Delete Book</a>
                    </li>

                    <li>
                        <a href="view_books.php">View Books</a>
                    </li>

                    <li>
                        <a href="borrowed_books.php">Borrowed Books</a>
                    </li>

                    <li>
                        <a href="issue_book.php">Issue Book</a>
                    </li>

                    <li>
                        <a href="return_book.php">Return Book</a>
                    </li>
                </ul>
            </div>

            <!-- students -->
            <div class="menu-section">
                <h4>STUDENTS</h4>

                <ul>
                    <li>
                        <a href="view_students.php">View Students</a>
                    </li>

                    <li>
                        <a href="manage_students.php">Manage Students</a>
                    </li>
                </ul>
            </div>

            <!-- view fines -->
            <div class="menu-section">
                <h4>FINES</h4>

                <ul>
                    <li>
                        <a href="view_fines.php">View Fines</a>
                    </li>
                </ul>
            </div>

            <hr>

            <!-- admin info -->
            <div class="admin-info">
                <p><strong>Admin Name</strong></p>
                <p>Administrator</p>
            </div>

            <div class="logout">
                <a href="logout.php">Log Out</a>
            </div>

        </aside>

        <!-- main -->
        <main class="main-content">

            <!-- admin dashboard -->
            <header>
                <h1>Dashboard</h1>

                <div class="top-actions">
                    <input type="text" placeholder="Search books...">
                </div>
            </header>

            <hr>

            <!-- Welcome Greetings -->
            <section class="welcome-section">
                <h2>Welcome back, Admin</h2>
            </section>

            <!-- display -->
            <section class="statistics">

                <div class="card">
                    <h3>Total Books</h3>
                    <p>0</p>
                </div>

                <div class="card">
                    <h3>Borrowed Books</h3>
                    <p>0</p>
                </div>

                <div class="card">
                    <h3>Registered Students</h3>
                    <p>0</p>
                </div>

                <div class="card">
                    <h3>Pending Fines</h3>
                    <p>₱0</p>
                </div>

            </section>

            <hr>

            <!-- recent books -->
            <section class="recent-books">

                <h2>Recently Borrowed Books</h2>

                <table border="1" cellpadding="10">

                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Student Name</th>
                            <th>Date Borrowed</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- sample book only -->
                        <tr>
                            <td>She's Dating a Gangster</td>
                            <td>Christine Joy Arenas</td>
                            <td>May 9, 2026</td>
                            <td>May 17, 2026</td>
                            <td>Borrowed</td>
                        </tr>

                    </tbody>

                </table>

            </section>

            <hr>

            <!-- quick actions -->
            <section class="quick-actions">

                <h2>Quick Actions</h2>

                <div class="actions">

                    <a href="add_book.php">
                        <button>Add Book</button>
                    </a>

                    <a href="issue_book.php">
                        <button>Issue Book</button>
                    </a>

                    <a href="return_book.php">
                        <button>Return Book</button>
                    </a>

                    <a href="view_students.php">
                        <button>View Students</button>
                    </a>

                </div>

            </section>

        </main>

    </div>

</body>
</html>