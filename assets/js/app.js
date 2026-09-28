/**
 * AutoScan AI - Core Application Logic
 * Pure Vanilla JavaScript (No Frameworks)
 * Handles: WebRTC, OCR Scanner, Drag & Drop, Bounding Box, FX Converter, Registry AJAX
 */

(function () {
    'use strict';

    // Application State
    const state = {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        stream: null,
        facingMode: 'environment', // Rear camera default for scanning plates
        ratesData: null,
        selectedCurrency: 'USD',
        baseFeeUSD: 50.00,
        totalBaseRevenueUSD: 0,
        activeTab: 'camera', // 'camera' or 'upload'
        lastCapturedDataUrl: null
    };

    /**
     * DOM Element Cache
     */
    const dom = {
        toastContainer: document.getElementById('toastContainer'),
        mobileNavToggle: document.getElementById('mobileNavToggle'),
        mainNav: document.getElementById('mainNav'),
        rateStatusText: document.getElementById('rateStatusText'),
        rateStatusPill: document.getElementById('rateStatusPill'),

        // Scanner Elements
        tabBtnCamera: document.getElementById('tabBtnCamera'),
        tabBtnUpload: document.getElementById('tabBtnUpload'),
        cameraContainer: document.getElementById('cameraContainer'),
        uploadContainer: document.getElementById('uploadContainer'),
        cameraVideo: document.getElementById('cameraVideo'),
        scanCanvas: document.getElementById('scanCanvas'),
        cameraPlaceholder: document.getElementById('cameraPlaceholder'),
        btnStartCamera: document.getElementById('btnStartCamera'),
        btnStopCamera: document.getElementById('btnStopCamera'),
        btnSwitchCamera: document.getElementById('btnSwitchCamera'),
        btnCapture: document.getElementById('btnCapture'),
        uploadDropzone: document.getElementById('uploadDropzone'),
        fileInput: document.getElementById('fileInput'),
        scanProcessing: document.getElementById('scanProcessing'),
        scanResultCard: document.getElementById('scanResultCard'),
        resultPlateNumber: document.getElementById('resultPlateNumber'),
        resultConfidence: document.getElementById('resultConfidence'),
        scannerOverlay: document.getElementById('scannerOverlay'),

        // Form Elements
        registrationForm: document.getElementById('registrationForm'),
        inputPlate: document.getElementById('plate_number'),
        inputOwner: document.getElementById('owner_name'),
        inputPhone: document.getElementById('phone'),
        inputMake: document.getElementById('make'),
        inputModel: document.getElementById('model'),
        inputColor: document.getElementById('color'),
        selectBodyType: document.getElementById('body_type'),
        selectCurrency: document.getElementById('currency'),
        feeConvertedAmount: document.getElementById('feeConvertedAmount'),
        feeOriginalAmount: document.getElementById('feeOriginalAmount'),
        feeExchangeRate: document.getElementById('feeExchangeRate'),
        btnSubmitForm: document.getElementById('btnSubmitForm'),

        // Vehicles Registry Elements
        registryTableBody: document.getElementById('registryTableBody'),
        searchQuery: document.getElementById('searchQuery'),
        filterBodyType: document.getElementById('filterBodyType'),
        filterDateFrom: document.getElementById('filterDateFrom'),
        filterDateTo: document.getElementById('filterDateTo'),
        btnResetFilters: document.getElementById('btnResetFilters'),
        btnExportCsv: document.getElementById('btnExportCsv'),
        paginationWrap: document.getElementById('paginationWrap'),
        paginationInfo: document.getElementById('paginationInfo'),
        paginationButtons: document.getElementById('paginationButtons'),
        statTotalRegistered: document.getElementById('statTotalRegistered'),
        statTodayRegistered: document.getElementById('statTodayRegistered'),
        statTotalFees: document.getElementById('statTotalFees'),
        statRevenueCurrency: document.getElementById('statRevenueCurrency'),
        currentRevenueCurr: document.getElementById('currentRevenueCurr')
    };

    /**
     * Toast Notification System
     *
     * @param {'success'|'error'|'info'} type
     * @param {string} title
     * @param {string} message
     * @param {number} duration (ms)
     */
    function showToast(type, title, message, duration = 4500) {
        if (!dom.toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: `<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`,
            error: `<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`,
            info: `<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
        };

        toast.innerHTML = `
            ${icons[type] || icons.info}
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" aria-label="Close">&times;</button>
        `;

        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            setTimeout(() => toast.remove(), 250);
        });

        dom.toastContainer.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                setTimeout(() => toast.remove(), 250);
            }
        }, duration);
    }

    /**
     * Mobile Navigation Menu Toggle
     */
    if (dom.mobileNavToggle && dom.mainNav) {
        dom.mobileNavToggle.addEventListener('click', () => {
            const isOpen = dom.mainNav.classList.toggle('open');
            dom.mobileNavToggle.setAttribute('aria-expanded', isOpen);
        });
    }

    /**
     * =====================================================================
     * WebRTC Camera Scanner Engine
     * =====================================================================
     */

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showToast('error', 'Camera Error', window.t ? window.t('scanner.error_no_camera') : 'Camera not supported in this browser.');
            return;
        }

        try {
            stopCamera();

            const constraints = {
                audio: false,
                video: {
                    facingMode: state.facingMode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            state.stream = stream;

            if (dom.cameraVideo) {
                dom.cameraVideo.srcObject = stream;
                dom.cameraVideo.style.display = 'block';
                await dom.cameraVideo.play();
            }

            if (dom.cameraPlaceholder) dom.cameraPlaceholder.style.display = 'none';
            if (dom.btnStartCamera) dom.btnStartCamera.style.display = 'none';
            if (dom.btnStopCamera) dom.btnStopCamera.style.display = 'inline-flex';
            if (dom.btnSwitchCamera) dom.btnSwitchCamera.style.display = 'inline-flex';
            if (dom.btnCapture) dom.btnCapture.disabled = false;
            if (dom.scannerOverlay) dom.scannerOverlay.style.display = 'flex';

            clearCanvasOverlay();
        } catch (err) {
            console.error('Camera initialization failed:', err);
            showToast('error', 'Camera Permission Denied', window.t ? window.t('scanner.error_no_camera') : 'Unable to access camera feed.');
        }
    }

    function stopCamera() {
        if (state.stream) {
            state.stream.getTracks().forEach(track => track.stop());
            state.stream = null;
        }
        if (dom.cameraVideo) {
            dom.cameraVideo.srcObject = null;
            dom.cameraVideo.style.display = 'none';
        }
        if (dom.cameraPlaceholder) dom.cameraPlaceholder.style.display = 'flex';
        if (dom.btnStartCamera) dom.btnStartCamera.style.display = 'inline-flex';
        if (dom.btnStopCamera) dom.btnStopCamera.style.display = 'none';
        if (dom.btnSwitchCamera) dom.btnSwitchCamera.style.display = 'none';
        if (dom.btnCapture) dom.btnCapture.disabled = true;
        if (dom.scannerOverlay) dom.scannerOverlay.style.display = 'none';

        // Clear any bounding box or snapshot preview left on the canvas
        clearCanvasOverlay();
    }

    function switchCamera() {
        state.facingMode = (state.facingMode === 'environment') ? 'user' : 'environment';
        startCamera();
    }

    /**
     * Capture frame snapshot from video element
     */
    function captureFrame() {
        if (!dom.cameraVideo || !state.stream) return;

        const video = dom.cameraVideo;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 1280;
        canvas.height = video.videoHeight || 720;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.90);
        state.lastCapturedDataUrl = dataUrl;

        // Draw snapshot preview onto main viewport overlay canvas
        renderCanvasPreview(dataUrl);

        // Send to OCR API
        processPlateImage(dataUrl);
    }

    /**
     * Clear and reset canvas overlay
     */
    function clearCanvasOverlay() {
        if (!dom.scanCanvas) return;
        const ctx = dom.scanCanvas.getContext('2d');
        ctx.clearRect(0, 0, dom.scanCanvas.width, dom.scanCanvas.height);
        dom.scanCanvas.width = 0;
        dom.scanCanvas.height = 0;
    }

    /**
     * Draw image and bounding box overlay on viewport canvas
     *
     * @param {string} dataUrl
     * @param {object|null} box { x, y, width, height }
     * @param {string|null} label
     */
    function renderCanvasPreview(dataUrl, box = null, label = null) {
        if (!dom.scanCanvas) return;

        const img = new Image();
        img.onload = () => {
            const canvas = dom.scanCanvas;
            const parent = canvas.parentElement;
            canvas.width = parent.clientWidth || 640;
            canvas.height = parent.clientHeight || 400;

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // In upload mode, render the uploaded image onto the canvas
            if (state.activeTab === 'upload') {
                // Fit image maintaining aspect ratio
                const hRatio = canvas.width / img.width;
                const vRatio = canvas.height / img.height;
                const ratio  = Math.min(hRatio, vRatio);
                const centerShiftX = (canvas.width - img.width * ratio) / 2;
                const centerShiftY = (canvas.height - img.height * ratio) / 2;
                ctx.drawImage(img, 0, 0, img.width, img.height, centerShiftX, centerShiftY, img.width * ratio, img.height * ratio);
            }

            // Draw bounding box if provided
            if (box && box.width > 0 && box.height > 0) {
                drawBoundingBox(ctx, canvas, img, box, label);
            }
        };
        img.src = dataUrl;
    }

    /**
     * Draw high-tech bounding box with neon corners and label
     */
    function drawBoundingBox(ctx, canvas, origImg, box, label) {
        const scaleX = canvas.width / (origImg.width || canvas.width);
        const scaleY = canvas.height / (origImg.height || canvas.height);

        const bx = box.x * scaleX;
        const by = box.y * scaleY;
        const bw = box.width * scaleX;
        const bh = box.height * scaleY;

        ctx.save();

        // Semi-transparent highlight fill
        ctx.fillStyle = 'rgba(6, 182, 212, 0.15)';
        ctx.fillRect(bx, by, bw, bh);

        // Neon outline
        ctx.strokeStyle = '#06b6d4';
        ctx.lineWidth = 3;
        ctx.shadowColor = '#06b6d4';
        ctx.shadowBlur = 12;
        ctx.strokeRect(bx, by, bw, bh);

        // Highlight corners
        const cLen = 14;
        ctx.strokeStyle = '#38bdf8';
        ctx.lineWidth = 4;

        // Top-Left Corner
        ctx.beginPath();
        ctx.moveTo(bx, by + cLen);
        ctx.lineTo(bx, by);
        ctx.lineTo(bx + cLen, by);
        ctx.stroke();

        // Top-Right Corner
        ctx.beginPath();
        ctx.moveTo(bx + bw - cLen, by);
        ctx.lineTo(bx + bw, by);
        ctx.lineTo(bx + bw, by + cLen);
        ctx.stroke();

        // Bottom-Left Corner
        ctx.beginPath();
        ctx.moveTo(bx, by + bh - cLen);
        ctx.lineTo(bx, by + bh);
        ctx.lineTo(bx + cLen, by + bh);
        ctx.stroke();

        // Bottom-Right Corner
        ctx.beginPath();
        ctx.moveTo(bx + bw - cLen, by + bh);
        ctx.lineTo(bx + bw, by + bh);
        ctx.lineTo(bx + bw, by + bh - cLen);
        ctx.stroke();

        // Label Tag Box
        if (label) {
            ctx.shadowBlur = 0;
            ctx.font = 'bold 13px "JetBrains Mono", monospace';
            const textWidth = ctx.measureText(label).width;
            const tagHeight = 22;
            const tagWidth = textWidth + 16;
            const tagY = Math.max(0, by - tagHeight);

            ctx.fillStyle = '#06b6d4';
            ctx.fillRect(bx, tagY, tagWidth, tagHeight);

            ctx.fillStyle = '#090d16';
            ctx.fillText(label, bx + 8, tagY + 16);
        }

        ctx.restore();
    }

    /**
     * =====================================================================
     * OCR Plate Scanner AJAX Request
     * =====================================================================
     */
    async function processPlateImage(imageData) {
        setScanningState(true);

        try {
            const response = await fetch('api/scan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': state.csrfToken
                },
                body: JSON.stringify({ image: imageData })
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                handleScanSuccess(result.data, imageData);
            } else {
                handleScanError(result);
            }
        } catch (err) {
            console.error('Scan request error:', err);
            showToast('error', 'Scanner Error', 'Failed to reach OCR scanning service. Please check connection.');
        } finally {
            setScanningState(false);
        }
    }

    function setScanningState(isScanning) {
        if (dom.scanProcessing) {
            dom.scanProcessing.classList.toggle('active', isScanning);
        }
        if (dom.btnCapture) {
            dom.btnCapture.disabled = isScanning;
        }
        if (dom.scannerOverlay) {
            const laser = dom.scannerOverlay.querySelector('.laser-line');
            if (laser) laser.style.animationDuration = isScanning ? '1s' : '2.6s';
        }
    }

    function handleScanSuccess(data, originalImage) {
        const plate = data.plate_number;
        const confPercent = Math.round((data.confidence || 0.95) * 100);

        // Display Result Card
        if (dom.scanResultCard) {
            dom.scanResultCard.style.display = 'flex';
            if (dom.resultPlateNumber) dom.resultPlateNumber.textContent = plate;
            if (dom.resultConfidence) dom.resultConfidence.textContent = `${confPercent}% Match`;
        }

        // Render Bounding Box on Canvas
        renderCanvasPreview(originalImage, data.box, plate);

        // Auto-fill into registration form
        if (dom.inputPlate) {
            dom.inputPlate.value = plate;
            dom.inputPlate.classList.remove('flash-autofill');
            void dom.inputPlate.offsetWidth; // Trigger reflow
            dom.inputPlate.classList.add('flash-autofill');

            // Scroll to form smoothly if on mobile
            if (window.innerWidth <= 1024) {
                dom.inputPlate.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        showToast('success', 'License Plate Detected', `Extracted "${plate}" with ${confPercent}% confidence. Auto-filled into form.`);
    }

    function handleScanError(result) {
        const message = result.message || (window.t ? window.t('scanner.error_unreadable') : 'Unable to read license plate clearly. Please adjust lighting or reposition the camera.');
        const tip = result.actionable_tip ? `<br><small style="color:#94a3b8">${result.actionable_tip}</small>` : '';

        showToast('error', 'Scanner Notice', message + tip, 6000);
    }

    /**
     * =====================================================================
     * Drag & Drop / File Upload Handler
     * =====================================================================
     */
    function setupUploadDropzone() {
        const zone = dom.uploadDropzone;
        const fileInput = dom.fileInput;
        if (!zone || !fileInput) return;

        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('dragover');
            });
        });

        zone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleUploadedFile(files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                handleUploadedFile(fileInput.files[0]);
            }
        });
    }

    function handleUploadedFile(file) {
        if (!file.type.match(/^image\/(jpeg|png|webp)$/i)) {
            showToast('error', 'Invalid File Type', 'Please upload a valid JPEG, PNG, or WebP image file.');
            return;
        }

        if (file.size > 8 * 1024 * 1024) {
            showToast('error', 'File Too Large', 'Maximum image file size is 8MB.');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            const dataUrl = e.target.result;
            state.lastCapturedDataUrl = dataUrl;

            // Switch to camera viewport or render on upload canvas
            renderCanvasPreview(dataUrl);

            // Send to OCR
            processPlateImage(dataUrl);
        };
        reader.readAsDataURL(file);
    }

    /**
     * =====================================================================
     * Scanner Mode Tabs (Live Camera vs Upload)
     * =====================================================================
     */
    function setupScannerTabs() {
        if (!dom.tabBtnCamera || !dom.tabBtnUpload) return;

        dom.tabBtnCamera.addEventListener('click', () => {
            state.activeTab = 'camera';
            dom.tabBtnCamera.classList.add('active');
            dom.tabBtnUpload.classList.remove('active');
            if (dom.cameraContainer) dom.cameraContainer.style.display = 'block';
            if (dom.uploadContainer) dom.uploadContainer.style.display = 'none';
            startCamera();
        });

        dom.tabBtnUpload.addEventListener('click', () => {
            state.activeTab = 'upload';
            dom.tabBtnUpload.classList.add('active');
            dom.tabBtnCamera.classList.remove('active');
            stopCamera();
            if (dom.cameraContainer) dom.cameraContainer.style.display = 'none';
            if (dom.uploadContainer) dom.uploadContainer.style.display = 'block';
        });
    }

    /**
     * =====================================================================
     * Live Foreign Exchange Rates Engine
     * =====================================================================
     */
    async function loadExchangeRates(base = 'USD') {
        try {
            const response = await fetch(`api/rates.php?base=${encodeURIComponent(base)}`);
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                state.ratesData = result.data;
                updateRateStatusIndicator(result.data);
                updateFeeCalculation();
                updateTotalRevenueDisplay();
            } else {
                console.warn('Failed to load exchange rates:', result);
            }
        } catch (err) {
            console.error('Exchange rate fetch error:', err);
        }
    }

    function updateRateStatusIndicator(data) {
        if (!dom.rateStatusText || !dom.rateStatusPill) return;

        if (data.cached) {
            dom.rateStatusText.textContent = 'Cached FX (1h)';
        } else {
            dom.rateStatusText.textContent = `Live FX (${data.source})`;
        }
    }

    function updateFeeCalculation() {
        if (!state.ratesData || !dom.selectCurrency) return;

        const currency = dom.selectCurrency.value || 'USD';
        const feeInfo = state.ratesData.fees ? state.ratesData.fees[currency] : null;

        if (feeInfo) {
            if (dom.feeConvertedAmount) {
                dom.feeConvertedAmount.textContent = feeInfo.formatted;
            }
            if (dom.feeOriginalAmount) {
                dom.feeOriginalAmount.textContent = `Base: $${state.baseFeeUSD.toFixed(2)} USD`;
            }
            if (dom.feeExchangeRate) {
                dom.feeExchangeRate.textContent = `1 USD = ${feeInfo.rate.toFixed(4)} ${currency}`;
            }
        }
    }

    /**
     * =====================================================================
     * Vehicle Registration Form Handler
     * =====================================================================
     */
    function setupRegistrationForm() {
        const form = dom.registrationForm;
        if (!form) return;

        // Recalculate fee dynamically on currency change
        if (dom.selectCurrency) {
            dom.selectCurrency.addEventListener('change', updateFeeCalculation);
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Client-side validation
            const plate = (dom.inputPlate?.value || '').trim();
            const owner = (dom.inputOwner?.value || '').trim();
            const phone = (dom.inputPhone?.value || '').trim();
            const make = (dom.inputMake?.value || '').trim();
            const model = (dom.inputModel?.value || '').trim();
            const color = (dom.inputColor?.value || '').trim();
            const bodyType = dom.selectBodyType?.value || '';
            const currency = dom.selectCurrency?.value || 'USD';

            if (!plate) {
                showToast('error', 'Validation Error', 'License plate number is required.');
                dom.inputPlate?.focus();
                return;
            }

            if (!owner || owner.length < 2) {
                showToast('error', 'Validation Error', 'Please enter a valid owner full name.');
                dom.inputOwner?.focus();
                return;
            }

            if (!phone || !phone.match(/^\+?[0-9\s\-\(\)]{7,20}$/)) {
                showToast('error', 'Validation Error', 'Please enter a valid phone number (e.g. +60123456789).');
                dom.inputPhone?.focus();
                return;
            }

            if (!make) {
                showToast('error', 'Validation Error', 'Vehicle make/brand is required.');
                dom.inputMake?.focus();
                return;
            }

            if (!model) {
                showToast('error', 'Validation Error', 'Vehicle model is required.');
                dom.inputModel?.focus();
                return;
            }

            if (!color) {
                showToast('error', 'Validation Error', 'Vehicle color is required.');
                dom.inputColor?.focus();
                return;
            }

            if (!bodyType) {
                showToast('error', 'Validation Error', 'Please select a vehicle body type.');
                dom.selectBodyType?.focus();
                return;
            }

            // Submit form via Fetch API
            if (dom.btnSubmitForm) {
                dom.btnSubmitForm.disabled = true;
                dom.btnSubmitForm.textContent = window.t ? window.t('form.submitting') : 'Registering Vehicle...';
            }

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': state.csrfToken
                    },
                    body: JSON.stringify({
                        csrf_token: state.csrfToken,
                        plate_number: plate,
                        owner_name: owner,
                        phone: phone,
                        make: make,
                        model: model,
                        color: color,
                        body_type: bodyType,
                        currency: currency
                    })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    // Update CSRF token if rotated
                    if (result.data?.new_csrf_token) {
                        state.csrfToken = result.data.new_csrf_token;
                    }

                    showToast(
                        'success',
                        'Registration Complete',
                        `Vehicle [${result.data.plate_number}] successfully registered for ${result.data.currency_symbol} ${result.data.fee_converted}!`,
                        6000
                    );

                    // Reset form fields
                    form.reset();
                    updateFeeCalculation();

                    // Hide scan result banner
                    if (dom.scanResultCard) dom.scanResultCard.style.display = 'none';
                    clearCanvasOverlay();

                    // Optional redirect prompt
                    setTimeout(() => {
                        if (confirm('Vehicle registered successfully! Would you like to view the vehicle in the Registry Directory?')) {
                            window.location.href = 'vehicles.php';
                        }
                    }, 1200);

                } else {
                    showToast('error', 'Registration Failed', result.message || 'Could not complete registration.');
                }
            } catch (err) {
                console.error('Registration network error:', err);
                showToast('error', 'Server Error', 'Failed to reach server. Please try again.');
            } finally {
                if (dom.btnSubmitForm) {
                    dom.btnSubmitForm.disabled = false;
                    dom.btnSubmitForm.textContent = window.t ? window.t('form.submit_btn') : 'Confirm & Register Vehicle';
                }
            }
        });
    }

    /**
     * =====================================================================
     * Vehicles Registry Directory AJAX Search & Pagination (Vehicles Page)
     * =====================================================================
     */
    let searchDebounceTimer = null;
    let currentRegistryPage = 1;

    async function loadVehiclesDirectory(page = 1) {
        if (!dom.registryTableBody) return;

        currentRegistryPage = page;
        const q = (dom.searchQuery?.value || '').trim();
        const bodyType = dom.filterBodyType?.value || '';
        const dateFrom = dom.filterDateFrom?.value || '';
        const dateTo = dom.filterDateTo?.value || '';

        const params = new URLSearchParams({
            page: page,
            limit: 10,
            q: q,
            body_type: bodyType,
            date_from: dateFrom,
            date_to: dateTo
        });

        // Show table loading state
        dom.registryTableBody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center; padding: 2.5rem; color: #94a3b8;">
                    <div class="spinner" style="margin: 0 auto 0.75rem;"></div>
                    <span>Loading vehicle records...</span>
                </td>
            </tr>
        `;

        try {
            const response = await fetch(`api/vehicles.php?${params.toString()}`);
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                renderVehiclesTable(result.data.vehicles);
                renderPagination(result.data.pagination);
                renderRegistryStats(result.data.stats);
            } else {
                dom.registryTableBody.innerHTML = `
                    <tr><td colspan="6" style="text-align:center; color: #f43f5e; padding: 2rem;">${result.message || 'Failed to load records.'}</td></tr>
                `;
            }
        } catch (err) {
            console.error('Registry directory fetch error:', err);
            dom.registryTableBody.innerHTML = `
                <tr><td colspan="6" style="text-align:center; color: #f43f5e; padding: 2rem;">Error connecting to registry database.</td></tr>
            `;
        }
    }

    function renderVehiclesTable(vehicles) {
        if (!dom.registryTableBody) return;

        if (!vehicles || vehicles.length === 0) {
            dom.registryTableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center; padding: 3rem; color: #94a3b8;">
                        <svg style="width:40px;height:40px;opacity:0.5;margin-bottom:0.5rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                        <p>${window.t ? window.t('registry.no_records') : 'No registered vehicles found.'}</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        vehicles.forEach(v => {
            html += `
                <tr>
                    <td>
                        <span class="plate-badge">${v.plate_number}</span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:#f8fafc;">${v.owner_name}</div>
                        <div style="font-size:0.75rem; color:#94a3b8;">${v.phone}</div>
                    </td>
                    <td>
                        <div style="font-weight:500;">${v.make} ${v.model}</div>
                        <div style="display:flex; gap:0.4rem; margin-top:0.2rem; align-items:center;">
                            <span class="body-type-tag">${v.body_type}</span>
                            <span style="font-size:0.75rem; color:#64748b;">${v.color}</span>
                        </div>
                    </td>
                    <td>
                        <div class="fee-cell">${v.fee_formatted}</div>
                        <span class="fee-sub">Base: $${v.base_fee} USD</span>
                    </td>
                    <td style="font-size:0.825rem; color:#94a3b8; white-space:nowrap;">
                        ${v.created_human}
                    </td>
                    <td>
                        <div style="display:flex; gap:0.4rem; align-items:center;">
                            <button type="button" class="btn btn-secondary btn-icon-only" style="padding:0.4rem 0.65rem;" title="View Details" onclick="window.viewVehicleModal(${JSON.stringify(v).replace(/"/g, '&quot;')})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-secondary btn-icon-only btn-delete-row" style="padding:0.4rem 0.65rem;" title="Delete Vehicle" onclick="window.confirmDeleteVehicle(${v.id}, '${v.plate_number}')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent-rose); width:16px; height:16px;">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        dom.registryTableBody.innerHTML = html;
    }

    function renderPagination(pagination) {
        if (!dom.paginationWrap || !pagination) return;

        const { total, page, total_pages, limit } = pagination;
        const start = total === 0 ? 0 : (page - 1) * limit + 1;
        const end = Math.min(page * limit, total);

        if (dom.paginationInfo) {
            dom.paginationInfo.textContent = `Showing ${start} - ${end} of ${total} records`;
        }

        if (!dom.paginationButtons) return;

        let btnHtml = '';

        // Prev Button
        btnHtml += `
            <button type="button" class="page-btn" ${!pagination.has_prev ? 'disabled' : ''} onclick="window.changeRegistryPage(${page - 1})">
                &larr;
            </button>
        `;

        // Page Numbers (sliding window)
        const maxVisible = 5;
        let startPage = Math.max(1, page - 2);
        let endPage = Math.min(total_pages, startPage + maxVisible - 1);
        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        for (let p = startPage; p <= endPage; p++) {
            btnHtml += `
                <button type="button" class="page-btn ${p === page ? 'active' : ''}" onclick="window.changeRegistryPage(${p})">
                    ${p}
                </button>
            `;
        }

        // Next Button
        btnHtml += `
            <button type="button" class="page-btn" ${!pagination.has_next ? 'disabled' : ''} onclick="window.changeRegistryPage(${page + 1})">
                &rarr;
            </button>
        `;

        dom.paginationButtons.innerHTML = btnHtml;
    }

    function updateTotalRevenueDisplay() {
        if (!dom.statTotalFees) return;
        const selectedCurr = dom.statRevenueCurrency ? dom.statRevenueCurrency.value : (localStorage.getItem('revenue_currency') || 'USD');
        const rate = (state.ratesData && state.ratesData.rates && state.ratesData.rates[selectedCurr]) ? parseFloat(state.ratesData.rates[selectedCurr]) : 1.0;
        const totalConverted = (state.totalBaseRevenueUSD || 0) * rate;

        const symbols = {
            USD: '$', EUR: '€', GBP: '£', MYR: 'RM', CNY: '¥', SGD: 'S$', JPY: '¥', AUD: 'A$', CAD: 'C$', THB: '฿', IDR: 'Rp'
        };
        const symbol = symbols[selectedCurr] || (selectedCurr + ' ');

        dom.statTotalFees.textContent = `${symbol} ${totalConverted.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (dom.currentRevenueCurr) {
            dom.currentRevenueCurr.textContent = selectedCurr;
        }
    }

    function renderRegistryStats(stats) {
        if (!stats) return;
        if (dom.statTotalRegistered) dom.statTotalRegistered.textContent = stats.total_registered.toLocaleString();
        if (dom.statTodayRegistered) dom.statTodayRegistered.textContent = stats.today_registered.toLocaleString();
        state.totalBaseRevenueUSD = parseFloat(String(stats.total_fees_usd || '0').replace(/,/g, '')) || 0;
        updateTotalRevenueDisplay();
    }

    function setupRegistryFilters() {
        if (dom.statRevenueCurrency) {
            const savedCurr = localStorage.getItem('revenue_currency') || 'USD';
            dom.statRevenueCurrency.value = savedCurr;
            dom.statRevenueCurrency.addEventListener('change', (e) => {
                localStorage.setItem('revenue_currency', e.target.value);
                updateTotalRevenueDisplay();
            });
        }

        if (!dom.searchQuery) return;

        dom.searchQuery.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                loadVehiclesDirectory(1);
            }, 300);
        });

        if (dom.filterBodyType) {
            dom.filterBodyType.addEventListener('change', () => loadVehiclesDirectory(1));
        }

        if (dom.filterDateFrom) {
            dom.filterDateFrom.addEventListener('change', () => loadVehiclesDirectory(1));
        }

        if (dom.filterDateTo) {
            dom.filterDateTo.addEventListener('change', () => loadVehiclesDirectory(1));
        }

        if (dom.btnResetFilters) {
            dom.btnResetFilters.addEventListener('click', () => {
                if (dom.searchQuery) dom.searchQuery.value = '';
                if (dom.filterBodyType) dom.filterBodyType.value = '';
                if (dom.filterDateFrom) dom.filterDateFrom.value = '';
                if (dom.filterDateTo) dom.filterDateTo.value = '';
                loadVehiclesDirectory(1);
            });
        }

        if (dom.btnExportCsv) {
            dom.btnExportCsv.addEventListener('click', exportRegistryToCSV);
        }
    }

    async function exportRegistryToCSV() {
        try {
            const response = await fetch('api/vehicles.php?limit=1000');
            const result = await response.json();

            if (!result.data || !result.data.vehicles || result.data.vehicles.length === 0) {
                showToast('info', 'Export CSV', 'No vehicle data available to export.');
                return;
            }

            const rows = [
                ['ID', 'Plate Number', 'Owner Name', 'Phone', 'Make', 'Model', 'Color', 'Body Type', 'Base Fee USD', 'Currency', 'Fee Converted', 'Registration Date']
            ];

            result.data.vehicles.forEach(v => {
                rows.push([
                    v.id,
                    `"${v.plate_number}"`,
                    `"${v.owner_name}"`,
                    `"${v.phone}"`,
                    `"${v.make}"`,
                    `"${v.model}"`,
                    `"${v.color}"`,
                    `"${v.body_type}"`,
                    v.base_fee,
                    v.currency,
                    v.fee_converted,
                    `"${v.created_at}"`
                ]);
            });

            const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + rows.map(e => e.join(',')).join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', `vehicles_registry_${new Date().toISOString().slice(0, 10)}.csv`);
            document.body.appendChild(link);
            link.click();
            link.remove();

            showToast('success', 'Export Complete', 'Vehicles registry exported successfully.');
        } catch (err) {
            console.error('CSV Export error:', err);
            showToast('error', 'Export Error', 'Failed to generate CSV export.');
        }
    }

    /**
     * Modal dialog to display single vehicle detailed inspection
     */
    window.viewVehicleModal = function (vehicle) {
        const modalBackdrop = document.createElement('div');
        modalBackdrop.className = 'modal-backdrop';

        modalBackdrop.innerHTML = `
            <div class="modal-card">
                <div class="modal-header">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <span class="plate-badge" style="font-size:1.1rem;">${vehicle.plate_number}</span>
                        <span class="modal-title">Vehicle Record #${vehicle.id}</span>
                    </div>
                    <button class="modal-close-btn" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
                        <div>
                            <div style="font-size:0.75rem; color:#94a3b8;">OWNER FULL NAME</div>
                            <div style="font-weight:600; font-size:1rem; color:#f8fafc;">${vehicle.owner_name}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:#94a3b8;">PHONE NUMBER</div>
                            <div style="font-weight:600; font-size:1rem; color:#f8fafc;">${vehicle.phone}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:#94a3b8;">VEHICLE SPECIFICATION</div>
                            <div style="font-weight:600; font-size:1rem; color:#f8fafc;">${vehicle.make} ${vehicle.model}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:#94a3b8;">BODY TYPE & COLOR</div>
                            <div style="font-weight:600; font-size:1rem; color:#f8fafc;">${vehicle.body_type} (${vehicle.color})</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:#94a3b8;">REGISTRATION FEE PAID</div>
                            <div style="font-weight:700; font-size:1.15rem; color:#10b981;">${vehicle.fee_formatted}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:#94a3b8;">DATE REGISTERED</div>
                            <div style="font-weight:500; font-size:0.875rem; color:#f8fafc;">${vehicle.created_human}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-secondary modal-delete-action" style="color:var(--accent-rose); border-color:rgba(244,63,94,0.3); display:inline-flex; align-items:center; gap:0.4rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span>${window.t ? window.t('registry.btn_delete') : 'Delete Vehicle'}</span>
                    </button>
                    <button type="button" class="btn btn-secondary modal-close-action">Close</button>
                </div>
            </div>
        `;

        const closeModal = () => modalBackdrop.remove();
        modalBackdrop.querySelector('.modal-close-btn').addEventListener('click', closeModal);
        modalBackdrop.querySelector('.modal-close-action').addEventListener('click', closeModal);
        modalBackdrop.querySelector('.modal-delete-action').addEventListener('click', () => {
            closeModal();
            window.confirmDeleteVehicle(vehicle.id, vehicle.plate_number);
        });
        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) closeModal();
        });

        document.body.appendChild(modalBackdrop);
    };

    /**
     * Delete vehicle confirmation & AJAX execution
     */
    window.confirmDeleteVehicle = function (id, plate) {
        const confirmMsg = window.t 
            ? window.t('registry.delete_confirm', `Are you sure you want to permanently delete vehicle [${plate}] from the registry? This action cannot be undone.`).replace('{plate}', plate)
            : `Are you sure you want to permanently delete vehicle [${plate}] from the registry? This action cannot be undone.`;

        if (!confirm(confirmMsg)) {
            return;
        }

        executeDeleteVehicle(id, plate);
    };

    async function executeDeleteVehicle(id, plate) {
        try {
            const response = await fetch(`api/vehicles.php?id=${encodeURIComponent(id)}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': state.csrfToken
                },
                body: JSON.stringify({ id: id })
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                const successMsg = window.t 
                    ? window.t('registry.delete_success', `Vehicle record [${plate}] was successfully deleted.`).replace('{plate}', plate)
                    : `Vehicle record [${plate}] was successfully deleted.`;
                showToast('success', 'Vehicle Deleted', successMsg);
                loadVehiclesDirectory(currentRegistryPage);
            } else {
                showToast('error', 'Delete Failed', result.message || 'Could not delete vehicle record.');
            }
        } catch (err) {
            console.error('Delete error:', err);
            showToast('error', 'Server Error', 'Failed to reach server to delete record.');
        }
    }

    window.changeRegistryPage = function (page) {
        loadVehiclesDirectory(page);
    };

    /**
     * =====================================================================
     * Initializer
     * =====================================================================
     */
    document.addEventListener('DOMContentLoaded', () => {
        // Setup Scanner Controls
        setupScannerTabs();
        setupUploadDropzone();

        if (dom.btnStartCamera) dom.btnStartCamera.addEventListener('click', startCamera);
        if (dom.btnStopCamera) dom.btnStopCamera.addEventListener('click', stopCamera);
        if (dom.btnSwitchCamera) dom.btnSwitchCamera.addEventListener('click', switchCamera);
        if (dom.btnCapture) dom.btnCapture.addEventListener('click', captureFrame);

        // Setup Form
        setupRegistrationForm();

        // Setup Registry Directory Filters
        setupRegistryFilters();
        if (dom.registryTableBody) {
            loadVehiclesDirectory(1);
        }

        // Preload Currency Exchange Rates
        loadExchangeRates('USD');

        // Automatically start camera if on index page and in camera tab
        if (dom.cameraVideo && state.activeTab === 'camera') {
            startCamera();
        }
    });

    // Cleanup camera stream when navigating away
    window.addEventListener('beforeunload', () => {
        stopCamera();
    });

})();
