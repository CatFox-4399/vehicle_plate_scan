<?php
/**
 * Vehicle License Plate Scanner & Registration System
 * Page: vehicles.php - Registered Vehicles Directory, Search & Analytics
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

render_header('Registered Vehicles Directory', 'vehicles', 'Search, filter, and inspect registered vehicles with dynamic currency fee logs.');
?>

<div class="container">
    <!-- Page Header -->
    <header class="page-header">
        <div class="page-badge">
            <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
            <span data-i18n="nav.vehicles_directory">Registry Directory</span>
        </div>
        <h1 class="page-title">
            <span class="page-title-gradient" data-i18n="registry.title">Registered Vehicles Directory</span>
        </h1>
        <p class="page-subtitle" data-i18n="registry.subtitle">
            Search, filter, and review registered vehicle records and payment logs.
        </p>
    </header>

    <!-- Metrics / Statistics Cards -->
    <div class="quick-stats-grid">
        <div class="stat-card">
            <div class="stat-icon cyan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="3"></rect>
                    <line x1="6" y1="12" x2="18" y2="12"></line>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="statTotalRegistered">0</span>
                <span class="stat-label" data-i18n="registry.stat_total">Total Vehicles</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 14 14"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="statTodayRegistered">0</span>
                <span class="stat-label" data-i18n="registry.stat_today">Registered Today</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="stat-info" style="flex: 1; min-width: 0;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem;">
                    <span class="stat-value" id="statTotalFees">$0.00</span>
                    <select id="statRevenueCurrency" class="stat-currency-select" title="Change Total Revenue Currency">
                        <?php foreach (SUPPORTED_CURRENCIES as $code => $c): ?>
                            <option value="<?php echo escape_html($code); ?>" <?php echo $code === 'USD' ? 'selected' : ''; ?>>
                                <?php echo escape_html($code); ?> (<?php echo escape_html($c['symbol']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="stat-label">
                    <span data-i18n="registry.stat_fees">Total Revenue</span> (<span id="currentRevenueCurr">USD</span>)
                </span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M2 12h20"></path>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value">1h Cache</span>
                <span class="stat-label" data-i18n="registry.stat_rates">Currency Engine</span>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="filter-card">
        <div class="filter-grid">
            <!-- Search Query -->
            <div class="form-group">
                <label for="searchQuery" class="form-label" data-i18n="registry.search_placeholder">Search</label>
                <div class="input-wrap">
                    <input 
                        type="text" 
                        id="searchQuery" 
                        class="form-input" 
                        placeholder="Search plate, owner, make, model..." 
                        data-i18n-placeholder="registry.search_placeholder"
                    >
                </div>
            </div>

            <!-- Body Type Filter -->
            <div class="form-group">
                <label for="filterBodyType" class="form-label" data-i18n="form.body_type">Body Type</label>
                <select id="filterBodyType" class="form-select">
                    <option value="" selected data-i18n="registry.all_types">All Body Types</option>
                    <?php foreach (ALLOWED_BODY_TYPES as $type): ?>
                        <option value="<?php echo escape_html($type); ?>"><?php echo escape_html($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date From -->
            <div class="form-group">
                <label for="filterDateFrom" class="form-label" data-i18n="registry.filter_date_from">From Date</label>
                <input type="date" id="filterDateFrom" class="form-input">
            </div>

            <!-- Date To -->
            <div class="form-group">
                <label for="filterDateTo" class="form-label" data-i18n="registry.filter_date_to">To Date</label>
                <input type="date" id="filterDateTo" class="form-input">
            </div>

            <!-- Actions: Reset & Export CSV -->
            <div style="display:flex; gap:0.5rem;">
                <button type="button" class="btn btn-secondary" id="btnResetFilters" title="Reset Filters">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    <span data-i18n="registry.btn_reset">Reset</span>
                </button>
                <button type="button" class="btn btn-primary" id="btnExportCsv" title="Export as CSV">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span data-i18n="registry.btn_export">Export CSV</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Registry Data Table Card -->
    <div class="card">
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table class="registry-table" aria-label="Vehicles registry table">
                    <thead>
                        <tr>
                            <th data-i18n="registry.th_plate">Plate Number</th>
                            <th data-i18n="registry.th_owner">Owner Details</th>
                            <th data-i18n="registry.th_vehicle">Vehicle Info</th>
                            <th data-i18n="registry.th_fee">Fee Paid</th>
                            <th data-i18n="registry.th_date">Registered At</th>
                            <th data-i18n="registry.th_actions">Action</th>
                        </tr>
                    </thead>
                    <tbody id="registryTableBody">
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 2.5rem; color: #94a3b8;">
                                <div class="spinner" style="margin: 0 auto 0.75rem;"></div>
                                <span>Loading vehicle records...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Pagination Footer -->
            <div class="pagination-wrap" id="paginationWrap" style="padding: 1.25rem 1.5rem;">
                <span class="pagination-info" id="paginationInfo">Showing 0 of 0 records</span>
                <div class="pagination-buttons" id="paginationButtons"></div>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>
