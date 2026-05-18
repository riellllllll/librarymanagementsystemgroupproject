<?php
session_start();

// Mock student data - in a real app, this would come from a database
$student = [
    'name' => 'Juan',
    'fullName' => 'Juan Dela Cruz',
    'accountType' => 'STUDENT',
    'books借Borrowed' => 2,
    'booksReturned' => 5,
    'pendingFines' => 20,
    'pendingRequests' => 1
];

$currentlyBorrowed = [
    [
        'title' => 'The Great Gatsby',
        'author' => 'F. Scott Fitzgerald',
        'dueDate' => 'May 25, 2026',
        'status' => 'ON TIME'
    ],
    [
        'title' => 'To Kill a Mockingbird',
        'author' => 'Harper Lee',
        'dueDate' => 'May 18, 2026',
        'status' => 'DUE TODAY'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CvSU Library System - Dashboard</title>
    <link rel="stylesheet" href="../assets/student.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-bars">|||</span>
                    <div class="logo-text">
                        <div class="logo-main">CvSU</div>
                        <div class="logo-sub">LIBRARY SYSTEM</div>
                    </div>
                </div>
            </div>

            <!-- Main Navigation -->
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">MAIN</div>
                    <ul class="nav-list">
                        <li class="nav-item active">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">⊞</span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-title">BOOKS</div>
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">📖</span>
                                <span class="nav-label">Browse Books</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">🔍</span>
                                <span class="nav-label">Search Books</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">↑</span>
                                <span class="nav-label">Request Borrow</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-title">MY LIBRARY</div>
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">📚</span>
                                <span class="nav-label">Borrowed Books</span>
                                <span class="badge">2</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">🔄</span>
                                <span class="nav-label">Borrow History</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">↩</span>
                                <span class="nav-label">Return a Book</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">💰</span>
                                <span class="nav-label">My Fines</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-title">ACCOUNT</div>
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="nav-icon">👤</span>
                                <span class="nav-label">My Profile</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar">JD</div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo $student['fullName']; ?></div>
                    <div class="profile-type"><?php echo $student['accountType']; ?></div>
                </div>
            </div>

            <!-- Logout -->
            <div class="logout-section">
                <a href="#" class="logout-btn">→ Log Out</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="header-title">Dashboard</h1>
                <div class="header-actions">
                    <div class="search-box">
                        <input type="text" placeholder="Search books..." class="search-input">
                        <span class="search-icon">🔍</span>
                    </div>
                    <button class="header-btn notification-btn">🔔</button>
                    <button class="header-btn profile-btn">👤</button>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Welcome Section -->
                <section class="welcome-section">
                    <h2 class="welcome-title">Welcome back, <span class="welcome-name"><?php echo $student['name']; ?></span></h2>
                    <p class="welcome-subtitle">Here's what's happening with your library account.</p>
                    <div class="divider"></div>
                </section>

                <!-- Stats Cards -->
                <section class="stats-section">
                    <div class="stat-card">
                        <div class="stat-icon books-borrowed">📖</div>
                        <div class="stat-number"><?php echo $student['books借Borrowed']; ?></div>
                        <div class="stat-label">BOOKS BORROWED</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon books-returned">⏰</div>
                        <div class="stat-number"><?php echo $student['booksReturned']; ?></div>
                        <div class="stat-label">BOOKS RETURNED</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon pending-fines">⚠</div>
                        <div class="stat-number">₱<?php echo $student['pendingFines']; ?></div>
                        <div class="stat-label">PENDING FINES</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon pending-requests">✓</div>
                        <div class="stat-number"><?php echo $student['pendingRequests']; ?></div>
                        <div class="stat-label">PENDING REQUESTS</div>
                    </div>
                </section>

                <!-- Currently Borrowed Section -->
                <section class="borrowed-section">
                    <div class="section-header">
                        <h3 class="section-title">Currently Borrowed</h3>
                        <p class="section-subtitle">Books you have checked out right now</p>
                    </div>

                    <table class="books-table">
                        <thead>
                            <tr>
                                <th class="col-title">TITLE</th>
                                <th class="col-author">AUTHOR</th>
                                <th class="col-due-date">DUE DATE</th>
                                <th class="col-status">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentlyBorrowed as $book): ?>
                            <tr>
                                <td class="col-title"><?php echo $book['title']; ?></td>
                                <td class="col-author"><?php echo $book['author']; ?></td>
                                <td class="col-due-date"><?php echo $book['dueDate']; ?></td>
                                <td class="col-status">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $book['status'])); ?>">
                                        <?php echo $book['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>
        </main>
    </div>
</body>
</html>