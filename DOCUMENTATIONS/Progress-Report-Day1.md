# Progress Report - Day 1
## MOH Laboratory Sample Tracking System
**Date:** February 13, 2026  
**Developer:** minipekka  
**Status:** Development Environment Setup Complete

---

## 🎯 What We Accomplished Today

### 1. Project Setup
- ✅ Created project directory structure
- ✅ Removed spaces from folder names (Linux best practice)
- ✅ Set up VSCode development environment
- ✅ Configured Apache HTTP Server
- ✅ Installed and configured MariaDB database
- ✅ Created database schema with 5 tables
- ✅ Set up Git ignore rules for security
- ✅ Created initial configuration files

---

## 📁 Current File Structure

```
~/CodingProjects/sample-tracking-system/
├── assets/
│   ├── css/              # Bootstrap and custom styles (empty - to be added)
│   └── js/               # JavaScript files (empty - to be added)
├── backups/              # Database backups (gitignored)
├── config/
│   ├── config.php        # General app configuration (empty)
│   ├── database.php      # Database credentials (NEVER commit to Git)
│   └── database.example.php  # Template for database config (safe to commit)
├── docs/                 # Documentation files (empty)
├── includes/
│   └── session.php       # Session management and authentication helpers
├── logs/                 # Application logs (gitignored)
├── pages/                # Application pages (login, dashboard, etc.) - empty
├── public/
│   ├── index.php         # Main landing page (test page currently)
│   └── test-db.php       # Database connection test
├── sql/
│   └── schema.sql        # Complete database schema
├── README.md             # Project overview (empty)
└── Progress-Report-Day1.md  # This file
```

---

## 🗄️ Database Setup

### Database Details
- **Database Name:** `sample_tracking`
- **Database User:** `moh_user`
- **Password:** `MOH_Secure_2026!` (stored in config/database.php)
- **Type:** MariaDB 10.11.15
- **Charset:** utf8mb4_general_ci

### Tables Created (5 total)

#### 1. **users** - User Authentication
Stores all user accounts with email-based login restricted to @moh.gov.my domain.

**Columns:**
- `user_id` - Primary key, auto-increment
- `email` - Unique email (must be @moh.gov.my)
- `password_hash` - Argon2ID encrypted password
- `role` - Either 'admin' or 'user'
- `created_at` - Account creation timestamp
- `last_login` - Last successful login time
- `is_active` - Account status (1=active, 0=disabled)

**Purpose:** Authentication and authorization

---

#### 2. **samples** - Core Sample Tracking
Stores all sample location information and tracking data.

**Columns:**
- `running_id` - Primary key, auto-increment
- `freezer_number` - Freezer identification
- `compartment_number` - Compartment within freezer
- `rack_number` - Rack within compartment
- `box_number` - Box within rack
- `sample_location` - Specific location descriptor
- `sample_number` - Unique sample identifier
- `created_by` - Foreign key to users table
- `created_at` - Record creation timestamp
- `modified_by` - Foreign key to users (who last edited)
- `modified_at` - Last modification timestamp
- `is_deleted` - Soft delete flag (0=active, 1=deleted)

**Purpose:** Main data storage for sample locations

---

#### 3. **special_passwords** - Delete Authorization
Stores hashed passwords required for delete operations (extra security layer).

**Columns:**
- `id` - Primary key
- `password_hash` - Argon2ID encrypted password
- `description` - Purpose note
- `created_at` - Creation timestamp

**Purpose:** Additional security for destructive operations

---

#### 4. **audit_log** - Complete Audit Trail
Records every action in the system for compliance and security.

**Columns:**
- `log_id` - Primary key
- `user_id` - Foreign key to users
- `action` - Type: 'create', 'edit', 'delete', 'login', 'logout'
- `record_id` - Which sample was affected (if applicable)
- `details` - Additional information (JSON or text)
- `ip_address` - User's IP address
- `timestamp` - When action occurred

**Purpose:** Security audit trail and compliance

---

#### 5. **login_attempts** - Security Monitoring
Tracks all login attempts for security analysis.

**Columns:**
- `attempt_id` - Primary key
- `email` - Attempted email address
- `success` - 1=successful, 0=failed
- `ip_address` - Source IP
- `attempted_at` - Timestamp

**Purpose:** Security monitoring and breach detection

---

## 🔧 Configuration Files Explained

### 1. `config/database.php` (⚠️ NEVER COMMIT TO GIT)

```php
<?php
// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'sample_tracking');
define('DB_USER', 'moh_user');
define('DB_PASS', 'MOH_Secure_2026!');  // SENSITIVE!
define('DB_CHARSET', 'utf8mb4');

// PDO Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // Error mode: throw exceptions on errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch mode: return associative arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Security: use real prepared statements (prevent SQL injection)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Log error and show generic message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please contact system administrator.");
}
?>
```

**What it does:**
- Defines database credentials as constants
- Creates a PDO (PHP Data Objects) connection
- Sets security attributes to prevent SQL injection
- Handles connection errors gracefully

**Security Note:** This file is in `.gitignore` - it will NEVER be committed to GitHub

---

### 2. `includes/session.php` - Session Management

```php
<?php
/**
 * Session Management
 * Handles secure session configuration and user authentication checks
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
    
    session_start();
}

// Session timeout: 30 minutes
define('SESSION_TIMEOUT', 1800);

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
        // Check session timeout
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            logout();
            return false;
        }
        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Logout user
 */
function logout() {
    $_SESSION = array();
    session_destroy();
    header('Location: ../pages/login.php');
    exit();
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../pages/login.php');
        exit();
    }
}

/**
 * Require admin - redirect if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../pages/dashboard.php');
        exit();
    }
}
?>
```

**What it does:**
- Manages user login sessions
- Handles session timeout (30 minutes of inactivity)
- Provides authentication checking functions
- Security: httponly cookies, prevents session hijacking

**Usage:** Include this file at the top of any protected page

---

## 🌐 Apache Configuration

### Location
`/etc/httpd/conf.d/sample-tracking.conf`

### What it does:
- Points Apache to your project directory
- Sets document root to `public/` folder
- Configures error and access logs
- Allows Apache to read your home directory

### Key Settings:
- **ServerName:** localhost
- **DocumentRoot:** `/home/minipekka/CodingProjects/sample-tracking-system/public`
- **Port:** 80 (HTTP)
- **Logs:** `/var/log/httpd/sample-tracking-*.log`

---

## 🔒 Security Measures Implemented

### 1. Git Security
- `.gitignore` prevents committing sensitive files
- Database credentials excluded from version control
- Log files and backups excluded

### 2. Database Security
- Dedicated database user (not root)
- Strong password required
- Prepared statements prevent SQL injection
- Password hashing using Argon2ID (strongest available)

### 3. Session Security
- HTTP-only cookies
- 30-minute timeout
- Session regeneration on privilege changes (to be implemented)

### 4. SELinux Configuration
- Configured to allow Apache read access to project directory
- `httpd_read_user_content` boolean enabled

---

## 🧪 Testing Status

### ✅ Working
- Apache HTTP Server running
- PHP 8.4.17 functioning
- MariaDB database connected
- All 5 database tables created
- Database connection from PHP verified

### ⚠️ Not Yet Tested
- User authentication
- Sample CRUD operations
- Audit logging
- Session timeout

---

## 📝 Code Quality Notes

### Good Practices Applied
1. **Separation of concerns** - Config, includes, pages in separate folders
2. **Security first** - Credentials protected, prepared statements used
3. **Documentation** - Comments in code explaining purpose
4. **Error handling** - Try-catch blocks for database operations
5. **No spaces in paths** - Linux-friendly naming

### Areas for Improvement
1. Need to add input validation functions
2. Need to implement XSS protection
3. Need to add CSRF tokens (future)
4. Need to implement proper logging
5. Bootstrap CSS not yet added

---

## 🚀 Next Steps (Not Yet Done)

### Phase 1: Authentication System
1. Create login page (`pages/login.php`)
2. Create authentication logic (`includes/auth.php`)
3. Create first admin user in database
4. Test login functionality

### Phase 2: Dashboard
1. Create dashboard page (`pages/dashboard.php`)
2. Show user info and navigation
3. Admin vs regular user views

### Phase 3: Sample Management
1. Create "Add Sample" page
2. Create "Search Samples" page
3. Create "Edit Sample" page
4. Create "Delete Sample" page (with special password)

### Phase 4: Admin Panel
1. User management interface
2. Audit log viewer
3. System statistics

---

## 🐛 Known Issues

1. **Minor:** test-db.php shows some PHP code at top of page (cosmetic issue)
2. **To Fix:** No error logging implemented yet
3. **To Fix:** No Bootstrap CSS files added yet
4. **To Fix:** README.md is empty

---

## 📊 Development Environment

### System Info
- **OS:** Fedora Linux
- **Web Server:** Apache/2.4.66
- **PHP Version:** 8.4.17
- **Database:** MariaDB 10.11.15
- **Project Path:** `~/CodingProjects/sample-tracking-system/`
- **Web URL:** `http://localhost`

### Required Packages Installed
- httpd (Apache)
- mariadb-server
- php
- php-mysqlnd (MySQL native driver)
- php-cli
- php-common
- php-mbstring
- php-xml
- php-json
- php-pdo

---

## 🎓 Key Concepts to Understand

### 1. PDO (PHP Data Objects)
- Modern way to connect to databases in PHP
- Database-agnostic (works with MySQL, PostgreSQL, etc.)
- Supports prepared statements (security against SQL injection)
- Better error handling than old mysql_* functions

### 2. Prepared Statements
```php
// BAD - Vulnerable to SQL injection
$sql = "SELECT * FROM users WHERE email = '$email'";

// GOOD - Safe from SQL injection
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### 3. Session Management
- Sessions store user data server-side
- Only session ID stored in browser cookie
- Used to keep users logged in across pages
- Must be secured against hijacking

### 4. Foreign Keys
- Links tables together (e.g., samples.created_by → users.user_id)
- Ensures data integrity
- Prevents orphaned records

### 5. Soft Delete
- `is_deleted` flag instead of actually deleting
- Preserves data for audit purposes
- Can be restored if needed

---

## 📚 Documentation Reference

**Main Documentation:** `system_documentation.md`

Key sections:
- Architecture: Page 2-3
- Database Schema: Page 4-6
- Security: Page 11-12
- Development Phases: Page 13-14

---

## ✅ Checklist: What's Done

- [x] Project directory created
- [x] Apache installed and configured
- [x] MariaDB installed and secured
- [x] Database created
- [x] Database user created
- [x] All 5 tables created
- [x] Database connection file created
- [x] Session management file created
- [x] .gitignore file created
- [x] Test page working
- [ ] Login page (next)
- [ ] Authentication functions (next)
- [ ] Dashboard (next)
- [ ] Sample management (future)
- [ ] Admin panel (future)

---

## 💡 Important Reminders

1. **NEVER** commit `config/database.php` to Git - it contains passwords!
2. **ALWAYS** use prepared statements for database queries
3. **TEST** in development before deploying to production
4. **BACKUP** the database regularly
5. **DOCUMENT** as you code (future you will thank you)

---

## 🔗 Useful Commands

### Start/Stop Services
```bash
sudo systemctl start httpd
sudo systemctl stop httpd
sudo systemctl restart httpd
sudo systemctl status httpd

sudo systemctl start mariadb
sudo systemctl status mariadb
```

### Database Access
```bash
# Login to database
mysql -u moh_user -p sample_tracking
# Password: MOH_Secure_2026!

# Show tables
mysql -u moh_user -p sample_tracking -e "SHOW TABLES;"

# Backup database
mysqldump -u moh_user -p sample_tracking > backup.sql

# Restore database
mysql -u moh_user -p sample_tracking < backup.sql
```

### View Logs
```bash
# Apache error log
tail -f /var/log/httpd/sample-tracking-error.log

# Apache access log
tail -f /var/log/httpd/sample-tracking-access.log
```

---

## 🎯 Success Metrics

**Today's Goal:** ✅ ACHIEVED  
Set up complete development environment with database.

**Next Session Goal:**  
Create working login system with first admin user.

---

## 📝 Notes for Next Session

1. Review this entire document
2. Understand PDO and prepared statements
3. Understand session management
4. Ready to build login page
5. Consider adding Bootstrap CSS before building UI

---

**End of Day 1 Progress Report**

*Generated: February 13, 2026*  
*Next Update: After login system implementation*
