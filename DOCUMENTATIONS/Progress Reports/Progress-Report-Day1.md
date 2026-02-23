# MOH Laboratory Sample Tracking System
# Progress Report — Day 1

**Project:** MOH Laboratory Sample Tracking System
**Day:** 1 — February 13, 2026
**Developer:** minipekka
**Status:** ✅ Development Environment Setup Complete

---

## 🎯 Today's Goals

1. Set up the development environment (Apache, PHP, MariaDB) on Fedora Linux
2. Design and create the database schema with 5 tables
3. Create the base project folder structure with security separation
4. Create initial configuration files (`database.php`, `session.php`)
5. Verify full stack is working (Apache → PHP → MariaDB)

---

## 🧰 Current Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.4.17 |
| Database | MariaDB 10.11.15 |
| Web Server | Apache 2.4.66 |
| OS | Fedora Linux |
| Framework | None (vanilla PHP) |
| Hosting | Local development (http://localhost) |
| Version Control | Git |

---

## 📁 Project File Structure

```
~/CodingProjects/sample-tracking-system/
├── assets/
│   ├── css/                        # Bootstrap + custom styles (empty)
│   └── js/                         # JavaScript files (empty)
├── backups/                        # Database backups (gitignored)
├── config/
│   ├── config.php                  # General app config (empty placeholder)
│   ├── database.php                # ⚠️ DB credentials — NEVER commit to Git
│   └── database.example.php        # Safe-to-commit template
├── docs/                           # Documentation (empty)
├── includes/
│   └── session.php                 # Session management + auth helpers
├── logs/                           # App logs (gitignored)
├── pages/                          # ⚠️ WRONG LOCATION — should be inside public/
├── public/
│   ├── index.php                   # Landing/test page
│   └── test-db.php                 # DB connection test
├── sql/
│   └── schema.sql                  # Full database schema
├── .gitignore                      # Excludes credentials, logs, backups
└── Progress-Report-Day1.md
```

---

## 🗄️ Database Schema (5 Tables)

**Database:** `sampletracking` | **User:** `mohuser` | **Charset:** utf8mb4

### Table 1 — `users`
| Column | Type | Notes |
|--------|------|-------|
| userid | INT PK AI | Primary key |
| email | VARCHAR UNIQUE | Must be @moh.gov.my |
| passwordhash | VARCHAR | Argon2ID hashed |
| role | ENUM | 'admin' or 'user' |
| createdat | DATETIME | Account created |
| lastlogin | DATETIME | Last successful login |
| isactive | TINYINT | 1=active, 0=disabled |

### Table 2 — `samples`
| Column | Type | Notes |
|--------|------|-------|
| runningid | INT PK AI | Primary key |
| freezernumber | VARCHAR | Freezer ID |
| compartmentnumber | VARCHAR | Compartment |
| racknumber | VARCHAR | Rack |
| boxnumber | VARCHAR | Box |
| samplelocation | VARCHAR | Location descriptor |
| samplenumber | VARCHAR UNIQUE | Sample ID |
| createdby | INT FK | → users.userid |
| createdat | DATETIME | Record creation |
| modifiedby | INT FK | → users.userid |
| modifiedat | DATETIME | Last edit |
| isdeleted | TINYINT | 0=active, 1=deleted (soft delete) |

### Table 3 — `specialpasswords`
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AI | Primary key |
| passwordhash | VARCHAR | Argon2ID — required for delete ops |
| description | TEXT | Purpose note |
| createdat | DATETIME | — |

### Table 4 — `auditlog`
| Column | Type | Notes |
|--------|------|-------|
| logid | INT PK AI | Primary key |
| userid | INT FK | → users.userid |
| action | ENUM | 'create', 'edit', 'delete', 'login', 'logout' |
| recordid | INT NULL | Affected sample (if any) |
| details | TEXT | JSON or text |
| ipaddress | VARCHAR | User IP |
| timestamp | DATETIME | When action occurred |

### Table 5 — `loginattempts`
| Column | Type | Notes |
|--------|------|-------|
| attemptid | INT PK AI | Primary key |
| email | VARCHAR | Attempted email |
| success | TINYINT | 1=success, 0=fail |
| ipaddress | VARCHAR | Source IP |
| attemptedat | DATETIME | Timestamp |

---

## ✅ Completed

- [x] Project directory created at `~/CodingProjects/sample-tracking-system/`
- [x] Removed spaces from all folder names (Linux best practice)
- [x] Apache installed and configured — `DocumentRoot` set to `public/`
- [x] Apache vhost config created at `/etc/httpd/conf.d/sample-tracking.conf`
- [x] SELinux configured: `httpd_read_user_content` boolean enabled
- [x] MariaDB installed, secured, and running
- [x] Database `sampletracking` created with all 5 tables
- [x] `config/database.php` created with PDO connection (prepared statements, exception mode)
- [x] `includes/session.php` created with `isLoggedIn()`, `isAdmin()`, `requireLogin()`, `requireAdmin()`, `logout()`
- [x] `.gitignore` created — credentials, logs, backups excluded
- [x] `public/test-db.php` verified DB connection works
- [x] Apache + PHP 8.4.17 + MariaDB all confirmed running

---

## 🐛 Bugs & Issues Introduced (Discovered Later in Day 2)

### Bug 1 — DB User `mohuser` Not Actually Created
**Discovered:** Day 2
**Symptom:** `ERROR 1045: Access denied for user 'mohuser'@'localhost'`
**Root Cause:** Day 1 setup appeared to complete without error, but the user was never actually created in MariaDB.
**Impact:** Entire authentication flow blocked until fixed in Day 2.
**Fix (Day 2):**
```sql
CREATE USER 'mohuser'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON sampletracking.* TO 'mohuser'@'localhost';
FLUSH PRIVILEGES;
```
**Lesson:** Always verify user creation with `SELECT User, Host FROM mysql.user WHERE User='mohuser';`

---

### Bug 2 — `pages/` Folder Outside `public/` (Wrong Location)
**Discovered:** Day 2
**Symptom:** HTTP 404 on `/pages/login.php`
**Root Cause:** Apache only serves files inside `DocumentRoot` (`public/`). The `pages/` folder was placed at the project root, outside `public/`, making it invisible to Apache.
**Impact:** No page in `pages/` could be served via browser.
**Fix (Day 2):** `mv pages/ public/pages/`
**Lesson:** Only files meant to be publicly accessible go in `public/`. Everything else (`config/`, `includes/`, `scripts/`) stays outside for security.

---

### Bug 3 — Relative Redirect Paths in `session.php`
**Introduced:** Day 1
**Symptom:** After login, browser redirected back to login page (loop).
**Root Cause:** `header('Location: ../pages/login.php')` uses a relative path, which PHP resolves relative to the current URL — not the filesystem. This is unpredictable across different folder depths.
**Impact:** `requireLogin()` and `logout()` redirects silently broke.
**Fix (Day 2):** Changed all redirects to absolute paths:
```php
header('Location: /pages/login.php');
```
**Lesson:** Always use absolute URL paths (starting with `/`) in `header('Location: ...')`.

---

## ⚠️ Known Issues (End of Day 1)

| # | Issue | Severity | Status |
|---|-------|----------|--------|
| 1 | `test-db.php` shows raw PHP text at top of page | Low | Cosmetic only |
| 2 | `README.md` is empty | Low | Non-blocking |
| 3 | No Bootstrap CSS added | Low | UI not started |
| 4 | No authentication implemented yet | High | Planned for Day 2 |
| 5 | `pages/` folder in wrong location | High | Fixed in Day 2 |
| 6 | `session.php` uses relative redirect paths | High | Fixed in Day 2 |
| 7 | `mohuser` DB user not actually created | Critical | Fixed in Day 2 |

---

## 🔒 Security Measures

- `config/database.php` excluded from Git via `.gitignore`
- PDO with `ATTR_EMULATE_PREPARES = false` — real prepared statements (SQL injection prevention)
- `ATTR_ERRMODE = ERRMODE_EXCEPTION` — proper error handling
- Session cookies: `httponly = 1`, `use_only_cookies = 1`
- Session timeout: 30 minutes (`SESSION_TIMEOUT = 1800`)
- Dedicated DB user (`mohuser`) — not root
- Apache `DocumentRoot` = `public/` — config/includes/scripts not web-accessible

---

## 📌 Next Steps — Day 2

1. **Fix** `mohuser` MariaDB user — create and verify it exists
2. **Create** `scripts/provision_admin.php` — insert real admin with Argon2ID hash
3. **Build** `includes/auth.php` — login, logout, audit logging, login attempt logging
4. **Build** `public/pages/login.php` — login form with validation
5. **Build** `public/pages/dashboard.php` — protected, role-aware dashboard
6. **Fix** `includes/session.php` — change redirect paths to absolute
7. **Move** `pages/` into `public/pages/`

---

## 🧰 Useful Commands Reference

```bash
# Services
sudo systemctl start httpd && sudo systemctl start mariadb
sudo systemctl status httpd
tail -f /var/log/httpd/sample-tracking-error.log

# Database
sudo mysql                                          # Login as root
mysql -u mohuser -p sampletracking                 # Login as app user
SELECT User, Host FROM mysql.user;                  # Verify users exist
SHOW TABLES;                                        # Verify tables exist
SELECT COUNT(*) FROM users;                         # Verify rows exist

# Backup / Restore
mysqldump -u mohuser -p sampletracking > backup.sql
mysql -u mohuser -p sampletracking < backup.sql
```

---

**End of Day 1 Progress Report**
*Date: February 13, 2026 | Next: Day 2 — Authentication System*
