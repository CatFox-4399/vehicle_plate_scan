<?php
/**
 * API Endpoint: /api/rates.php
 * Serves cached and live exchange rates for vehicle registration fees
 */

require_once __DIR__ . '/../includes/helpers.php';

// Enforce rate limiting
check_rate_limit('rates', RATE_LIMIT_MAX, RATE_LIMIT_WINDOW);

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed. Use GET.', 'METHOD_NOT_ALLOWED', 405);
}

// Determine requested base currency
$base = strtoupper(sanitize_input($_GET['base'] ?? DEFAULT_BASE_CURRENCY));
if (!array_key_exists($base, SUPPORTED_CURRENCIES)) {
    $base = DEFAULT_BASE_CURRENCY;
}

// Fetch rates (from 1-hour MySQL cache or live APIs)
$ratesResult = get_exchange_rates($base);
$rates = $ratesResult['rates'] ?? [];

// Calculate registration fees across supported currencies
$fees = [];
$baseFee = DEFAULT_BASE_FEE;

foreach (SUPPORTED_CURRENCIES as $code => $meta) {
    $rate = isset($rates[$code]) ? (float)$rates[$code] : 1.0;
    // Fee calculated dynamically from base fee in base currency
    $convertedFee = round($baseFee * $rate, 2);

    $fees[$code] = [
        'code'      => $code,
        'name'      => $meta['name'],
        'symbol'    => $meta['symbol'],
        'rate'      => $rate,
        'fee'       => $convertedFee,
        'formatted' => $meta['symbol'] . ' ' . number_format($convertedFee, 2)
    ];
}

json_response([
    'status' => 'success',
    'data'   => [
        'base_currency' => $ratesResult['base'],
        'base_fee'      => $baseFee,
        'supported'     => SUPPORTED_CURRENCIES,
        'fees'          => $fees,
        'rates'         => $rates,
        'cached'        => (bool)$ratesResult['cached'],
        'stale'         => (bool)$ratesResult['stale'],
        'source'        => $ratesResult['source'],
        'last_updated'  => $ratesResult['last_updated'],
        'expires_in'    => $ratesResult['expires_in']
    ]
]);
