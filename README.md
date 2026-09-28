# AutoScan AI — Vehicle License Plate Scanner & Registration System

A modern, high-performance **Vehicle License Plate Recognition (OCR)** and **Automated Vehicle Registration Web Application** built strictly using native web technologies: **PHP 8+**, **MySQL (InnoDB)**, **HTML5**, **CSS3**, **Vanilla JavaScript**, **PDO**, **WebRTC**, and **Fetch API**.

Zero external PHP or JavaScript frameworks (no Laravel, React, Vue, jQuery, or Bootstrap). Designed for fast execution and optimal performance on both local environments (XAMPP) and shared hosting (iFastNet).

---

## 🌟 Key Features

1. **WebRTC Live Camera & Image License Plate Scanner**
   - Live camera stream with rear/environment camera priority (`navigator.mediaDevices.getUserMedia`).
   - Frame capture button for real-time video snapshots.
   - HTML5 drag-and-drop / file upload for static plate images (JPG, PNG, WebP).
   - Optical Character Recognition (OCR) extraction with confidence scoring.
   - Dynamic cyan neon bounding box overlay rendering on canvas preview.
   - Automatic auto-fill directly into the vehicle registration form with highlight flash animation.
   - Resilient error handling with actionable tips (`PLATE_UNREADABLE`, `NO_PLATE_DETECTED`).

2. **Manual Vehicle Information Registration**
   - Ensures 100% data integrity with manual user verification.
   - License plate (auto-filled by scanner, editable).
   - Owner Full Name, Contact Phone, Vehicle Make/Brand, Model, Color, and Body Type.
   - Body types supported: Sedan, SUV, Hatchback, Pickup, Van, Motorcycle, Lorry.

3. **Dynamic Live Exchange Rate Fee Converter**
   - Real-time foreign exchange fee calculation on currency selection without reloading the page.
   - **Primary Exchange API:** `https://open.er-api.com/v6/latest/`
   - **Fallback Exchange API:** `https://api.frankfurter.dev/v1/latest?base=`
   - **1-Hour MySQL Caching:** Rates cached in `rate_cache` table to reduce external network calls.
   - Stale cache fallback if both external APIs are temporarily unavailable.
   - Currencies supported: USD, EUR, GBP, MYR, CNY, SGD, JPY, AUD, CAD, THB, IDR.

4. **Registered Vehicles Directory & Registry Log**
   - Live debounced AJAX search by Plate Number, Owner Name, Vehicle Make, or Model.
   - Multi-field filtering by Body Type and Date range.
   - Paginated table layout with vehicle inspection modal.
   - Original fee and converted fee display.
   - Quick export to CSV utility.

5. **Multi-Language Support (i18n)**
   - English (EN) and Chinese (ZH / 中文).
   - Instant client-side switching using `i18n.js` with persistence in `localStorage`.

6. **Comprehensive Security & Defense**
   - 100% PDO prepared statements for SQL Injection prevention.
   - Cryptographic 64-hex CSRF token verification on all POST endpoints.
   - Database-driven IP rate limiting (max 30 requests per 60 seconds per IP).
   - Cross-Site Scripting (XSS) defense via `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
   - Error traces suppressed from end-users and logged to private error logs.
   - Security headers configured in `.htaccess`.

---

## 📁 Project Structure

```
vehicle_plate_scan/
├── api/
│   ├── rates.php          # Serves cached/live exchange rates for fee calculation
│   ├── register.php       # Sanitizes, validates, and stores registration data
│   ├── scan.php           # Receives camera frame / image file and returns OCR plate text
│   └── vehicles.php       # Handles AJAX search and pagination for registered vehicles
│
├── assets/
│   ├── css/
│   │   └── style.css      # Pure Vanilla CSS3 cyber dark design system
│   └── js/
│       ├── app.js         # Core JS: WebRTC camera, canvas bounding box, AJAX, converter
│       └── i18n.js        # Multi-language translation engine (EN / 中文)
│
├── includes/
│   ├── config.php         # Application constants, fee rules, and session configs
│   ├── db.php             # Singleton PDO connection with multi-port auto fallback
│   ├── helpers.php        # CSRF, rate limiter, currency engine, and sanitizers
│   ├── layout.php         # Reusable HTML5 layout, navigation, and footer
│   └── schema.sql         # MySQL InnoDB schema with indexes and seed demo records
│
├── .htaccess              # HTTPS redirection, security headers, file protection, compression
├── index.php              # Plate scanner & vehicle registration form
├── manual.php             # System usage manual & API documentation
├── README.md              # Project documentation & deployment guide
└── vehicles.php           # Registered vehicles directory & search
```

---

## 🛠️ Local Development Setup (XAMPP / Apache)

### Prerequisites
- PHP 8.0 or newer (tested on PHP 8.2, 8.3, 8.5) with `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `gd`.
- MySQL 8.0+ or MariaDB 10.4+.
- Web browser supporting WebRTC (Chrome, Edge, Firefox, Safari).

### Step-by-Step Installation

1. **Clone or Copy Repository:**
   Place the project directory into your web server document root:
   ```
   d:/xampp/htdocs/vehicle_plate_scan/
   ```

2. **Create MySQL Database & Import Schema:**
   Open your MySQL client (or phpMyAdmin at `http://localhost/phpmyadmin/`):
   ```sql
   CREATE DATABASE IF NOT EXISTS `vehicle_plate_scan` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE `vehicle_plate_scan`;
   ```
   Import `includes/schema.sql` via command line:
   ```bash
   mysql -u root -p -P 3306 vehicle_plate_scan < includes/schema.sql
   ```
   *(If your local MySQL runs on port 3307, specify `-P 3307`)*.

3. **Configure Database Credentials (`includes/config.php`):**
   Open `includes/config.php` and verify your database connection settings:
   ```php
   define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
   define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306)); // or 3307
   define('DB_NAME', getenv('DB_NAME') ?: 'vehicle_plate_scan');
   define('DB_USER', getenv('DB_USER') ?: 'root');
   define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
   ```
   *Note: `includes/db.php` includes automatic port fallback between 3306 and 3307.*

4. **Run the Application:**
   - **Via Apache in XAMPP:** Navigate to:
     ```
     http://localhost/vehicle_plate_scan/
     ```
   - **Via PHP Built-in Server:**
     ```bash
     cd d:/xampp/htdocs/vehicle_plate_scan
     php -S 127.0.0.1:8000
     ```
     Then open `http://127.0.0.1:8000` in your web browser.

---

## 🚀 Deployment Guide for iFastNet Shared Hosting

iFastNet provides standard cPanel / VistaPanel shared hosting with Apache and MySQL.

1. **Create MySQL Database:**
   - Log in to your hosting control panel (cPanel / VistaPanel).
   - Go to **MySQL Database Wizard**.
   - Create a database name (e.g. `epiz_12345678_vehicles`).
   - Create a database user and assign a password.
   - Grant **ALL PRIVILEGES** to the user.

2. **Import Database Schema:**
   - Go to **phpMyAdmin** in your control panel.
   - Select your newly created database.
   - Click the **Import** tab.
   - Choose `includes/schema.sql` from your computer and click **Go**.

3. **Upload Files:**
   - Connect via FTP (e.g., FileZilla) or use the hosting **File Manager**.
   - Upload all files into the `htdocs/` or `public_html/` folder.

4. **Update `includes/config.php`:**
   Edit `includes/config.php` on the server:
   ```php
   define('DB_HOST', 'sqlxxx.epizy.com'); // Provided by iFastNet
   define('DB_PORT', 3306);
   define('DB_NAME', 'epiz_12345678_vehicles');
   define('DB_USER', 'epiz_12345678');
   define('DB_PASS', 'YourAssignedPassword');
   ```

5. **Enable HTTPS / SSL Certificate:**
   - Modern browsers (Chrome, Safari, iOS, Android) strictly require **HTTPS** to allow access to camera hardware (`getUserMedia`).
   - In your iFastNet control panel, activate the free **SSL Certificate** (Let's Encrypt / ZeroSSL).
   - The `.htaccess` file automatically redirects HTTP traffic to HTTPS.

---

## 📡 REST API Reference

| Endpoint | Method | Description | Rate Limit |
|---|---|---|---|
| `/api/scan.php` | `POST` | Uploads frame snapshot or file; returns plate OCR text and coordinates | 30 req/min |
| `/api/register.php` | `POST` | Validates CSRF and vehicle specifications; registers vehicle | 30 req/min |
| `/api/rates.php` | `GET` | Returns 1h cached or live exchange rates and calculated fees | 30 req/min |
| `/api/vehicles.php` | `GET` | AJAX search, body type filter, date filter, and pagination | 30 req/min |

### Example Scan Request:
```bash
curl -X POST http://localhost/vehicle_plate_scan/api/scan.php \
  -H "Content-Type: application/json" \
  -d '{"image": "data:image/jpeg;base64,..."}'
```

### Example Response:
```json
{
  "status": "success",
  "data": {
    "plate_number": "ABC1234",
    "confidence": 0.98,
    "box": { "x": 120, "y": 80, "width": 300, "height": 90 },
    "raw_text": "ABC1234"
  }
}
```

---

## 🔍 Troubleshooting & Common Issues

### 1. Camera Access Denied / Not Opening
- **Cause:** WebRTC requires a secure context. Mobile browsers block camera requests on unencrypted `http://` (except `http://localhost` and `http://127.0.0.1`).
- **Fix:** Enable HTTPS on your live domain or test locally using `http://localhost` or `http://127.0.0.1:8000`. Ensure browser permissions allow camera access.

### 2. cURL SSL Certificate Error on Local Windows
- **Cause:** Local PHP installation lacks a root certificate bundle `cacert.pem`.
- **Fix:** `includes/helpers.php` includes built-in SSL fallback handling and stream context recovery to ensure smooth operation on development servers without errors.

### 3. Rate Limit Exceeded (HTTP 429)
- **Cause:** More than 30 API requests were initiated within 60 seconds from the same IP address.
- **Fix:** Wait for the retry cooldown window (shown in JSON error header `Retry-After`), or adjust `RATE_LIMIT_MAX` in `includes/config.php` if higher limits are needed.

### 4. Duplicate License Plate Error (HTTP 409)
- **Cause:** The database enforces a `UNIQUE` constraint on `plate_number`.
- **Fix:** Each vehicle plate can only be registered once. If editing is needed, search for the plate in the Directory (`vehicles.php`).

---

## 📄 License
This software is provided under the MIT License. Designed for commercial and educational use.
