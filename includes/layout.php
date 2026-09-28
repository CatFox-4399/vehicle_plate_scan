<?php
/**
 * Layout Components (Header, Navbar, Footer)
 * Vehicle License Plate Scanner & Registration System
 */

require_once __DIR__ . '/helpers.php';

/**
 * Render standard page header and navigation
 *
 * @param string $pageTitle
 * @param string $activePage (index|vehicles|manual)
 * @param string $metaDescription
 * @return void
 */
function render_header(string $pageTitle, string $activePage = 'index', string $metaDescription = 'AI-powered Vehicle License Plate Scanner and automated registration system.'): void {
    $token = csrf_token();
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?php echo escape_html($token); ?>">
    <meta name="description" content="<?php echo escape_html($metaDescription); ?>">
    <meta name="theme-color" content="#090d16">

    <title><?php echo escape_html($pageTitle); ?> — AutoScan AI</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body>
    <!-- Ambient glowing backdrop effect -->
    <div class="ambient-glow glow-top-left" aria-hidden="true"></div>
    <div class="ambient-glow glow-bottom-right" aria-hidden="true"></div>

    <!-- Navigation Header -->
    <header class="app-header">
        <div class="container header-container">
            <a href="index.php" class="brand-logo" aria-label="AutoScan AI Home">
                <div class="logo-icon-wrap">
                    <svg class="logo-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="3" />
                        <line x1="6" y1="12" x2="18" y2="12" stroke-dasharray="2 2" />
                        <circle cx="6" cy="8" r="1" fill="currentColor" />
                        <circle cx="18" cy="8" r="1" fill="currentColor" />
                        <circle cx="6" cy="16" r="1" fill="currentColor" />
                        <circle cx="18" cy="16" r="1" fill="currentColor" />
                    </svg>
                    <span class="scan-laser-dot"></span>
                </div>
                <div class="brand-text">
                    <span class="brand-name">AutoScan<span class="brand-accent">AI</span></span>
                    <span class="brand-tagline" data-i18n="nav.tagline">Plate Scanner & Registry</span>
                </div>
            </a>

            <!-- Mobile Navigation Toggle -->
            <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle Navigation Menu" aria-expanded="false">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>

            <!-- Main Navigation Links -->
            <nav class="main-nav" id="mainNav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo $activePage === 'index' ? 'active' : ''; ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            <span data-i18n="nav.scan_register">Scanner & Register</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vehicles.php" class="nav-link <?php echo $activePage === 'vehicles' ? 'active' : ''; ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span data-i18n="nav.vehicles_directory">Registry Directory</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manual.php" class="nav-link <?php echo $activePage === 'manual' ? 'active' : ''; ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <span data-i18n="nav.system_manual">System Manual</span>
                        </a>
                    </li>
                </ul>

                <!-- Action Controls: Language Switcher & Status Pill -->
                <div class="nav-actions">
                    <!-- Live Rate Status Pill -->
                    <div class="status-pill" id="rateStatusPill" title="Live exchange rate connection status">
                        <span class="status-indicator"></span>
                        <span class="status-text" id="rateStatusText">Live FX Active</span>
                    </div>

                    <!-- Language Switcher -->
                    <div class="lang-switch-wrap">
                        <button type="button" class="lang-btn active" data-lang="en" id="langBtnEn" aria-label="Switch to English">
                            <span>EN</span>
                        </button>
                        <span class="lang-divider">/</span>
                        <button type="button" class="lang-btn" data-lang="zh" id="langBtnZh" aria-label="Switch to Chinese">
                            <span>中文</span>
                        </button>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Global Toast Container -->
    <div class="toast-container" id="toastContainer" aria-live="polite"></div>

    <!-- Main Content Wrapper -->
    <main class="main-content" id="mainContent">
    <?php
}

/**
 * Render standard page footer and scripts
 *
 * @param string $extraScripts
 * @return void
 */
function render_footer(string $extraScripts = ''): void {
    ?>
    </main>

    <!-- Application Footer -->
    <footer class="app-footer">
        <div class="container footer-container">
            <div class="footer-grid">
                <div class="footer-col brand-col">
                    <div class="footer-logo">
                        <span class="brand-name">AutoScan<span class="brand-accent">AI</span></span>
                    </div>
                    <p class="footer-desc" data-i18n="footer.desc">
                        Next-generation vehicle license plate recognition and dynamic registration system powered by Vanilla JS, WebRTC, and real-time exchange rates.
                    </p>
                    <div class="tech-pills">
                        <span class="tech-pill">PHP 8+</span>
                        <span class="tech-pill">MySQL InnoDB</span>
                        <span class="tech-pill">WebRTC</span>
                        <span class="tech-pill">Vanilla JS</span>
                        <span class="tech-pill">Zero Frameworks</span>
                    </div>
                </div>

                <div class="footer-col links-col">
                    <h4 class="footer-heading" data-i18n="footer.nav_title">Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="index.php" data-i18n="nav.scan_register">Scanner & Register</a></li>
                        <li><a href="vehicles.php" data-i18n="nav.vehicles_directory">Registry Directory</a></li>
                        <li><a href="manual.php" data-i18n="nav.system_manual">System Manual & API</a></li>
                    </ul>
                </div>

                <div class="footer-col security-col">
                    <h4 class="footer-heading" data-i18n="footer.security_title">Security & Integrity</h4>
                    <ul class="security-badges">
                        <li>
                            <svg class="badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            <span>PDO Prepared Statements (SQLi-Proof)</span>
                        </li>
                        <li>
                            <svg class="badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <span>Cryptographic CSRF Tokens</span>
                        </li>
                        <li>
                            <svg class="badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                            <span>Rate Limiting (30 req/min/IP)</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> AutoScan AI. <span data-i18n="footer.all_rights">All rights reserved. Designed for performance, reliability, and security.</span></p>
                <div class="footer-badges">
                    <span class="badge-pill">v<?php echo APP_VERSION; ?></span>
                    <span class="badge-pill">iFastNet Ready</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Core Scripts -->
    <script src="assets/js/i18n.js?v=<?php echo APP_VERSION; ?>"></script>
    <script src="assets/js/app.js?v=<?php echo APP_VERSION; ?>"></script>
    <?php if (!empty($extraScripts)): ?>
        <?php echo $extraScripts; ?>
    <?php endif; ?>
</body>
</html>
    <?php
}
