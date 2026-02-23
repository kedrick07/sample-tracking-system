# MOH Laboratory Sample Tracking System
# Progress Report — Day 2

**Project:** MOH Laboratory Sample Tracking System
**Day:** 2 — February 19, 2026
**Developer:** minipekka
**Status:** ✅ Authentication System Complete

---

## 🎯 Today's Goals

1. Fix the missing `mohuser` MariaDB user (Day 1 oversight)
2. Clean and reset the database — remove placeholder/invalid data
3. Implement full authentication: login, logout, session management, audit logging
4. Build working login page and protected role-aware dashboard
5. Resolve all path and redirect issues from Day 1

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

## 📁 Project File Structure (End of Day 2)

```
~/CodingProjects/sample-tracking-system/
├── assets/
│   ├── css/                        # Empty — Bootstrap pending (Day 3)
│   └── js/                         # Empty
├── backups/
├── config/
│   ├── config.php
│   ├── database.php                # ⚠️ NEVER commit to Git
│   └── database.example.php
├── docs/
│   └── Developer-Operations-Guide.md   # ✅ NEW — full dev ops reference
├── includes/
│   ├── auth.php                    # ✅ NEW — login/logout/audit logic
│   └── session.php                 # ✅ UPDATED — fixed redirect paths
├── logs/
├── public/
│   ├── index.php
│   ├── test-db.php
│   └── pages/
│       ├── login.php               # ✅ NEW — login form UI
│       └── dashboard.php           # ✅ NEW — protected, role-aware dashboard
├── scripts/
│   └── provision_admin.php         # ✅ NEW — CLI script to create admin user
├── sql/
│   └── schema.sql
├── .gitignore
└── Progress-Report-Day2.md
```

---

## ✅ Completed

- [x] Fixed `mohuser` MariaDB user — created and verified with `SELECT User, Host FROM mysql.user`
- [x] Cleaned database — dropped placeholder data, wipe order respected FK constraints
- [x] Standardized table naming to no-underscore convention (e.g., `auditlog`, not `audit_log`)
- [x] Created `scripts/provision_admin.php` — inserts real admin with valid Argon2ID hash
- [x] Built `includes/auth.php` — login, logout, `auditlog` + `loginattempts` writes, session binding
- [x] Built `public/pages/login.php` — login form with server-side validation
- [x] Built `public/pages/dashboard.php` — protected page, role-based nav (admin vs user)
- [x] Fixed `includes/session.php` — all redirects changed to absolute paths (`/pages/login.php`)
- [x] Fixed `require_once` paths in `login.php` and `dashboard.php` — changed `../` to `../../` after `pages/` moved inside `public/`
- [x] Moved `pages/` from project root into `public/pages/`
- [x] Produced `docs/Developer-Operations-Guide.md`

---

## 🐛 Bugs & Errors Encountered

### Bug 1 — `ERROR 1045`: Access Denied for `mohuser`@`localhost`
**Symptom:**
```
ERROR 1045 (28000): Access denied for user 'mohuser'@'localhost' (using password: YES)
```
**Root Cause:** The user `mohuser` was never created in MariaDB during Day 1, despite the report claiming it was done.
**Fix:**
```sql
CREATE USER 'mohuser'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON sampletracking.* TO 'mohuser'@'localhost';
FLUSH PRIVILEGES;
```
**Verify:**
```sql
SELECT User, Host FROM mysql.user WHERE User='mohuser';
```
**Lesson:** Never assume a setup step succeeded. Always verify with a query.

---

### Bug 2 — Tables Appeared "Missing" (Misunderstood Empty Tables)
**Symptom:** All 5 tables showed 0 rows — developer thought tables weren't created.
**Root Cause:** Confusion between table structure (schema) and table data (rows). `CREATE TABLE` only creates structure — it inserts nothing.
**Fix:** No fix needed — tables were correct. Next step was to insert data via `provision_admin.php`.
**Verify:**
```sql
SHOW TABLES;                        -- confirms structure exists
SELECT COUNT(*) FROM users;         -- confirms rows (data) exist
```
**Lesson:** `SHOW TABLES` and `SELECT COUNT(*)` are two separate checks. Always do both.

---

### Bug 3 — Naming Inconsistency: `snake_case` vs No-Separator
**Symptom:** `schema.sql` used `audit_log`, `login_attempts`, `special_passwords` but Day 1 docs used `auditlog`, `loginattempts`, `specialpasswords`.
**Root Cause:** Schema was written in snake_case; design docs used concatenated names. Not kept in sync.
**Fix:** Dropped and recreated the database — standardized on no-underscore convention (Day 1 naming).
```sql
DROP DATABASE sampletracking;
CREATE DATABASE sampletracking;
-- re-run corrected schema.sql
```
**Lesson:** Pick a naming convention before writing any code. Document it. Changing table names later forces updates in every query, PHP file, and doc.

---

### Bug 4 — `ERROR 1451`: Cannot Delete Parent Row (Foreign Key Constraint)
**Symptom:**
```
ERROR 1451 (23000): Cannot delete or update a parent row: a foreign key
constraint fails (sampletracking.auditlog, CONSTRAINT auditlog_ibfk_1
FOREIGN KEY (userid) REFERENCES users (userid))
```
**Root Cause:** FK constraints prevented deleting `users` rows while `auditlog` still referenced them. This is correct, expected behavior.
**Fix:** Delete child tables first, then parents. Or disable FK checks during dev reset:
```sql
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM loginattempts;
DELETE FROM auditlog;
DELETE FROM samples;
DELETE FROM specialpasswords;
DELETE FROM users;
SET FOREIGN_KEY_CHECKS = 1;
```
**Safe deletion order for this schema:**
1. `loginattempts`
2. `auditlog`
3. `samples`
4. `specialpasswords`
5. `users` ← always last (referenced by all others)

**Lesson:** FK constraints are a feature, not a bug. In production, never disable them. Only use `FOREIGN_KEY_CHECKS = 0` during dev resets.

---

### Bug 5 — `PLACEHOLDER_HASH` in `users` Table (Login Always Fails)
**Symptom:** Login always failed even with correct credentials. `password_verify()` returned `false`.
**Root Cause:** Sample SQL inserted literal string `'PLACEHOLDER_HASH'` as the password. PHP's `password_verify()` cannot verify against fake strings — it requires a real Argon2ID hash.
**Fix:** Wiped `users` table. Used `scripts/provision_admin.php` to insert admin with a real hash:
```php
$hash = password_hash($password, PASSWORD_ARGON2ID);
// Produces: $argon2id$v=19$m=65536,t=4,p=1$...
```
**Lesson:** Never insert password hashes manually via raw SQL. Always use PHP's `password_hash()`. The hash contains the algorithm, cost parameters, salt, and digest — `password_verify()` needs all of it.

---

### Bug 6 — `HTTP 404` on `/pages/login.php`
**Symptom:**
```
Not Found — The requested URL was not found on this server.
```
**Root Cause:** Apache's `DocumentRoot` is `public/`. The `pages/` folder was at the project root — outside `public/` — so Apache couldn't find it.
**Fix:**
```bash
mv pages/ public/pages/
sudo systemctl restart httpd
```
**Lesson:** Apache only serves files inside `DocumentRoot`. Files outside it (`config/`, `includes/`, `scripts/`) are intentionally protected. Only publicly accessible files go in `public/`.

---

### Bug 7 — `HTTP 500` After Moving `pages/` Into `public/`
**Symptom:**
```
HTTP ERROR 500 — This page isn't working
```
**Root Cause:** After moving `pages/` to `public/pages/`, the `require_once` path `__DIR__ . '/../includes/session.php'` was now wrong — the file was two levels deep (`public/pages/`) but the path only went up one level.
**Fix:** Updated require paths in `login.php` and `dashboard.php`:
```php
// Before (wrong — only one level up)
require_once __DIR__ . '/../includes/session.php';

// After (correct — two levels up from public/pages/)
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
```
**Lesson:** `__DIR__` = absolute path of the current file's directory. Count folder levels from the file's actual location — not the project root.

---

### Bug 8 — Login Succeeds But Redirects Back to Login Page (Loop)
**Symptom:** Correct credentials accepted, no error shown — but browser stayed on login page.
**Initial Suspicion:** "Headers already sent" (output before `header()` call).
**Actual Root Cause (confirmed via `var_dump`):** `requireLogin()` in `session.php` used a relative path `../pages/login.php` in its redirect. When `dashboard.php` called `requireLogin()`, the session was valid but the bad redirect path caused a loop.

**Debugging Steps:**
1. Checked `/var/log/httpd/error_log` → no PHP errors recorded
2. Added `var_dump($_SESSION)` before `requireLogin()` in `dashboard.php`
3. Session showed all 4 correct values (`userid`, `email`, `role`, `lastactivity`)
4. Session was fine — problem was the redirect path in `session.php`

**Fix:**
```php
// Before (relative — breaks depending on folder depth)
header('Location: ../pages/login.php');

// After (absolute — always works)
header('Location: /pages/login.php');
```
**Lesson:** Always use absolute URL paths in `header('Location: ...')`. Relative paths are resolved by the browser against the current URL — not the filesystem — and break unpredictably across folder depths.

---

## 📂 New Files Created Today

| File | Location | Purpose |
|------|----------|---------|
| `auth.php` | `includes/` | Login, logout, audit logging, login attempt logging |
| `login.php` | `public/pages/` | Login form UI with server-side validation |
| `dashboard.php` | `public/pages/` | Protected dashboard with role-based navigation |
| `provision_admin.php` | `scripts/` | One-time CLI script — creates admin with Argon2ID hash |
| `Developer-Operations-Guide.md` | `docs/` | Full developer operations reference guide |

---

## 🔄 Changes From Day 1 → Day 2

| Item | Day 1 | Day 2 |
|------|-------|-------|
| DB user `mohuser` | Planned but missing | Created and verified ✅ |
| `users` table | Empty | 1 real admin with Argon2ID hash ✅ |
| Table naming | Inconsistent (snake_case vs none) | Standardized — no underscores ✅ |
| Authentication | Not implemented | Fully working ✅ |
| `session.php` redirects | Relative paths (`../`) | Absolute paths (`/pages/`) ✅ |
| `pages/` location | Project root (unreachable) | Inside `public/` ✅ |
| `require_once` depth | Single `../` | Corrected to `../../` ✅ |
| Audit logging | Table existed, empty | Logs every login/logout ✅ |
| Login attempt tracking | Table existed, empty | Records all attempts ✅ |
| Dashboard | Not implemented | Working, role-aware, protected ✅ |

---

## 📊 System Status (End of Day 2)

| Component | Status |
|-----------|--------|
| Apache (httpd) | ✅ Running |
| MariaDB | ✅ Running |
| Database `sampletracking` | ✅ Clean, 5 tables |
| Admin user | ✅ Real Argon2ID hash |
| Login page | ✅ Working |
| Dashboard | ✅ Working, role-aware |
| Audit logging | ✅ Recording login/logout |
| Login attempt tracking | ✅ Recording all attempts |
| Session timeout (30 min) | ✅ Active |
| Bootstrap CSS | ❌ Not yet added |
| Sample management | ❌ Not yet built |

---

## ⚠️ Known Issues (End of Day 2)

| # | Issue | Severity | Status |
|---|-------|----------|--------|
| 1 | No Bootstrap CSS — all pages are unstyled | Low | Planned for Day 3 |
| 2 | No `add-sample.php`, `search-samples.php`, `my-records.php` | High | Planned for Day 3 |
| 3 | No `sample-functions.php` CRUD logic | High | Planned for Day 3 |
| 4 | No admin panel (user management, audit viewer) | Medium | Planned for Day 3 |
| 5 | `README.md` still empty | Low | Non-blocking |

---

## 🔒 Important Reminders

1. **NEVER** commit `config/database.php` to Git — it contains credentials
2. **ALWAYS** use absolute paths in `header('Location: ...')` calls
3. **ALWAYS** use `__DIR__` in `require_once` — count folder levels carefully
4. **ALWAYS** delete child table rows before parent rows when wiping the DB
5. **NEVER** insert password hashes manually via SQL — always use PHP
6. **AFTER** moving files, recheck and recount `../../` levels in all require paths

---

## 💡 Key Concepts Learned Today

### Foreign Key Deletion Order
Tables with FK references must be cleared child-first. For this schema: `loginattempts` → `auditlog` → `samples` → `specialpasswords` → `users`.

### Argon2ID Password Hashing
`password_hash($password, PASSWORD_ARGON2ID)` produces a self-contained hash string with algorithm, cost params, salt, and digest. `password_verify($input, $hash)` needs this exact format — fake strings always return `false`.

### Apache DocumentRoot Boundary
`public/` is the web root. Files outside it are unreachable by browser — this is intentional security design. Never move sensitive files inside `public/`.

### `__DIR__` for Reliable Require Paths
`__DIR__` resolves to the absolute path of the current file's directory, regardless of where the script is invoked from. Always use it over bare relative paths.

### Debugging Strategy
1. Check Apache error log first: `tail -f /var/log/httpd/sample-tracking-error.log`
2. Enable `error_reporting(E_ALL)` + `display_errors = On` temporarily
3. Use `var_dump($_SESSION); die();` to inspect state at a point
4. Remove all debug output once issue is confirmed and fixed

---

## 📌 Next Steps — Day 3

1. `public/pages/add-sample.php` — form to add a new sample record
2. `public/pages/search-samples.php` — search across all sample fields
3. `public/pages/my-records.php` — view own submitted sample records
4. `includes/sample-functions.php` — full CRUD logic (insert, search, edit, soft delete)
5. Admin panel: user management page + audit log viewer
6. Add Bootstrap CSS across all pages for consistent UI

---

**End of Day 2 Progress Report**
*Date: February 19, 2026 | Next: Day 3 — Sample Management*
