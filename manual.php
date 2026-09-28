<?php
/**
 * Vehicle License Plate Scanner & Registration System
 * Page: manual.php - System Usage Manual & API Documentation
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

render_header('System Manual & API Documentation', 'manual', 'Complete system usage manual, WebRTC troubleshooting, exchange rate architecture, and REST API documentation.');
?>

<div class="container">
    <header class="page-header">
        <div class="page-badge">
            <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span data-i18n="nav.system_manual">System Manual</span>
        </div>
        <h1 class="page-title">
            <span class="page-title-gradient" data-i18n="manual.title">System Usage Manual & Documentation</span>
        </h1>
        <p class="page-subtitle" data-i18n="manual.subtitle">
            Technical specifications, API guides, and troubleshooting workflows.
        </p>
    </header>

    <div class="manual-grid">
        <!-- Sticky Left Table of Contents -->
        <aside class="manual-sidebar">
            <a href="#overview" class="manual-nav-link" data-i18n="manual.nav_overview">1. Overview & Architecture</a>
            <a href="#scanner" class="manual-nav-link" data-i18n="manual.nav_scanner">2. Camera & Scanner Workflow</a>
            <a href="#exchange" class="manual-nav-link" data-i18n="manual.nav_exchange">3. Exchange Rate Engine</a>
            <a href="#api" class="manual-nav-link" data-i18n="manual.nav_api">4. REST API Documentation</a>
            <a href="#security" class="manual-nav-link" data-i18n="manual.nav_security">5. Security & Protection</a>
            <a href="#hosting" class="manual-nav-link" data-i18n="manual.nav_hosting">6. Shared Hosting (iFastNet)</a>
        </aside>

        <!-- Main Documentation Content -->
        <div class="manual-content">
            
            <!-- Section 1: Overview -->
            <section id="overview" class="manual-section">
                <h2>
                    <svg style="width:24px;height:24px;color:#06b6d4;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    1. System Architecture & Overview
                </h2>
                <p>
                    <strong>AutoScan AI</strong> is a standalone vehicle registration and license plate recognition system built strictly with standard native web technologies:
                    <strong>PHP 8+</strong>, <strong>MySQL 8 / MariaDB (InnoDB)</strong>, <strong>HTML5</strong>, <strong>CSS3</strong>, <strong>Vanilla JavaScript</strong>, and <strong>WebRTC</strong>.
                </p>
                <p>
                    No third-party PHP frameworks (such as Laravel or CodeIgniter) or JavaScript libraries (such as React, Vue, jQuery, or Bootstrap) are utilized, guaranteeing minimal server resource utilization and exceptional execution speed on budget shared hosting environments like iFastNet.
                </p>
                <div class="code-block">
Project Architecture:
/api/               → Pure RESTful JSON endpoints (scan, register, rates, vehicles)
/assets/css/        → Custom responsive cyber dark CSS design system
/assets/js/         → Native JS application core (WebRTC, Canvas, AJAX, i18n)
/includes/          → Configuration, DB singleton, helper functions, schema
                </div>
            </section>

            <!-- Section 2: Scanner -->
            <section id="scanner" class="manual-section">
                <h2>
                    <svg style="width:24px;height:24px;color:#06b6d4;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    2. Camera & Scanner Workflow
                </h2>
                <p>
                    The scanning module operates through two user-selectable channels:
                </p>
                <ol style="margin-left: 1.5rem; color: #94a3b8; line-height: 1.8;">
                    <li><strong>Real-time Camera Feed (WebRTC):</strong> Uses <code>navigator.mediaDevices.getUserMedia()</code> with rear camera priority (<code>facingMode: 'environment'</code>). Pressing <em>Capture & Scan Frame</em> snapshots the video feed directly to an off-screen canvas at full resolution.</li>
                    <li><strong>Static File Upload / Drag-and-Drop:</strong> Accepts high-resolution image files (JPG, PNG, WebP up to 8MB) via HTML5 drag-and-drop or file picker.</li>
                    <li><strong>OCR & Bounding Box:</strong> The captured image is sent asynchronously via AJAX to <code>/api/scan.php</code>. The server parses text, locates license plate patterns, and returns coordinate bounds. The frontend renders a glowing cyan bounding box over the vehicle preview and auto-fills the registration form.</li>
                </ol>
            </section>

            <!-- Section 3: Exchange Rate Engine -->
            <section id="exchange" class="manual-section">
                <h2>
                    <svg style="width:24px;height:24px;color:#06b6d4;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    3. Dynamic Exchange Rate Engine
                </h2>
                <p>
                    Vehicle registration fees are calculated dynamically based on real-time foreign exchange market values. To ensure 100% uptime and high performance:
                </p>
                <ul style="margin-left: 1.5rem; color: #94a3b8; line-height: 1.8;">
                    <li><strong>Primary API:</strong> <code>https://open.er-api.com/v6/latest/{BASE}</code></li>
                    <li><strong>Automatic Fallback:</strong> <code>https://api.frankfurter.dev/v1/latest?base={BASE}</code> if primary is unreachable or times out.</li>
                    <li><strong>MySQL Database Cache:</strong> Rates are persisted in table <code>rate_cache</code> with a 1-hour Time-to-Live (TTL). Repeated requests within 60 minutes are served directly from MySQL without incurring external network latency.</li>
                    <li><strong>Offline / Stale Fallback:</strong> If both external APIs fail, the system falls back to the latest cached rates or baseline values.</li>
                </ul>
            </section>

            <!-- Section 4: API Documentation -->
            <section id="api" class="manual-section">
                <h2>
                    <svg style="width:24px;height:24px;color:#06b6d4;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"></polyline>
                        <polyline points="8 6 2 12 8 18"></polyline>
                    </svg>
                    4. REST API Documentation
                </h2>
                <p>All endpoints enforce strict JSON output and rate limiting (maximum 30 requests/minute per IP address).</p>

                <!-- Endpoint 1: scan.php -->
                <h3 style="margin-top:1.5rem; color:#f8fafc;"><span class="endpoint-badge method-post">POST</span> /api/scan.php</h3>
                <p>Receives camera snapshot or uploaded file and extracts license plate text.</p>
                <div class="code-block">
// Request Payload (JSON or multipart/form-data)
{
  "image": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}

// Success Response (HTTP 200)
{
  "status": "success",
  "data": {
    "plate_number": "ABC1234",
    "confidence": 0.98,
    "box": { "x": 120, "y": 80, "width": 300, "height": 90 }
  }
}
                </div>

                <!-- Endpoint 2: register.php -->
                <h3 style="margin-top:2rem; color:#f8fafc;"><span class="endpoint-badge method-post">POST</span> /api/register.php</h3>
                <p>Validates vehicle specifications, verifies CSRF token, calculates converted fee, and stores vehicle into database.</p>
                <div class="code-block">
// Request Payload (JSON)
{
  "csrf_token": "a4f89d...",
  "plate_number": "ABC1234",
  "owner_name": "Alexander Tan",
  "phone": "+60123456789",
  "make": "Toyota",
  "model": "Corolla",
  "color": "Pearl White",
  "body_type": "Sedan",
  "currency": "USD"
}

// Success Response (HTTP 201)
{
  "status": "success",
  "message": "Vehicle successfully registered in the system.",
  "data": {
    "id": 4,
    "plate_number": "ABC1234",
    "base_fee": "50.00",
    "currency": "USD",
    "fee_converted": "50.00"
  }
}
                </div>

                <!-- Endpoint 3: rates.php -->
                <h3 style="margin-top:2rem; color:#f8fafc;"><span class="endpoint-badge method-get">GET</span> /api/rates.php</h3>
                <p>Returns cached/live exchange rates and precalculated fee tables for supported currencies.</p>
                <div class="code-block">
// Query Parameters: ?base=USD
// Success Response (HTTP 200)
{
  "status": "success",
  "data": {
    "base_currency": "USD",
    "base_fee": 50,
    "cached": true,
    "source": "open.er-api.com",
    "fees": {
      "USD": { "fee": 50.00, "rate": 1.0, "formatted": "$ 50.00" },
      "MYR": { "fee": 203.70, "rate": 4.074, "formatted": "RM 203.70" }
    }
  }
}
                </div>

                <!-- Endpoint 4: vehicles.php -->
                <h3 style="margin-top:2rem; color:#f8fafc;"><span class="endpoint-badge method-get">GET</span> /api/vehicles.php</h3>
                <p>AJAX endpoint for searching and paginating registered vehicles.</p>
                <div class="code-block">
// Query Parameters: ?page=1&limit=10&q=Toyota&body_type=Sedan&date_from=2026-01-01
// Success Response (HTTP 200)
{
  "status": "success",
  "data": {
    "vehicles": [ ... ],
    "pagination": {
      "total": 1,
      "page": 1,
      "limit": 10,
      "total_pages": 1
    },
    "stats": {
      "total_registered": 4,
      "today_registered": 1,
      "total_fees_usd": "200.00"
    }
  }
}
                </div>
            </section>

            <!-- Section 5: Security -->
            <section id="security" class="manual-section">
                <h2>
                    <svg style="width:24px;height:24px;color:#06b6d4;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    5. Security & Protection
                </h2>
                <ul style="margin-left: 1.5rem; color: #94a3b8; line-height: 1.8;">
                    <li><strong>SQL Injection Immunity:</strong> 100% of database interactions are executed using PDO prepared statements with strict parameter binding (<code>PDO::ATTR_EMULATE_PREPARES => false</code>).</li>
                    <li><strong>Cross-Site Scripting (XSS) Prevention:</strong> All user-supplied output is escaped via <code>htmlspecialchars($var, ENT_QUOTES, 'UTF-8')</code> before rendering.</li>
                    <li><strong>CSRF Token Protection:</strong> Every state-altering action requires a cryptographically secure 64-hex CSRF token verified via <code>hash_equals()</code>.</li>
                    <li><strong>API Rate Limiting:</strong> Database-backed rate limiter restricts endpoints to 30 requests per 60 seconds per IP address, preventing brute-force and DDoS attacks.</li>
                    <li><strong>Safe Error Handling:</strong> Technical database stack traces and credentials are logged privately to the server log and never revealed in client responses.</li>
                </ul>
            </section>

            <!-- Section 6: Hosting Deployment -->
            <section id="hosting" class="manual-section">
                <h2>
                    <svg style="width:24px;height:24px;color:#06b6d4;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                    </svg>
                    6. Shared Hosting (iFastNet) Deployment
                </h2>
                <p>To deploy on iFastNet or cPanel shared hosting:</p>
                <ol style="margin-left: 1.5rem; color: #94a3b8; line-height: 1.8;">
                    <li>Create a new MySQL database via <strong>MySQL Database Wizard</strong> in cPanel / VistaPanel.</li>
                    <li>Open <strong>phpMyAdmin</strong> and import <code>includes/schema.sql</code>.</li>
                    <li>Upload all project files to <code>/htdocs/</code> or <code>/public_html/</code> using FTP or File Manager.</li>
                    <li>Edit <code>includes/config.php</code> with your assigned database name, username, and password.</li>
                    <li>Ensure HTTPS is enabled via free SSL certificate (Let's Encrypt / ZeroSSL) so WebRTC camera permissions are permitted by mobile browsers.</li>
                </ol>
            </section>

        </div>
    </div>
</div>

<?php render_footer(); ?>
