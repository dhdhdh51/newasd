# School ERP — Deployment Guide

## Table of Contents
1. [Requirements](#requirements)
2. [cPanel Shared Hosting Setup](#cpanel-shared-hosting-setup)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [SMTP Email Setup](#smtp-email-setup)
6. [PayU Payment Gateway](#payu-payment-gateway)
7. [File Permissions](#file-permissions)
8. [SSL / HTTPS](#ssl--https)
9. [Troubleshooting](#troubleshooting)

---

## Requirements

| Item | Minimum |
|------|---------|
| PHP  | 8.1 (8.2 recommended) |
| MySQL | 5.7 / MariaDB 10.4 |
| Apache | mod_rewrite, mod_headers |
| PHP Extensions | PDO, PDO_MySQL, openssl, fileinfo, mbstring |
| Disk | 100 MB (excluding uploads) |

> **No Composer, no framework, no terminal required.** Everything runs on standard cPanel shared hosting.

---

## cPanel Shared Hosting Setup

### Step 1 — Upload Files

1. Log in to **cPanel → File Manager**.
2. Navigate to `public_html` (or your subdirectory, e.g. `public_html/schoolerp`).
3. Upload the entire project folder contents there.  
   *(Or upload a ZIP and extract it via cPanel's Extract button.)*

### Step 2 — Verify .htaccess

cPanel Apache usually has `mod_rewrite` enabled. If you see a *500 Internal Server Error*:

- Go to **cPanel → Apache Handlers** or **MultiPHP INI Editor** and check PHP version.
- If `AllowOverride` is `None` on your host, contact support to enable `.htaccess`.

### Step 3 — Create the Database

1. **cPanel → MySQL Databases**  
   - Create a new database, e.g. `youruser_schoolerp`
   - Create a MySQL user with a strong password
   - Add the user to the database with **All Privileges**

2. **cPanel → phpMyAdmin**  
   - Select your database
   - Click **Import**
   - Browse to `database.sql` and import it

### Step 4 — Set SITE_URL

Open `config/config.php` and update:

```php
define('SITE_URL', 'https://yourdomain.com');
```

If deployed in a sub-directory:

```php
define('SITE_URL', 'https://yourdomain.com/schoolerp');
```

Also update the `RewriteBase` in `.htaccess` if using a sub-directory:

```apache
RewriteBase /schoolerp/
```

### Step 5 — First Login

Default admin credentials (created by `database.sql`):

| Field    | Value       |
|----------|-------------|
| Role     | Admin       |
| Email    | admin@school.com |
| Password | `Admin@123` |

**Change the password immediately** after first login via Settings.

---

## Database Setup

The `database.sql` file creates all 18 tables and seeds:

- 1 admin user
- 12 classes (Class 1 – Class 12)
- 2 sections per class (A, B)
- 12 subjects
- Default application settings

If you need a fresh database at any time, drop all tables and re-import.

---

## Configuration

All configuration is in `config/config.php`. The following constants **must** be reviewed:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'youruser_dbuser');
define('DB_PASS', 'strongpassword');
define('DB_NAME', 'youruser_schoolerp');

// Site URL — no trailing slash
define('SITE_URL', 'https://yourdomain.com');
```

Runtime settings (logo, SMTP, PayU, SEO) are stored in the `settings` table and managed through **Admin → Settings**.

---

## SMTP Email Setup

The application uses a **pure PHP SMTP class** — no PHPMailer, no Composer.

### Gmail (App Password)

1. Enable 2-Factor Authentication on your Google account.
2. Go to **Google Account → Security → App Passwords**.
3. Generate an app password for "Mail".
4. In **Admin → Settings → SMTP**:

| Field | Value |
|-------|-------|
| SMTP Host | `smtp.gmail.com` |
| Port | `587` |
| Encryption | `tls` (STARTTLS) |
| Username | `yourname@gmail.com` |
| Password | (16-character app password) |
| From Email | `yourname@gmail.com` |
| From Name | Your School Name |

### Outlook / Office 365

| Field | Value |
|-------|-------|
| SMTP Host | `smtp.office365.com` |
| Port | `587` |
| Encryption | `tls` |
| Username | `yourname@school.edu` |
| Password | Your Microsoft password |

### cPanel Email (Recommended for Production)

1. **cPanel → Email Accounts** — create `noreply@yourdomain.com`
2. Note the SMTP hostname (usually `mail.yourdomain.com`)

| Field | Value |
|-------|-------|
| SMTP Host | `mail.yourdomain.com` |
| Port | `465` |
| Encryption | `ssl` |
| Username | `noreply@yourdomain.com` |
| Password | Email account password |

### Test SMTP

After saving settings, use the **Test Email** button on the Settings page, or trigger a "Forgot Password" OTP from the login page.

---

## PayU Payment Gateway

The application supports **PayU India** (PayUbiz / PayUmoney).

### Step 1 — Get Credentials

1. Sign up at [https://dashboard.payu.in](https://dashboard.payu.in)
2. Go to **Settings → Merchant Keys**
3. Note your **Merchant Key** and **Salt**

For testing, use PayU's sandbox credentials.

### Step 2 — Configure in Admin Panel

Go to **Admin → Settings → PayU**:

| Field | Value |
|-------|-------|
| Merchant Key | `gtKFFx` (sandbox) or your live key |
| Merchant Salt | `eCwWELxi` (sandbox) or your live salt |
| PayU Mode | `test` or `live` |

### Step 3 — Set Callback URLs in PayU Dashboard

| Type | URL |
|------|-----|
| Success URL | `https://yourdomain.com/payment/success.php` |
| Failure URL | `https://yourdomain.com/payment/failure.php` |

### How It Works

1. Student clicks **Pay Now** on a fee invoice.
2. JavaScript `fetch()`-es `/payment/payu.php` which generates a SHA512 hash:
   ```
   key|txnid|amount|productinfo|firstname|email|udf1||||||||||||salt
   ```
3. The hidden form auto-submits to PayU's payment page.
4. PayU redirects back to `/payment/success.php` (or failure).
5. Success handler verifies the **reverse hash**, marks fee as paid, logs the transaction, and sends a confirmation email.

### Hash Formula (for reference)

**Forward hash** (before payment):
```
sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||salt)
```

**Reverse hash** (after payment, for verification):
```
sha512(salt|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)
```

---

## File Permissions

Set via **cPanel → File Manager → Right-click → Change Permissions**:

| Path | Permission |
|------|-----------|
| `uploads/` | `755` |
| `uploads/students/` | `755` |
| `uploads/teachers/` | `755` |
| `uploads/settings/` | `755` |
| `config/config.php` | `644` |
| `database.sql` | `600` (or delete after import) |
| All `.php` files | `644` |
| All directories | `755` |

> **Security:** Delete or restrict `database.sql` after importing. It contains your schema and default credentials.

---

## SSL / HTTPS

All production deployments **must** use HTTPS.

### Enable Free SSL (Let's Encrypt)

1. **cPanel → SSL/TLS → Let's Encrypt (AutoSSL)**
2. Click **Run AutoSSL** or install a certificate for your domain.

### After SSL is Active

Uncomment in `.htaccess`:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

Uncomment in `.htaccess` PHP settings:

```apache
php_value session.cookie_secure 1
```

---

## Directory Structure

```
/
├── admin/              Admin panel pages
│   ├── ajax/           AJAX endpoints
│   ├── admissions/
│   ├── attendance/
│   ├── classes/
│   ├── exams/
│   ├── fees/
│   ├── marks/
│   ├── notifications/
│   ├── parents/
│   ├── results/
│   ├── settings/
│   ├── students/
│   ├── subjects/
│   └── teachers/
├── assets/
│   ├── css/style.css   Custom stylesheet
│   └── js/main.js      Custom JavaScript
├── auth/               Login, logout, forgot-password
├── config/             config.php, database.php
├── includes/           header, footer, functions, mailer, notifications
├── notifications/      Mark-read, mark-all-read
├── parent/             Parent portal
├── payment/            PayU handler
├── public/             Admission form (no auth)
├── student/            Student portal
├── teacher/            Teacher portal
├── uploads/            User-uploaded files (photos, logos)
├── .htaccess
├── database.sql
├── index.php           Smart redirect entry point
├── robots.txt
└── sitemap.xml
```

---

## Troubleshooting

### Blank page or 500 error
- Enable PHP error display temporarily: add `ini_set('display_errors', 1);` at top of `index.php`
- Check the Apache error log: **cPanel → Error Log**
- Verify `config/config.php` DB credentials are correct

### Login redirects back to login page
- Check session settings — ensure `session.cookie_httponly = 1` is supported
- Verify `SITE_URL` has no trailing slash
- Clear browser cookies and try again

### Images / CSS not loading
- Verify `ASSETS_URL` in `config/config.php` is correct
- Check file permissions on `assets/` directory (must be `755`)

### Emails not sending
- Check SMTP credentials in Admin → Settings
- Ensure outbound port 587/465 is not blocked by your host (most shared hosts allow it)
- Some hosts require SMTP authentication — double-check username/password
- Check PHP error log for socket errors

### PayU hash mismatch
- Ensure the **Salt** in settings exactly matches your PayU dashboard
- Check for extra spaces or newlines in the key/salt fields
- Test in **sandbox mode** first before going live

### Uploads not working
- Create the directories manually if missing:
  ```
  uploads/
  uploads/students/
  uploads/teachers/
  uploads/settings/
  ```
- Set permissions to `755`
- Verify `upload_max_filesize` and `post_max_size` in PHP settings (or `.htaccess`)

---

## Security Checklist

- [ ] Change default admin password immediately after first login
- [ ] Delete or restrict access to `database.sql`
- [ ] Enable HTTPS and uncomment HSTS header
- [ ] Set `session.cookie_secure = 1` after enabling HTTPS
- [ ] Verify `uploads/` directory blocks PHP execution (`.htaccess` rule is included)
- [ ] Review SMTP credentials and use app passwords, not account passwords
- [ ] Keep PHP version updated to 8.1+
- [ ] Enable cPanel's ModSecurity if available
- [ ] Regularly backup the database via cPanel → Backup

---

*School ERP — Core PHP, no frameworks, no Composer. Built for cPanel shared hosting.*
