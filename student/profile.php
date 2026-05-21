<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile — CvSU Library</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/profile.css">
</head>
<body>

<!-- ============================================================
     SIDEBAR
     ============================================================ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="6"  y="8"  width="8"  height="32" rx="1.5" fill="#c9973a"/>
        <rect x="16" y="10" width="6"  height="30" rx="1.5" fill="#e8c26a"/>
        <rect x="24" y="6"  width="10" height="36" rx="1.5" fill="#c9973a"/>
        <rect x="36" y="9"  width="6"  height="31" rx="1.5" fill="#a07830"/>
        <rect x="5"  y="38" width="38" height="2.5" rx="1.25" fill="#7a6030"/>
      </svg>
    </div>
    <div>
      <h2>Cv<em>SU</em></h2>
      <div class="sidebar-subtitle">Library System</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="dashboard.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <div class="nav-section-label">Books</div>
    <a href="view_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Browse Books
    </a>
    <a href="search_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Search Books
    </a>
    <a href="request_borrow.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 5v14M5 12l7-7 7 7"/>
      </svg>
      Request Borrow
    </a>

    <div class="nav-section-label">My Library</div>
    <a href="borrowed_books.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
      Borrowed Books
      <span class="nav-badge">2</span>
    </a>
    <a href="borrow_history.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/>
        <polyline points="3 3 3.05 11 11 10.94"/>
      </svg>
      Borrow History
    </a>
    <a href="return_book.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>
      </svg>
      Return a Book
    </a>
    <a href="view_fines.php" class="nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      My Fines
    </a>

    <div class="nav-section-label">Account</div>
    <a href="profile.php" class="nav-link active">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      My Profile
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">JD</div>
      <div class="user-info">
        <div class="user-name">Juan Dela Cruz</div>
        <div class="user-role">Student</div>
      </div>
    </div>
    <a href="../includes/logout.php" class="btn-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Log Out
    </a>
  </div>
</aside>


<!-- ============================================================
     MAIN WRAPPER
     ============================================================ -->
<div class="main-wrapper">

  <header class="topbar">
    <button class="topbar-icon-btn" id="menuToggle" style="display:none;" aria-label="Open menu">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <span class="topbar-title">My Profile</span>
    <div class="topbar-spacer"></div>
    <div class="topbar-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" placeholder="Search books…">
    </div>
    <a href="view_fines.php" class="topbar-icon-btn" title="Fines &amp; Notifications">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <span class="topbar-notif-dot"></span>
    </a>
    <a href="profile.php" class="topbar-icon-btn" title="My Profile">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>


  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>My <em style="font-style:italic;color:var(--gold)">Profile</em></h1>
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
    </div>

    <!-- Profile Hero Card -->
    <div class="profile-hero">
      <div class="profile-banner"></div>
      <div class="profile-hero-body">
        <div class="profile-avatar-xl" title="Change photo">
          JD
          <div class="avatar-edit-overlay">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
          </div>
        </div>
        <div class="profile-hero-info">
          <h2>Juan Dela Cruz</h2>
          <div class="ph-id">Student ID: 2022-01234</div>
          <div class="profile-hero-chips">
            <span class="profile-chip chip-gold">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              Student
            </span>
            <span class="profile-chip chip-sage">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              Active
            </span>
            <span class="profile-chip">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
              </svg>
              BS Computer Science
            </span>
          </div>
        </div>
        <div class="profile-hero-actions">
          <button class="btn-edit-toggle" id="editToggleBtn" onclick="toggleEdit()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit Profile
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="profile-stats">
        <div class="ps-item">
          <span class="psi-val">7</span>
          <span class="psi-lbl">Total Borrowed</span>
        </div>
        <div class="ps-divider"></div>
        <div class="ps-item">
          <span class="psi-val">5</span>
          <span class="psi-lbl">Returned</span>
        </div>
        <div class="ps-divider"></div>
        <div class="ps-item">
          <span class="psi-val">2</span>
          <span class="psi-lbl">Active Loans</span>
        </div>
        <div class="ps-divider"></div>
        <div class="ps-item">
          <span class="psi-val" style="color:var(--rust)">₱20</span>
          <span class="psi-lbl">Unpaid Fines</span>
        </div>
        <div class="ps-divider"></div>
        <div class="ps-item">
          <span class="psi-val">Jan 15, 2022</span>
          <span class="psi-lbl">Member Since</span>
        </div>
      </div>
    </div>


    <!-- Two-column layout -->
    <div class="profile-layout">

      <!-- LEFT: Personal Info -->
      <div>

        <!-- Personal Info Card -->
        <div class="card" style="margin-bottom:16px;">
          <div class="card-body">
            <div class="edit-toggle-header">
              <h3>Personal Information</h3>
            </div>

            <!-- Read-only view -->
            <div class="view-mode" id="viewMode">
              <div class="info-grid">
                <div class="info-cell">
                  <div class="ic-label">First Name</div>
                  <div class="ic-val">Juan</div>
                </div>
                <div class="info-cell">
                  <div class="ic-label">Last Name</div>
                  <div class="ic-val">Dela Cruz</div>
                </div>
                <div class="info-cell">
                  <div class="ic-label">Email Address</div>
                  <div class="ic-val">juan.delacruz@cvsu.edu.ph</div>
                </div>
                <div class="info-cell">
                  <div class="ic-label">Contact Number</div>
                  <div class="ic-val">+63 912 345 6789</div>
                </div>
                <div class="info-cell">
                  <div class="ic-label">Course / Program</div>
                  <div class="ic-val">BS Computer Science</div>
                </div>
                <div class="info-cell">
                  <div class="ic-label">Year Level</div>
                  <div class="ic-val">3rd Year</div>
                </div>
                <div class="info-cell" style="grid-column:1/-1;">
                  <div class="ic-label">Department / College</div>
                  <div class="ic-val">College of Engineering and Information Technology</div>
                </div>
              </div>
            </div>

            <!-- Edit form -->
            <div class="edit-form" id="editForm">
              <div class="field-grid">
                <div class="field">
                  <label>First Name <span>*</span></label>
                  <div class="input-wrap">
                    <span class="ico">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                      </svg>
                    </span>
                    <input type="text" value="Juan" placeholder="First name">
                  </div>
                </div>
                <div class="field">
                  <label>Last Name <span>*</span></label>
                  <div class="input-wrap">
                    <span class="ico">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                      </svg>
                    </span>
                    <input type="text" value="Dela Cruz" placeholder="Last name">
                  </div>
                </div>
              </div>

              <div class="field">
                <label>Email Address</label>
                <div class="input-wrap">
                  <span class="ico">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                      <polyline points="22,6 12,13 2,6"/>
                    </svg>
                  </span>
                  <input type="email" value="juan.delacruz@cvsu.edu.ph" placeholder="Email address" readonly style="opacity:0.6;cursor:not-allowed;">
                </div>
                <div class="field-hint">Institutional email cannot be changed.</div>
              </div>

              <div class="field">
                <label>Contact Number</label>
                <div class="input-wrap">
                  <span class="ico">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.56 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                  </span>
                  <input type="tel" value="+63 912 345 6789" placeholder="Contact number">
                </div>
              </div>

              <div class="field-grid">
                <div class="field">
                  <label>Course / Program</label>
                  <div class="input-wrap">
                    <span class="ico">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                      </svg>
                    </span>
                    <select class="no-icon" style="padding-left:42px;">
                      <option>BS Computer Science</option>
                      <option>BS Information Technology</option>
                      <option>BS Information Systems</option>
                      <option>Other</option>
                    </select>
                    <span class="select-arrow">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6 9 12 15 18 9"/>
                      </svg>
                    </span>
                  </div>
                </div>
                <div class="field">
                  <label>Year Level</label>
                  <div class="input-wrap">
                    <span class="ico">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                      </svg>
                    </span>
                    <select class="no-icon" style="padding-left:42px;">
                      <option>1st Year</option>
                      <option>2nd Year</option>
                      <option selected>3rd Year</option>
                      <option>4th Year</option>
                      <option>5th Year</option>
                    </select>
                    <span class="select-arrow">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6 9 12 15 18 9"/>
                      </svg>
                    </span>
                  </div>
                </div>
              </div>

              <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
                <button class="btn-outline" onclick="cancelEdit()">Cancel</button>
                <button class="btn-primary" onclick="saveProfile()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  Save Changes
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- Change Password Card -->
        <div class="card">
          <div class="card-body">
            <div class="edit-toggle-header">
              <h3>Change Password</h3>
            </div>

            <div class="alert alert-gold" style="margin-bottom:18px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <span>Use a strong password with at least 8 characters, including letters, numbers, and symbols.</span>
            </div>

            <div class="field">
              <label>Current Password <span>*</span></label>
              <div class="input-wrap">
                <span class="ico">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                </span>
                <input type="password" id="currentPw" placeholder="Enter current password">
              </div>
            </div>

            <div class="field">
              <label>New Password <span>*</span></label>
              <div class="input-wrap">
                <span class="ico">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                </span>
                <input type="password" id="newPw" placeholder="Enter new password" oninput="checkStrength(this.value)">
              </div>
              <div class="password-strength">
                <div class="password-strength-bar" id="strengthBar"></div>
              </div>
              <div class="password-strength-label" id="strengthLabel"></div>
            </div>

            <div class="field">
              <label>Confirm New Password <span>*</span></label>
              <div class="input-wrap">
                <span class="ico">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                </span>
                <input type="password" id="confirmPw" placeholder="Confirm new password">
              </div>
            </div>

            <div style="display:flex;justify-content:flex-end;">
              <button class="btn-primary" onclick="changePassword()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Update Password
              </button>
            </div>

          </div>
        </div>

      </div>

      <!-- RIGHT: Account Info + Quick Links -->
      <div>

        <!-- Account Details card -->
        <div class="card" style="margin-bottom:16px;">
          <div class="card-body">
            <div class="card-title">Account Details</div>
            <div class="card-subtitle">System-assigned information</div>

            <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px;">
              <div class="info-cell" style="padding:0;border:none;">
                <div class="ic-label">Student ID</div>
                <div class="ic-val">2022-01234</div>
              </div>
              <div class="info-cell" style="padding:0;border:none;">
                <div class="ic-label">Library Card No.</div>
                <div class="ic-val" style="font-family:monospace;letter-spacing:0.08em;">LIB-22-001234</div>
              </div>
              <div class="info-cell" style="padding:0;border:none;">
                <div class="ic-label">Account Status</div>
                <div class="ic-val"><span class="badge badge-sage">Active</span></div>
              </div>
              <div class="info-cell" style="padding:0;border:none;">
                <div class="ic-label">Borrow Limit</div>
                <div class="ic-val">3 books at a time</div>
              </div>
              <div class="info-cell" style="padding:0;border:none;">
                <div class="ic-label">Loan Period</div>
                <div class="ic-val">14 days per book</div>
              </div>
              <div class="info-cell" style="padding:0;border:none;">
                <div class="ic-label">Registered On</div>
                <div class="ic-val">January 15, 2022</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Links card -->
        <div class="card" style="margin-bottom:16px;">
          <div class="card-body">
            <div class="card-title">Quick Links</div>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
              <a href="borrowed_books.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:var(--input-bg);border:1px solid var(--border);text-decoration:none;color:var(--ink);font-size:0.83rem;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.background='#fdf8ee'" onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--input-bg)'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2">
                  <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                View Borrowed Books
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:auto;opacity:0.4">
                  <polyline points="9 18 15 12 9 6"/>
                </svg>
              </a>
              <a href="view_fines.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:var(--input-bg);border:1px solid var(--border);text-decoration:none;color:var(--ink);font-size:0.83rem;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.background='#fdf8ee'" onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--input-bg)'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--rust)" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                View Fines
                <span class="nav-badge" style="margin-left:auto;font-size:0.65rem;">₱20</span>
              </a>
              <a href="borrow_history.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:var(--input-bg);border:1px solid var(--border);text-decoration:none;color:var(--ink);font-size:0.83rem;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.background='#fdf8ee'" onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--input-bg)'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2">
                  <polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/>
                </svg>
                Borrow History
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:auto;opacity:0.4">
                  <polyline points="9 18 15 12 9 6"/>
                </svg>
              </a>
            </div>
          </div>
        </div>

        <!-- Danger Zone -->
        <div class="danger-zone">
          <h4>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            Account Assistance
          </h4>
          <p>If you need to report an issue with your account or dispute a fine, contact the library directly.</p>
          <a href="mailto:library@cvsu.edu.ph" class="btn-danger" style="font-size:0.78rem;padding:8px 16px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
            </svg>
            Contact Library
          </a>
        </div>

      </div>
    </div>

  </main>
</div>


<div class="toast" id="toast"></div>

<script>
  /* ── Mobile ── */
  function checkMobile() {
    const t = document.getElementById('menuToggle');
    t.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    if (window.innerWidth > 768) document.getElementById('sidebar').classList.remove('open');
  }
  checkMobile();
  window.addEventListener('resize', checkMobile);
  document.getElementById('menuToggle').addEventListener('click', () =>
    document.getElementById('sidebar').classList.toggle('open')
  );

  /* ── Edit toggle ── */
  let isEditing = false;

  function toggleEdit() {
    isEditing = !isEditing;
    const btn      = document.getElementById('editToggleBtn');
    const viewMode = document.getElementById('viewMode');
    const editForm = document.getElementById('editForm');

    if (isEditing) {
      btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancel`;
      btn.classList.add('editing');
      viewMode.classList.add('hidden');
      editForm.classList.add('active');
    } else {
      cancelEdit();
    }
  }

  function cancelEdit() {
    isEditing = false;
    const btn      = document.getElementById('editToggleBtn');
    const viewMode = document.getElementById('viewMode');
    const editForm = document.getElementById('editForm');

    btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit Profile`;
    btn.classList.remove('editing');
    viewMode.classList.remove('hidden');
    editForm.classList.remove('active');
  }

  function saveProfile() {
    cancelEdit();
    showToast('Profile updated successfully!', 'success');
  }

  /* ── Password strength ── */
  function checkStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    bar.className = 'password-strength-bar';
    if (!val) { label.textContent = ''; return; }
    if (val.length < 6) {
      bar.classList.add('strength-weak');
      label.textContent = 'Weak — too short';
      label.style.color = 'var(--rust)';
    } else if (val.length < 10 || !/[0-9]/.test(val)) {
      bar.classList.add('strength-medium');
      label.textContent = 'Medium — add numbers or symbols';
      label.style.color = 'var(--gold-dk)';
    } else {
      bar.classList.add('strength-strong');
      label.textContent = 'Strong password!';
      label.style.color = 'var(--sage)';
    }
  }

  /* ── Change password ── */
  function changePassword() {
    const cur  = document.getElementById('currentPw').value;
    const nw   = document.getElementById('newPw').value;
    const conf = document.getElementById('confirmPw').value;

    if (!cur || !nw || !conf) {
      showToast('Please fill in all password fields.', 'error');
      return;
    }
    if (nw !== conf) {
      showToast('New passwords do not match.', 'error');
      return;
    }
    if (nw.length < 8) {
      showToast('Password must be at least 8 characters.', 'error');
      return;
    }

    document.getElementById('currentPw').value = '';
    document.getElementById('newPw').value = '';
    document.getElementById('confirmPw').value = '';
    document.getElementById('strengthBar').className = 'password-strength-bar';
    document.getElementById('strengthLabel').textContent = '';
    showToast('Password updated successfully!', 'success');
  }

  /* ── Toast ── */
  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (type ? ' ' + type : '');
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }
</script>
</body>