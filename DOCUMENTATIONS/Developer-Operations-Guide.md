# MOH Laboratory Sample Tracking System
# Developer Operations Guide

> **Environment:** Fedora Linux · Apache (httpd) · MariaDB · PHP 8.4  
> **Project path:** `~/CodingProjects/sample-tracking-system`  
> **Web root:** `public/` → served at `http://localhost/`  
> ⚠️ Passwords are intentionally omitted. Check `config/database.php` for credentials.

---

## 1. Start / Stop Services

Always make sure both services are running before doing anything.

### Check status
```bash
sudo systemctl status httpd
sudo systemctl status mariadb
```

### Start
```bash
sudo systemctl start httpd
sudo systemctl start mariadb
```

### Restart (after config or PHP file changes)
```bash
sudo systemctl restart httpd
sudo systemctl restart mariadb
```

### Stop
```bash
sudo systemctl stop httpd
sudo systemctl stop mariadb
```

---

## 2. Connect to the Database

Log in **once** and stay inside the shell. Everything else in this guide uses SQL commands from inside.

### Standard login
```bash
mysql -u mohuser -p sampletracking
```

### Login as root (for admin tasks — creating users, dropping databases)
```bash
sudo mysql
```

### Once inside, always select the database first
```sql
USE sampletracking;
SELECT DATABASE();
```

---

## 3. Common SQL Commands

Run these from **inside the MariaDB shell** after logging in once.

### Show databases and tables
```sql
SHOW DATABASES;
SHOW TABLES;
```

### Check table structure
```sql
DESCRIBE users;
DESCRIBE samples;
DESCRIBE auditlog;
DESCRIBE loginattempts;
DESCRIBE specialpasswords;
```

### Check row counts
```sql
SELECT COUNT(*) AS users_rows             FROM users;
SELECT COUNT(*) AS samples_rows           FROM samples;
SELECT COUNT(*) AS auditlog_rows          FROM auditlog;
SELECT COUNT(*) AS loginattempts_rows     FROM loginattempts;
SELECT COUNT(*) AS specialpasswords_rows  FROM specialpasswords;
```

### View table data
```sql
SELECT userid, email, role, isactive, createdat FROM users;
SELECT runningid, samplenumber, freezernumber, createdby, isdeleted FROM samples LIMIT 10;
SELECT logid, userid, action, timestamp FROM auditlog ORDER BY timestamp DESC LIMIT 10;
SELECT attemptid, email, success, attemptedat FROM loginattempts ORDER BY attemptedat DESC LIMIT 10;
```

### Exit
```sql
exit;
```

---

## 4. Running SQL Files (Inside the Shell)

Use `SOURCE` to run any `.sql` file without leaving MariaDB or re-entering your password.

```sql
SOURCE sql/schema.sql
SOURCE sql/sample_data.sql
```

If you are not sure about the current directory, use the full path:
```sql
SOURCE /home/minipekka/CodingProjects/sample-tracking-system/sql/schema.sql
```

### Verify after running
```sql
SHOW TABLES;
SELECT COUNT(*) FROM users;
```

---

## 5. Resetting the Database (Full Wipe and Reload)

Log in once, then do everything inside the shell. No password re-entry needed.

```bash
# Login once
mysql -u mohuser -p sampletracking
```

Then inside MariaDB:
```sql
-- Step 1: Wipe existing database
DROP DATABASE IF EXISTS sampletracking;

-- Step 2: Recreate it
CREATE DATABASE sampletracking CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sampletracking;

-- Step 3: Re-apply schema (creates all 5 tables)
SOURCE sql/schema.sql

-- Step 4: (Optional) Load sample/test data
SOURCE sql/sample_data.sql

-- Step 5: Verify
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM samples;
```

> ⚠️ If DROP DATABASE fails due to permissions, log in as root instead:
> `sudo mysql` → then repeat from Step 1.

---

## 6. Running PHP Scripts

### Web pages (open in browser)
| Page | URL |
|------|-----|
| Home | http://localhost/ |
| DB test | http://localhost/test-db.php |
| Login | http://localhost/pages/login.php |
| Dashboard | http://localhost/pages/dashboard.php |

Hard refresh if changes don't appear: **Ctrl+F5**

### CLI scripts (run from terminal, not browser)
```bash
# Create first admin user (Argon2ID hashed password)
php scripts/provision_admin.php
```

> `scripts/` is outside `public/` intentionally — it is NOT web-accessible.

### Alternative: PHP built-in server (no Apache needed)
```bash
cd ~/CodingProjects/sample-tracking-system/public
php -S localhost:8000
```
Then open: `http://localhost:8000/`  
MariaDB must still be running.

---

## 7. Applying Code Changes

| Change type | What to do |
|-------------|-----------|
| PHP file edited | Reload browser (Ctrl+F5) |
| Apache config changed | `sudo systemctl restart httpd` |
| `config/database.php` changed | `sudo systemctl restart httpd` |
| SQL schema changed | Login to MariaDB → `SOURCE sql/schema.sql` |
| New SQL file added | Login to MariaDB → `SOURCE sql/yourfile.sql` |

---

## 8. Viewing Logs (Debugging)

### Apache error log — most important for PHP errors
```bash
sudo tail -f /var/log/httpd/sample-tracking-error.log
```

### Apache access log
```bash
sudo tail -f /var/log/httpd/sample-tracking-access.log
```

> **Tip:** Keep the error log open in one terminal while reloading the page in your browser.

---

## 9. Testing Checklist

Run through this after any major change:

- [ ] `sudo systemctl status httpd` → active (running)
- [ ] `sudo systemctl status mariadb` → active (running)
- [ ] `http://localhost/test-db.php` → shows "✓ Database connected" + all 5 tables listed
- [ ] `http://localhost/` → home page loads without errors
- [ ] Inside MariaDB: `SHOW TABLES;` → returns 5 tables
- [ ] Inside MariaDB: `SELECT COUNT(*) FROM users;` → expected row count
- [ ] `php scripts/provision_admin.php` → admin user created (first-time only)
- [ ] Login at `http://localhost/pages/login.php` → redirects to dashboard
- [ ] Apache error log → no new errors

---

## 10. Backup and Restore

### Backup (from terminal)
```bash
mysqldump -u mohuser -p sampletracking > backups/backup-$(date +%Y%m%d-%H%M%S).sql
```

### Restore (from inside MariaDB shell)
```sql
USE sampletracking;
SOURCE backups/your-backup-file.sql
```

---

## 11. Quick Troubleshooting

| Symptom | Likely cause | Fix |
|---------|-------------|-----|
| "Database connection error" | MariaDB not running or wrong credentials | `systemctl status mariadb`, verify `config/database.php` |
| Page not found (404) | Apache not running or wrong DocumentRoot | `systemctl status httpd`, check `/etc/httpd/conf.d/sample-tracking.conf` |
| PHP shows as plain text | Apache PHP module not loaded | `php --version`, check Apache error log |
| `SHOW TABLES` returns 0 rows | Schema not imported | Inside MariaDB: `SOURCE sql/schema.sql` |
| `Call to undefined function db()` | `includes/db.php` empty or not required | Check `includes/db.php` contains `function db()` |
| Changes not appearing | Browser cache | Ctrl+F5 hard refresh |
| Access denied for mohuser | User not created or wrong password | `sudo mysql` → recreate user and GRANT |

---

**Last updated:** February 19, 2026  
**Project:** MOH Laboratory Sample Tracking System  
**Developer:** minipekka
