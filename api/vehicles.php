<?php
/**
 * API Endpoint: /api/vehicles.php
 * Handles AJAX search, filtering, and pagination for registered vehicles
 */

require_once __DIR__ . '/../includes/helpers.php';

// Enforce rate limiting
check_rate_limit('vehicles', RATE_LIMIT_MAX, RATE_LIMIT_WINDOW);

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed. Use GET.', 'METHOD_NOT_ALLOWED', 405);
}

$pdo = getDB();

// 1. Parse and sanitize search & filter parameters
$search    = sanitize_input($_GET['q'] ?? '');
$bodyType  = sanitize_input($_GET['body_type'] ?? '');
$dateFrom  = sanitize_input($_GET['date_from'] ?? '');
$dateTo    = sanitize_input($_GET['date_to'] ?? '');

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(5, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

$allowedSortFields = ['created_at', 'plate_number', 'owner_name', 'make', 'fee_converted'];
$sortBy = in_array($_GET['sort_by'] ?? '', $allowedSortFields, true) ? $_GET['sort_by'] : 'created_at';
$sortOrder = (strtolower($_GET['sort_order'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

// 2. Build parameterized dynamic query
$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(
        `plate_number` LIKE :q 
        OR `owner_name` LIKE :q 
        OR `make` LIKE :q 
        OR `model` LIKE :q 
        OR `phone` LIKE :q
    )";
    $params[':q'] = '%' . $search . '%';
}

if ($bodyType !== '' && in_array($bodyType, ALLOWED_BODY_TYPES, true)) {
    $whereClauses[] = "`body_type` = :body_type";
    $params[':body_type'] = $bodyType;
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $whereClauses[] = "`created_at` >= :date_from";
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}

if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $whereClauses[] = "`created_at` <= :date_to";
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

$whereSql = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

// 3. Count total matching rows
$countSql = "SELECT COUNT(*) FROM `vehicles` {$whereSql}";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

// 4. Fetch page records
$dataSql = "
    SELECT 
        `id`, `plate_number`, `owner_name`, `phone`, 
        `make`, `model`, `color`, `body_type`, 
        `base_fee`, `currency`, `fee_converted`, `created_at`
    FROM `vehicles`
    {$whereSql}
    ORDER BY `{$sortBy}` {$sortOrder}
    LIMIT :limit OFFSET :offset
";

$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $key => $val) {
    $dataStmt->bindValue($key, $val);
}
$dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$rawVehicles = $dataStmt->fetchAll();

// Format results safely
$vehicles = [];
foreach ($rawVehicles as $row) {
    $curr = $row['currency'];
    $symbol = SUPPORTED_CURRENCIES[$curr]['symbol'] ?? '$';
    
    $vehicles[] = [
        'id'             => (int)$row['id'],
        'plate_number'   => escape_html($row['plate_number']),
        'owner_name'     => escape_html($row['owner_name']),
        'phone'          => escape_html($row['phone']),
        'make'           => escape_html($row['make']),
        'model'          => escape_html($row['model']),
        'color'          => escape_html($row['color']),
        'body_type'      => escape_html($row['body_type']),
        'base_fee'       => number_format((float)$row['base_fee'], 2),
        'currency'       => escape_html($curr),
        'currency_symbol'=> $symbol,
        'fee_converted'  => number_format((float)$row['fee_converted'], 2),
        'fee_formatted'  => $symbol . ' ' . number_format((float)$row['fee_converted'], 2),
        'created_at'     => $row['created_at'],
        'created_human'  => date('M d, Y h:i A', strtotime($row['created_at']))
    ];
}

// 5. Gather summary metrics for dashboard cards
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) AS total_count,
        SUM(CASE WHEN DATE(`created_at`) = CURDATE() THEN 1 ELSE 0 END) AS today_count,
        SUM(`base_fee`) AS total_base_revenue
    FROM `vehicles`
");
$statsRow = $statsStmt->fetch();

json_response([
    'status' => 'success',
    'data'   => [
        'vehicles'   => $vehicles,
        'pagination' => [
            'total'       => $totalRecords,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => $totalPages,
            'has_prev'    => $page > 1,
            'has_next'    => $page < $totalPages
        ],
        'stats' => [
            'total_registered' => (int)($statsRow['total_count'] ?? 0),
            'today_registered' => (int)($statsRow['today_count'] ?? 0),
            'total_fees_usd'   => number_format((float)($statsRow['total_base_revenue'] ?? 0), 2)
        ]
    ]
]);
