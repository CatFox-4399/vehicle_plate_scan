<?php
/**
 * Vehicle License Plate Scanner & Registration System
 * Page: index.php - Scanner Dashboard & Registration Form
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

render_header('License Plate Scanner & Registration', 'index', 'Scan vehicle license plates in real-time using AI OCR and register vehicle specifications with live multi-currency fee conversion.');
?>

<div class="container">
    <!-- Hero / Title Section -->
    <header class="page-header">
        <div class="page-badge">
            <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polygon points="12 8 8 12 12 16 16 12 12 8"></polygon>
            </svg>
            <span>WebRTC + AI Optical Character Recognition</span>
        </div>
        <h1 class="page-title">
            <span class="page-title-gradient" data-i18n="header.title">AI Vehicle License Plate Scanner</span>
        </h1>
        <p class="page-subtitle" data-i18n="header.subtitle">
            Real-time WebRTC camera scanning, OCR plate extraction, and dynamic registration fee calculation.
        </p>
    </header>

    <!-- Main Scanner & Registration Grid -->
    <div class="scan-register-grid">
        
        <!-- ========================================================= -->
        <!-- Left Column: Camera Feed & Image Scanner -->
        <!-- ========================================================= -->
        <section class="card scanner-card" aria-labelledby="scanner-card-title">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                    </div>
                    <div>
                        <h2 class="card-title" id="scanner-card-title" data-i18n="scanner.title">Plate Recognition Scanner</h2>
                        <span class="card-subtitle" data-i18n="scanner.subtitle">Capture live camera frame or upload image</span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Mode Switch Tabs -->
                <div class="scanner-modes" role="tablist">
                    <button type="button" class="mode-tab-btn active" id="tabBtnCamera" role="tab" aria-selected="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="23 7 16 12 23 17 23 7"></polygon>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        <span data-i18n="scanner.tab_camera">Live Camera</span>
                    </button>
                    <button type="button" class="mode-tab-btn" id="tabBtnUpload" role="tab" aria-selected="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <span data-i18n="scanner.tab_upload">Upload File</span>
                    </button>
                </div>

                <!-- Camera Viewport Container -->
                <div id="cameraContainer">
                    <div class="camera-viewport">
                        <video id="cameraVideo" playsinline autoplay muted></video>
                        <canvas id="scanCanvas"></canvas>

                        <!-- High-Tech Reticle & Laser Overlay Guide -->
                        <div class="scanner-overlay-guide" id="scannerOverlay" style="display:none;">
                            <div class="reticle-frame">
                                <span class="reticle-corner corner-tl"></span>
                                <span class="reticle-corner corner-tr"></span>
                                <span class="reticle-corner corner-bl"></span>
                                <span class="reticle-corner corner-br"></span>
                                <div class="laser-line"></div>
                                <span class="reticle-instruction" data-i18n="scanner.reticle_guide">ALIGN LICENSE PLATE WITHIN BOX</span>
                            </div>
                        </div>

                        <!-- Camera Inactive Placeholder -->
                        <div class="camera-placeholder" id="cameraPlaceholder">
                            <svg class="placeholder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                                <path d="M21 21H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3m3-3h6l2 3h4a2 2 0 0 1 2 2v9.34"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                            <p data-i18n="scanner.camera_inactive">Camera is inactive</p>
                        </div>
                    </div>

                    <!-- Camera Action Controls -->
                    <div class="scanner-actions">
                        <button type="button" class="btn btn-secondary" id="btnStartCamera">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                            <span data-i18n="scanner.camera_start">Start Camera</span>
                        </button>

                        <button type="button" class="btn btn-secondary" id="btnStopCamera" style="display:none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="6" y="6" width="12" height="12"></rect>
                            </svg>
                            <span data-i18n="scanner.camera_stop">Stop Camera</span>
                        </button>

                        <button type="button" class="btn btn-secondary btn-icon-only" id="btnSwitchCamera" title="Flip Camera" style="display:none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                            </svg>
                        </button>

                        <button type="button" class="btn btn-primary" id="btnCapture" disabled style="flex:1;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <span data-i18n="scanner.capture_scan">Capture & Scan Frame</span>
                        </button>
                    </div>
                </div>

                <!-- Drag-and-Drop Image Upload Container -->
                <div id="uploadContainer" style="display:none;">
                    <div class="upload-dropzone" id="uploadDropzone">
                        <input type="file" id="fileInput" accept="image/jpeg,image/png,image/webp">
                        <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"></path>
                            <path d="M12 12v9"></path>
                            <path d="m8 16 4-4 4 4"></path>
                        </svg>
                        <span class="dropzone-title" data-i18n="scanner.drop_title">Drag & drop license plate image here</span>
                        <span class="dropzone-desc" data-i18n="scanner.drop_sub">Supports JPG, PNG, WebP (Max 8MB) or click to browse</span>
                    </div>
                </div>

                <!-- Processing Spinner Bar -->
                <div class="scan-processing-bar" id="scanProcessing">
                    <div class="spinner"></div>
                    <span class="processing-text" data-i18n="scanner.processing">Scanning plate with AI OCR engine...</span>
                </div>

                <!-- Scanned Result Card -->
                <div class="scan-result-card" id="scanResultCard" style="display:none;">
                    <div class="scan-result-left">
                        <span class="scan-result-label" data-i18n="scanner.result_detected">Detected Plate Number</span>
                        <span class="scan-result-plate" id="resultPlateNumber">---</span>
                    </div>
                    <div class="scan-result-meta">
                        <span class="confidence-badge" id="resultConfidence">98% Match</span>
                    </div>
                </div>

            </div>
        </section>


        <!-- ========================================================= -->
        <!-- Right Column: Manual Vehicle Registration Form -->
        <!-- ========================================================= -->
        <section class="card form-card" aria-labelledby="form-card-title">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h2 class="card-title" id="form-card-title" data-i18n="form.title">Vehicle Information Registration</h2>
                        <span class="card-subtitle" data-i18n="form.subtitle">Manual vehicle specifications for 100% data integrity</span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form id="registrationForm" class="registration-form" novalidate autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">

                    <!-- License Plate Field (Auto-filled by scanner) -->
                    <div class="form-group">
                        <label for="plate_number" class="form-label">
                            <span data-i18n="form.plate_number">License Plate Number</span>
                            <span class="label-tag" data-i18n="form.plate_tag">Scanner Auto-fill</span>
                        </label>
                        <div class="input-wrap">
                            <input 
                                type="text" 
                                id="plate_number" 
                                name="plate_number" 
                                class="form-input input-plate" 
                                placeholder="e.g., ABC1234" 
                                data-i18n-placeholder="form.plate_placeholder" 
                                maxlength="15" 
                                required
                            >
                        </div>
                        <span class="form-hint">Automatically filled by AI scanner or enter manually</span>
                    </div>

                    <!-- Owner & Phone Row -->
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="owner_name" class="form-label">
                                <span><span data-i18n="form.owner_name">Owner Full Name</span> <span class="required">*</span></span>
                            </label>
                            <input 
                                type="text" 
                                id="owner_name" 
                                name="owner_name" 
                                class="form-input" 
                                placeholder="e.g., Alexander Tan" 
                                data-i18n-placeholder="form.owner_placeholder" 
                                maxlength="100" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <span><span data-i18n="form.phone">Phone Number</span> <span class="required">*</span></span>
                            </label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-input" 
                                placeholder="e.g., +60123456789" 
                                data-i18n-placeholder="form.phone_placeholder" 
                                maxlength="20" 
                                required
                            >
                        </div>
                    </div>

                    <!-- Make & Model Row -->
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="make" class="form-label">
                                <span><span data-i18n="form.make">Vehicle Make / Brand</span> <span class="required">*</span></span>
                            </label>
                            <input 
                                type="text" 
                                id="make" 
                                name="make" 
                                class="form-input" 
                                placeholder="e.g., Toyota, Honda, Proton" 
                                data-i18n-placeholder="form.make_placeholder" 
                                maxlength="50" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="model" class="form-label">
                                <span><span data-i18n="form.model">Vehicle Model</span> <span class="required">*</span></span>
                            </label>
                            <input 
                                type="text" 
                                id="model" 
                                name="model" 
                                class="form-input" 
                                placeholder="e.g., Corolla, Civic, Saga" 
                                data-i18n-placeholder="form.model_placeholder" 
                                maxlength="50" 
                                required
                            >
                        </div>
                    </div>

                    <!-- Color & Body Type Row -->
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="color" class="form-label">
                                <span><span data-i18n="form.color">Vehicle Color</span> <span class="required">*</span></span>
                            </label>
                            <input 
                                type="text" 
                                id="color" 
                                name="color" 
                                class="form-input" 
                                placeholder="e.g., Pearl White, Obsidian Black" 
                                data-i18n-placeholder="form.color_placeholder" 
                                maxlength="30" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="body_type" class="form-label">
                                <span><span data-i18n="form.body_type">Vehicle Body Type</span> <span class="required">*</span></span>
                            </label>
                            <select id="body_type" name="body_type" class="form-select" required>
                                <option value="" disabled selected data-i18n="form.select_body_type">Select body type...</option>
                                <?php foreach (ALLOWED_BODY_TYPES as $type): ?>
                                    <option value="<?php echo escape_html($type); ?>"><?php echo escape_html($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Live Exchange Rate Fee Converter Card -->
                    <div class="currency-fee-card">
                        <div class="currency-fee-header">
                            <span class="fee-title" data-i18n="form.currency">Preferred Payment Currency</span>
                            <span class="fee-exchange-rate" id="feeExchangeRate">1 USD = 1.0000 USD</span>
                        </div>

                        <div class="fee-display-row">
                            <div class="fee-amount-group">
                                <span class="fee-converted-amount" id="feeConvertedAmount">$ 50.00</span>
                                <span class="fee-original-amount" id="feeOriginalAmount">Base: $50.00 USD</span>
                            </div>

                            <div class="currency-selector-wrap">
                                <select id="currency" name="currency" class="form-select">
                                    <?php foreach (SUPPORTED_CURRENCIES as $code => $curr): ?>
                                        <option value="<?php echo escape_html($code); ?>" <?php echo $code === 'USD' ? 'selected' : ''; ?>>
                                            <?php echo escape_html($code); ?> - <?php echo escape_html($curr['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-success" id="btnSubmitForm" style="width: 100%; padding: 0.95rem; font-size: 1.05rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span data-i18n="form.submit_btn">Confirm & Register Vehicle</span>
                    </button>
                </form>
            </div>
        </section>

    </div>
</div>

<?php render_footer(); ?>
