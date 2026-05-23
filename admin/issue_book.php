<?php
// issue_book.php - Complete working version, no database required
session_start();

// Initialize demo data in session if not exists
if (!isset($_SESSION['demo_students'])) {
    $_SESSION['demo_students'] = [
        ['id' => 1, 'student_id' => '2021-0001', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'juan@cvsu.edu.ph', 'course' => 'BSIT', 'year_level' => '3rd Year'],
        ['id' => 2, 'student_id' => '2021-0002', 'first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria@cvsu.edu.ph', 'course' => 'BSCS', 'year_level' => '2nd Year'],
        ['id' => 3, 'student_id' => '2021-0003', 'first_name' => 'Pedro', 'last_name' => 'Penduko', 'email' => 'pedro@cvsu.edu.ph', 'course' => 'BSIS', 'year_level' => '4th Year'],
        ['id' => 4, 'student_id' => '2021-0004', 'first_name' => 'Ana', 'last_name' => 'Garcia', 'email' => 'ana@cvsu.edu.ph', 'course' => 'BSIT', 'year_level' => '1st Year'],
        ['id' => 5, 'student_id' => '2021-0005', 'first_name' => 'Jose', 'last_name' => 'Rizal', 'email' => 'jose@cvsu.edu.ph', 'course' => 'BSCS', 'year_level' => '3rd Year'],
    ];
}

if (!isset($_SESSION['demo_books'])) {
    $_SESSION['demo_books'] = [
        ['id' => 1, 'title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald', 'isbn' => '978-0743273565', 'category' => 'Fiction', 'publication_year' => '1925', 'copies_available' => 3, 'status' => 'active'],
        ['id' => 2, 'title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee', 'isbn' => '978-0061120084', 'category' => 'Fiction', 'publication_year' => '1960', 'copies_available' => 2, 'status' => 'active'],
        ['id' => 3, 'title' => '1984', 'author' => 'George Orwell', 'isbn' => '978-0451524935', 'category' => 'Dystopian', 'publication_year' => '1949', 'copies_available' => 5, 'status' => 'active'],
        ['id' => 4, 'title' => 'Pride and Prejudice', 'author' => 'Jane Austen', 'isbn' => '978-0141439518', 'category' => 'Romance', 'publication_year' => '1813', 'copies_available' => 1, 'status' => 'active'],
        ['id' => 5, 'title' => 'The Catcher in the Rye', 'author' => 'J.D. Salinger', 'isbn' => '978-0316769488', 'category' => 'Fiction', 'publication_year' => '1951', 'copies_available' => 4, 'status' => 'active'],
        ['id' => 6, 'title' => 'Harry Potter', 'author' => 'J.K. Rowling', 'isbn' => '978-0590353427', 'category' => 'Fantasy', 'publication_year' => '1997', 'copies_available' => 6, 'status' => 'active'],
        ['id' => 7, 'title' => 'Lord of the Rings', 'author' => 'J.R.R. Tolkien', 'isbn' => '978-0544003415', 'category' => 'Fantasy', 'publication_year' => '1954', 'copies_available' => 2, 'status' => 'active'],
        ['id' => 8, 'title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien', 'isbn' => '978-0547928227', 'category' => 'Fantasy', 'publication_year' => '1937', 'copies_available' => 3, 'status' => 'active'],
    ];
}

if (!isset($_SESSION['borrowings'])) {
    $_SESSION['borrowings'] = [];
}

$students = $_SESSION['demo_students'];
$books = $_SESSION['demo_books'];

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
    
    if ($student_id <= 0 || $book_id <= 0 || empty($due_date)) {
        $error = "All fields are required.";
    } else {
        // Find student
        $student = null;
        foreach ($students as $s) {
            if ((int)$s['id'] === $student_id) {
                $student = $s;
                break;
            }
        }
        
        // Find book
        $book = null;
        $bookIndex = -1;
        foreach ($books as $index => $b) {
            if ((int)$b['id'] === $book_id) {
                $book = $b;
                $bookIndex = $index;
                break;
            }
        }
        
        if (!$student) {
            $error = "Student not found.";
        } elseif (!$book) {
            $error = "Book not found.";
        } elseif ((int)$book['copies_available'] < 1) {
            $error = "No copies available.";
        } else {
            // Check if already borrowed
            $already_borrowed = false;
            foreach ($_SESSION['borrowings'] as $borrow) {
                if (isset($borrow['student_id']) && (int)$borrow['student_id'] === $student_id 
                    && isset($borrow['book_id']) && (int)$borrow['book_id'] === $book_id 
                    && isset($borrow['status']) && $borrow['status'] === 'borrowed') {
                    $already_borrowed = true;
                    break;
                }
            }
            
            if ($already_borrowed) {
                $error = "Student already has this book borrowed.";
            } else {
                // Add borrowing record
                $newId = count($_SESSION['borrowings']) > 0 ? max(array_column($_SESSION['borrowings'], 'id')) + 1 : 1;
                
                $new_borrow = [
                    'id' => $newId,
                    'student_id' => $student_id,
                    'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                    'student_sid' => $student['student_id'],
                    'book_id' => $book_id,
                    'book_title' => $book['title'],
                    'issue_date' => date('Y-m-d'),
                    'due_date' => $due_date,
                    'status' => 'borrowed',
                    'fine' => 0
                ];
                
                $_SESSION['borrowings'][] = $new_borrow;
                
                // Decrease book copies in session
                $_SESSION['demo_books'][$bookIndex]['copies_available'] = (int)$_SESSION['demo_books'][$bookIndex]['copies_available'] - 1;
                
                $success = "Book '" . htmlspecialchars($book['title']) . "' issued to " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "!";
            }
        }
    }
    
    // Refresh data after changes
    $students = $_SESSION['demo_students'];
    $books = $_SESSION['demo_books'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Book - CvSU Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; color: #333; }
        .container { display: flex; min-height: 100vh; }
        
        /* Sidebar - matches your screenshot */
        .sidebar {
            width: 260px; background: #1a2332; color: #a0aec0;
            position: fixed; height: 100vh; overflow-y: auto;
        }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #2d3748; }
        .sidebar-header h1 { color: #fff; font-size: 18px; font-weight: 700; letter-spacing: 1px; }
        .sidebar-header p { font-size: 11px; color: #718096; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
        
        .nav-section { margin-top: 20px; }
        .nav-section-title { padding: 10px 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #718096; }
        .nav-item { display: flex; align-items: center; padding: 12px 20px; color: #a0aec0; text-decoration: none; font-size: 14px; transition: all 0.3s ease; }
        .nav-item:hover { background: #2d3748; color: #fff; }
        .nav-item.active { background: #d4a843; color: #1a2332; font-weight: 600; }
        .nav-item i { width: 20px; margin-right: 12px; font-size: 14px; }
        .nav-item .badge { margin-left: auto; background: #e53e3e; color: white; font-size: 11px; padding: 2px 6px; border-radius: 10px; }
        
        .sidebar-footer { margin-top: auto; padding: 20px; border-top: 1px solid #2d3748; }
        .admin-profile { display: flex; align-items: center; margin-bottom: 15px; }
        .admin-avatar { width: 36px; height: 36px; background: #d4a843; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #1a2332; font-weight: 700; font-size: 14px; margin-right: 12px; }
        .admin-info h4 { color: #fff; font-size: 13px; font-weight: 600; }
        .admin-info p { color: #718096; font-size: 11px; }
        .logout-btn { width: 100%; padding: 10px; background: transparent; border: 1px solid #4a5568; color: #a0aec0; border-radius: 6px; cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; }
        .logout-btn:hover { background: #e53e3e; border-color: #e53e3e; color: #fff; }
        
        /* Main Content */
        .main-content { margin-left: 260px; flex: 1; padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h2 { font-size: 24px; color: #1a2332; font-weight: 700; }
        .header-actions { display: flex; gap: 15px; }
        .icon-btn { width: 40px; height: 40px; border-radius: 8px; border: none; background: white; color: #718096; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1); font-size: 16px; display: flex; align-items: center; justify-content: center; }
        .icon-btn:hover { background: #d4a843; color: #1a2332; }
        
        /* Alerts */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; }
        .alert-success { background: #f0fff4; border: 1px solid #9ae6b4; color: #22543d; }
        .alert-error { background: #fff5f5; border: 1px solid #feb2b2; color: #c53030; }
        
        /* Form Card */
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); max-width: 800px; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; background: #fafafa; }
        .card-header h3 { font-size: 18px; color: #1a2332; font-weight: 600; }
        .card-body { padding: 30px; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group label .required { color: #e53e3e; margin-left: 2px; }
        .form-control { padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #2d3748; background: #fff; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: #d4a843; box-shadow: 0 0 0 3px rgba(212,168,67,0.1); }
        select.form-control { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23718096' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 15px center; padding-right: 40px; }
        
        .info-card { margin-top: 10px; padding: 15px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: none; font-size: 13px; color: #4a5568; line-height: 1.6; }
        .info-card.active { display: block; }
        .info-card strong { color: #2d3748; }
        
        .hint { margin-top: 10px; padding: 12px; background: #fffaf0; border: 1px solid #fbd38d; border-radius: 8px; font-size: 13px; color: #744210; display: flex; align-items: flex-start; gap: 10px; }
        .hint i { color: #d4a843; margin-top: 2px; }
        
        .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; padding-top: 25px; border-top: 1px solid #e2e8f0; }
        .btn { padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
        .btn-secondary { background: #edf2f7; color: #4a5568; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-primary { background: #d4a843; color: #1a2332; }
        .btn-primary:hover { background: #c49a3a; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>CvSU</h1>
                <p>Admin Panel</p>
            </div>
            <nav>
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-bar"></i>Dashboard</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Books</div>
                    <a href="add_book.php" class="nav-item"><i class="fas fa-plus"></i>Add Book</a>
                    <a href="edit_book.php" class="nav-item"><i class="fas fa-pen"></i>Edit Book</a>
                    <a href="delete_book.php" class="nav-item"><i class="fas fa-trash"></i>Delete Book</a>
                    <a href="view_books.php" class="nav-item"><i class="fas fa-book"></i>View Books</a>
                    <a href="archive_books.php" class="nav-item"><i class="fas fa-archive"></i>Archive Books</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Borrowing</div>
                    <a href="student_requests.php" class="nav-item"><i class="fas fa-clipboard-list"></i>Student Requests <span class="badge">2</span></a>
                    <a href="borrowed_books.php" class="nav-item"><i class="fas fa-book-reader"></i>Borrowed Books</a>
                    <a href="issue_book.php" class="nav-item active"><i class="fas fa-hand-holding"></i>Issue Book</a>
                    <a href="return_book.php" class="nav-item"><i class="fas fa-undo"></i>Return Book</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Students</div>
                    <a href="students.php" class="nav-item"><i class="fas fa-users"></i>Students</a>
                    <a href="manage_students.php" class="nav-item"><i class="fas fa-user-cog"></i>Manage Students</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Fines</div>
                    <a href="fines.php" class="nav-item"><i class="fas fa-coins"></i>Fines</a>
                </div>
            </nav>
            <div class="sidebar-footer">
                <div class="admin-profile">
                    <div class="admin-avatar">AD</div>
                    <div class="admin-info">
                        <h4>Admin</h4>
                        <p>Administrator</p>
                    </div>
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Log Out</button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <h2>Issue Book</h2>
                </div>
                <div class="header-actions">
                    <button class="icon-btn"><i class="fas fa-bell"></i></button>
                    <button class="icon-btn"><i class="fas fa-cog"></i></button>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Assign Book to Student</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-row">
                            <!-- Student Selection -->
                            <div class="form-group">
                                <label>Student <span class="required">*</span></label>
                                <select name="student_id" class="form-control" id="studentSelect" required>
                                    <option value="">-- Select Student --</option>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?php echo (int)$s['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?>"
                                            data-sid="<?php echo htmlspecialchars($s['student_id']); ?>"
                                            data-email="<?php echo htmlspecialchars($s['email']); ?>"
                                            data-course="<?php echo htmlspecialchars($s['course']); ?>"
                                            data-year="<?php echo htmlspecialchars($s['year_level']); ?>">
                                            <?php echo htmlspecialchars($s['last_name'] . ', ' . $s['first_name'] . ' (' . $s['student_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="info-card" id="studentInfo"></div>
                            </div>
                            
                            <!-- Book Selection -->
                            <div class="form-group">
                                <label>Book <span class="required">*</span></label>
                                <select name="book_id" class="form-control" id="bookSelect" required>
                                    <option value="">-- Select Book --</option>
                                    <?php foreach ($books as $b): ?>
                                        <option value="<?php echo (int)$b['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($b['title']); ?>"
                                            data-author="<?php echo htmlspecialchars($b['author']); ?>"
                                            data-isbn="<?php echo htmlspecialchars($b['isbn']); ?>"
                                            data-copies="<?php echo (int)$b['copies_available']; ?>"
                                            data-category="<?php echo htmlspecialchars($b['category']); ?>">
                                            <?php echo htmlspecialchars($b['title'] . ' (' . $b['copies_available'] . ' left)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="info-card" id="bookInfo"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <!-- Due Date -->
                            <div class="form-group">
                                <label>Due Date <span class="required">*</span></label>
                                <input type="date" name="due_date" class="form-control" id="dueDate" required 
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                <div class="hint"><i class="fas fa-info-circle"></i> Student must return by this date. Late returns: PHP 5/day.</div>
                            </div>
                            
                            <!-- Issue Date (Read-only) -->
                            <div class="form-group">
                                <label>Issue Date</label>
                                <input type="text" class="form-control" value="<?php echo date('F d, Y'); ?>" readonly style="background:#f7fafc;color:#718096;">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Issue Book</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('studentSelect').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const info = document.getElementById('studentInfo');
            if (this.value) {
                info.innerHTML = '<strong>Name:</strong> ' + opt.dataset.name + '<br><strong>ID:</strong> ' + opt.dataset.sid + '<br><strong>Email:</strong> ' + opt.dataset.email + '<br><strong>Course:</strong> ' + opt.dataset.course + '<br><strong>Year:</strong> ' + opt.dataset.year;
                info.classList.add('active');
            } else {
                info.classList.remove('active');
            }
        });

        document.getElementById('bookSelect').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const info = document.getElementById('bookInfo');
            if (this.value) {
                info.innerHTML = '<strong>Title:</strong> ' + opt.dataset.title + '<br><strong>Author:</strong> ' + opt.dataset.author + '<br><strong>ISBN:</strong> ' + opt.dataset.isbn + '<br><strong>Category:</strong> ' + opt.dataset.category + '<br><strong>Available:</strong> ' + opt.dataset.copies + ' copies';
                info.classList.add('active');
            } else {
                info.classList.remove('active');
            }
        });

        // Set default due date to 7 days from now
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        document.getElementById('dueDate').value = nextWeek.toISOString().split('T')[0];
    </script>
</body>
</html>