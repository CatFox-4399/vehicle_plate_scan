<?php
/**
 * API Endpoint: /api/register.php
 * Validates, sanitizes, and records vehicle registration data
 */

require_once __DIR__ . '/../includes/helpers.php';

// Enforce rate limiting
check_rate_limit('register', RATE_LIMIT_MAX, RATE_LIMIT_WINDOW);

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed. Use POST.', 'METHOD_NOT_ALLOWED', 405);
}

// Read input (support JSON or standard form-data / x-www-form-urlencoded)
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

// 1. Validate CSRF Token
$token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
if (!verify_csrf_token($token)) {
    json_error('Security validation failed: Invalid or expired CSRF token. Please refresh the page and try again.', 'CSRF_INVALID', 403);
}

// 2. Sanitize and validate fields
$plate = sanitize_input($input['plate_number'] ?? '');
$cleanPlate = validate_plate_number($plate);
if (!$cleanPlate) {
    json_error('Invalid license plate number. It must be 2 to 15 alphanumeric characters.', 'INVALID_PLATE', 422);
}

$ownerName = sanitize_input($input['owner_name'] ?? '');
if (mb_strlen($ownerName) < 2 || mb_strlen($ownerName) > 100) {
    json_error('Owner full name must be between 2 and 100 characters.', 'INVALID_OWNER_NAME', 422);
}

$phone = sanitize_input($input['phone'] ?? '');
if (!validate_phone_number($phone)) {
    json_error('Invalid phone number format. Please provide a valid phone number (e.g., +60123456789 or 012-3456789).', 'INVALID_PHONE', 422);
}

$make = sanitize_input($input['make'] ?? '');
if (mb_strlen($make) < 1 || mb_strlen($make) > 50) {
    json_error('Vehicle make/brand is required (maximum 50 characters).', 'INVALID_MAKE', 422);
}

$model = sanitize_input($input['model'] ?? '');
if (mb_strlen($model) < 1 || mb_strlen($model) > 50) {
    json_error('Vehicle model is required (maximum 50 characters).', 'INVALID_MODEL', 422);
}

$color = sanitize_input($input['color'] ?? '');
if (mb_strlen($color) < 1 || mb_strlen($color) > 30) {
    json_error('Vehicle color is required (maximum 30 characters).', 'INVALID_COLOR', 422);
}

$bodyType = sanitize_input($input['body_type'] ?? '');
if (!in_array($bodyType, ALLOWED_BODY_TYPES, true)) {
    json_error('Invalid body type selected. Allowed types: ' . implode(', ', ALLOWED_BODY_TYPES), 'INVALID_BODY_TYPE', 422);
}

$currency = strtoupper(sanitize_input($input['currency'] ?? DEFAULT_BASE_CURRENCY));
if (!array_key_exists($currency, SUPPORTED_CURRENCIES)) {
    $currency = DEFAULT_BASE_CURRENCY;
}

// 3. Compute dynamic registration fee based on live/cached exchange rate
$baseFee = DEFAULT_BASE_FEE;
$ratesData = get_exchange_rates(DEFAULT_BASE_CURRENCY);
$rate = isset($ratesData['rates'][$currency]) ? (float)$ratesData['rates'][$currency] : 1.0;
$feeConverted = round($baseFee * $rate, 2);

// 4. Save into Database with Prepared Statement
$pdo = getDB();

// Check for duplicate license plate
$checkStmt = $pdo->prepare("SELECT `id` FROM `vehicles` WHERE `plate_number` = :plate LIMIT 1");
$checkStmt->execute([':plate' => $cleanPlate]);
if ($checkStmt->fetch()) {
    json_error(
        "A vehicle with license plate '{$cleanPlate}' is already registered in the system.",
        'DUPLICATE_PLATE',
        409
    );
}

try {
    $insertStmt = $pdo->prepare("
        INSERT INTO `vehicles` 
        (`plate_number`, `owner_name`, `phone`, `make`, `model`, `color`, `body_type`, `base_fee`, `currency`, `fee_converted`, `created_at`)
        VALUES
        (:plate, :owner, :phone, :make, :model, :color, :body_type, :base_fee, :currency, :fee_converted, NOW())
    ");

    $insertStmt->execute([
        ':plate'         => $cleanPlate,
        ':owner'         => $ownerName,
        ':phone'         => $phone,
        ':make'          => $make,
        ':model'         => $model,
        ':color'         => $color,
        ':body_type'     => $bodyType,
        ':base_fee'      => $baseFee,
        ':currency'      => $currency,
        ':fee_converted' => $feeConverted
    ]);

    $vehicleId = (int)$pdo->lastInsertId();

    // Regenerate CSRF token after successful state-changing action to prevent replay
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    json_response([
        'status'  => 'success',
        'message' => 'Vehicle successfully registered in the system.',
        'data'    => [
            'id'             => $vehicleId,
            'plate_number'   => $cleanPlate,
            'owner_name'     => $ownerName,
            'phone'          => $phone,
            'make'           => $make,
            'model'          => $model,
            'color'          => $color,
            'body_type'      => $bodyType,
            'base_fee'       => number_format($baseFee, 2),
            'currency'       => $currency,
            'fee_converted'  => number_format($feeConverted, 2),
            'currency_symbol'=> SUPPORTED_CURRENCIES[$currency]['symbol'],
            'created_at'     => date('Y-m-d H:i:s'),
            'new_csrf_token' => $_SESSION['csrf_token']
        ]
    ], 201);

} catch (PDOException $e) {
    error_log("Vehicle registration DB error: " . $e->getMessage());
    if ($e->getCode() == 23000) {
        json_error("A vehicle with license plate '{$cleanPlate}' is already registered in the system.", 'DUPLICATE_PLATE', 409);
    }
    json_error('Failed to register vehicle due to a server error. Please try again.', 'SERVER_ERROR', 500);
}
