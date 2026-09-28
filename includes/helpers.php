<?php
/**
 * Helper Functions and Utility Engine
 * Vehicle License Plate Scanner & Registration System
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Standardized JSON response helper
 *
 * @param array $payload
 * @param int $statusCode
 * @return void
 */
function json_response(array $payload, int $statusCode = 200): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Standardized JSON error response helper
 *
 * @param string $message
 * @param string $code
 * @param int $statusCode
 * @param array $extra
 * @return void
 */
function json_error(string $message, string $code = 'ERROR', int $statusCode = 400, array $extra = []): void {
    $response = array_merge([
        'status'  => 'error',
        'code'    => $code,
        'message' => $message
    ], $extra);
    json_response($response, $statusCode);
}

/**
 * Generate or retrieve CSRF token
 *
 * @return string
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate submitted CSRF token
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * HTML escape helper
 *
 * @param mixed $val
 * @return string
 */
function escape_html(mixed $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize generic string input
 *
 * @param mixed $input
 * @return string
 */
function sanitize_input(mixed $input): string {
    if ($input === null) {
        return '';
    }
    $str = is_string($input) ? $input : (string)$input;
    // Strip null bytes and trim whitespace
    return trim(str_replace("\0", '', $str));
}

/**
 * Safely determine client IP address
 *
 * @return string
 */
function get_client_ip(): string {
    $headers = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ipList = explode(',', $_SERVER[$header]);
            $ip = trim($ipList[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '127.0.0.1';
}

/**
 * Enforce rate limiting per endpoint and IP (max 30 requests/minute by default)
 *
 * @param string $endpoint
 * @param int $maxRequests
 * @param int $windowSeconds
 * @return void (terminates on breach)
 */
function check_rate_limit(string $endpoint, int $maxRequests = RATE_LIMIT_MAX, int $windowSeconds = RATE_LIMIT_WINDOW): void {
    $ip = get_client_ip();
    $now = time();

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT `request_count`, `last_request` FROM `rate_limits` WHERE `ip_address` = :ip AND `endpoint` = :ep");
        $stmt->execute([':ip' => $ip, ':ep' => $endpoint]);
        $row = $stmt->fetch();

        if ($row) {
            $count = (int)$row['request_count'];
            $lastReq = (int)$row['last_request'];

            if ($now - $lastReq < $windowSeconds) {
                if ($count >= $maxRequests) {
                    $retryAfter = $windowSeconds - ($now - $lastReq);
                    header("Retry-After: {$retryAfter}");
                    json_error(
                        "Too many requests. Please wait {$retryAfter} seconds before trying again.",
                        'RATE_LIMIT_EXCEEDED',
                        429,
                        ['retry_after' => $retryAfter]
                    );
                }
                // Increment count
                $update = $pdo->prepare("UPDATE `rate_limits` SET `request_count` = `request_count` + 1 WHERE `ip_address` = :ip AND `endpoint` = :ep");
                $update->execute([':ip' => $ip, ':ep' => $endpoint]);
            } else {
                // Window elapsed: reset count
                $update = $pdo->prepare("UPDATE `rate_limits` SET `request_count` = 1, `last_request` = :now WHERE `ip_address` = :ip AND `endpoint` = :ep");
                $update->execute([':now' => $now, ':ip' => $ip, ':ep' => $endpoint]);
            }
        } else {
            // First request for this endpoint
            $insert = $pdo->prepare("INSERT INTO `rate_limits` (`ip_address`, `endpoint`, `request_count`, `last_request`) VALUES (:ip, :ep, 1, :now)");
            $insert->execute([':ip' => $ip, ':ep' => $endpoint, ':now' => $now]);
        }
    } catch (Exception $e) {
        // Fallback: If DB query fails, log and permit request to maintain availability
        error_log("Rate limiting DB check error: " . $e->getMessage());
    }
}

/**
 * Validate and clean license plate number
 * Allows letters, numbers, spaces, and hyphens (length 2-15 characters)
 *
 * @param string $plate
 * @return string|null
 */
function validate_plate_number(string $plate): ?string {
    // Allows Latin letters, numbers, and international characters (e.g. Chinese province prefixes like 粤/京)
    $cleaned = mb_strtoupper(preg_replace('/[^\p{L}\p{N}]/u', '', $plate));
    $len = mb_strlen($cleaned);
    if ($len >= 2 && $len <= 15) {
        return $cleaned;
    }
    return null;
}

/**
 * Validate phone number format
 * Accepts international and local numbers: +1234567890, 012-3456789, etc.
 *
 * @param string $phone
 * @return bool
 */
function validate_phone_number(string $phone): bool {
    return (bool)preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', trim($phone));
}

/**
 * Fetch and cache foreign exchange rates (1-hour cache in MySQL)
 * Supports primary API (open.er-api.com) and fallback (api.frankfurter.dev)
 *
 * @param string $baseCurrency
 * @return array
 */
function get_exchange_rates(string $baseCurrency = 'USD'): array {
    $baseCurrency = strtoupper(trim($baseCurrency));
    if (!array_key_exists($baseCurrency, SUPPORTED_CURRENCIES)) {
        $baseCurrency = DEFAULT_BASE_CURRENCY;
    }

    $pdo = getDB();
    $now = time();

    // 1. Check MySQL rate_cache for fresh cache (within 1 hour)
    try {
        $stmt = $pdo->prepare("SELECT `rates_json`, `fetched_at` FROM `rate_cache` WHERE `base_currency` = :base ORDER BY `fetched_at` DESC LIMIT 1");
        $stmt->execute([':base' => $baseCurrency]);
        $cached = $stmt->fetch();

        if ($cached && ($now - (int)$cached['fetched_at'] < RATE_CACHE_TTL)) {
            $rates = json_decode($cached['rates_json'], true);
            if (is_array($rates) && !empty($rates)) {
                return [
                    'base'         => $baseCurrency,
                    'rates'        => $rates,
                    'cached'       => true,
                    'stale'        => false,
                    'source'       => 'local_cache',
                    'last_updated' => (int)$cached['fetched_at'],
                    'expires_in'   => RATE_CACHE_TTL - ($now - (int)$cached['fetched_at'])
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error reading rate_cache table: " . $e->getMessage());
    }

    // 2. Fetch from Primary API: open.er-api.com
    $primaryUrl = EXCHANGE_API_PRIMARY . urlencode($baseCurrency);
    $ratesData = fetch_curl_json($primaryUrl, 5);

    if ($ratesData && !empty($ratesData['rates']) && is_array($ratesData['rates'])) {
        $rates = $ratesData['rates'];
        save_rates_to_cache($pdo, $baseCurrency, $rates, $now);
        return [
            'base'         => $baseCurrency,
            'rates'        => $rates,
            'cached'       => false,
            'stale'        => false,
            'source'       => 'open.er-api.com',
            'last_updated' => $now,
            'expires_in'   => RATE_CACHE_TTL
        ];
    }

    // 3. Fallback API: api.frankfurter.dev
    $fallbackUrl = EXCHANGE_API_FALLBACK . urlencode($baseCurrency);
    $fallbackData = fetch_curl_json($fallbackUrl, 5);

    if ($fallbackData && !empty($fallbackData['rates']) && is_array($fallbackData['rates'])) {
        $rates = $fallbackData['rates'];
        $rates[$baseCurrency] = 1.0; // Ensure base rate is 1.0
        save_rates_to_cache($pdo, $baseCurrency, $rates, $now);
        return [
            'base'         => $baseCurrency,
            'rates'        => $rates,
            'cached'       => false,
            'stale'        => false,
            'source'       => 'api.frankfurter.dev',
            'last_updated' => $now,
            'expires_in'   => RATE_CACHE_TTL
        ];
    }

    // 4. Both APIs failed: Try to use any stale cache in database
    if (!empty($cached['rates_json'])) {
        $staleRates = json_decode($cached['rates_json'], true);
        if (is_array($staleRates)) {
            return [
                'base'         => $baseCurrency,
                'rates'        => $staleRates,
                'cached'       => true,
                'stale'        => true,
                'source'       => 'stale_cache_fallback',
                'last_updated' => (int)$cached['fetched_at'],
                'expires_in'   => 0
            ];
        }
    }

    // 5. Ultimate Emergency Fallback (hardcoded standard baselines for USD)
    $emergencyRates = [
        'USD' => 1.0,
        'EUR' => 0.88,
        'GBP' => 0.76,
        'MYR' => 4.08,
        'CNY' => 6.72,
        'SGD' => 1.28,
        'JPY' => 157.5,
        'AUD' => 1.43,
        'CAD' => 1.41,
        'THB' => 33.4,
        'IDR' => 17920.0
    ];

    return [
        'base'         => $baseCurrency,
        'rates'        => $emergencyRates,
        'cached'       => true,
        'stale'        => true,
        'source'       => 'emergency_baseline',
        'last_updated' => $now,
        'expires_in'   => 0
    ];
}

/**
 * Save rates to rate_cache table
 *
 * @param PDO $pdo
 * @param string $baseCurrency
 * @param array $rates
 * @param int $timestamp
 * @return void
 */
function save_rates_to_cache(PDO $pdo, string $baseCurrency, array $rates, int $timestamp): void {
    try {
        $json = json_encode($rates);
        // Clean old records for this base currency to keep table compact
        $del = $pdo->prepare("DELETE FROM `rate_cache` WHERE `base_currency` = :base");
        $del->execute([':base' => $baseCurrency]);

        $ins = $pdo->prepare("INSERT INTO `rate_cache` (`base_currency`, `rates_json`, `fetched_at`) VALUES (:base, :json, :ts)");
        $ins->execute([
            ':base' => $baseCurrency,
            ':json' => $json,
            ':ts'   => $timestamp
        ]);
    } catch (Exception $e) {
        error_log("Failed to cache exchange rates: " . $e->getMessage());
    }
}

/**
 * Secure cURL GET helper with timeout
 *
 * @param string $url
 * @param int $timeout
 * @return array|null
 */
function fetch_curl_json(string $url, int $timeout = 5): ?array {
    // 1. Try cURL first
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AutoScan-AI/1.0');
        // If curl.cainfo or openssl.cafile is not configured, don't break on local dev SSL handshake
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if (!$curlError && $httpCode === 200 && $response) {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } else if ($curlError) {
            error_log("fetch_curl_json cURL error for {$url}: {$curlError}");
        }
    }

    // 2. Stream context fallback
    try {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header'  => "User-Agent: AutoScan-AI/1.0\r\nAccept: application/json\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $response = @file_get_contents($url, false, $ctx);
        if ($response !== false) {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    } catch (Exception $e) {
        error_log("fetch_curl_json stream error for {$url}: " . $e->getMessage());
    }

    return null;
}

/**
 * Heuristic Plate Extractor from OCR Raw Text
 * Identifies license plate patterns and calculates confidence score
 *
 * @param string $rawText
 * @param array $overlayLines
 * @return array
 */
function extract_plate_from_ocr(string $rawText, array $overlayLines = []): array {
    // 1. Split lines and words
    $lines = preg_split('/[\r\n]+/', $rawText);
    $candidates = [];

    // Comprehensive International License Plate Regex Patterns
    $platePatterns = [
        // 1. China (e.g., 粤B12345, 京A88888, 沪A12345)
        '/^([\x{4e00}-\x{9fa5}][A-Z][A-Z0-9]{4,6})$/u'                 => 0.99,

        // 2. Malaysia / Standard Asian (e.g., ABC 1234, W 1234 A, KV 8888 B)
        '/^([A-Z]{1,3})\s*([0-9]{1,4})\s*([A-Z]{0,2})$/'               => 0.98,

        // 3. Singapore (e.g., SBA 1234 A, SKL 8888 Z, SJA 123 B)
        '/^([A-Z]{2,3})\s*([0-9]{1,4})\s*([A-Z]{1})$/'                 => 0.98,

        // 4. Indonesia (e.g., B 1234 ABC, D 5678 EF, AB 999 XY)
        '/^([A-Z]{1,2})\s*([0-9]{1,4})\s*([A-Z]{1,3})$/'               => 0.98,

        // 5. United Kingdom (e.g., BD51 SMR, AB12 CDE, LO69 XYZ)
        '/^([A-Z]{2})\s*([0-9]{2})\s*([A-Z]{3})$/'                     => 0.98,

        // 6. France / Italy / Spain (e.g., AB-123-CD, AA 999 BB)
        '/^([A-Z]{2})\s*([0-9]{3})\s*([A-Z]{2})$/'                     => 0.98,

        // 7. Germany (e.g., B MW 1234, M AB 567, HH XY 888)
        '/^([A-Z]{1,3})\s*([A-Z]{1,2})\s*([0-9]{1,4})$/'               => 0.97,

        // 8. United States - California style (e.g., 7SAM123, 2XYZ999, 1ABC234)
        '/^([0-9]{1})\s*([A-Z]{3})\s*([0-9]{3})$/'                     => 0.98,

        // 9. United States - Standard 7-char (e.g., ABC-1234, GHR 5678)
        '/^([A-Z]{3})\s*([0-9]{4})$/'                                   => 0.98,

        // 10. United States / Canada / Australia 6-char (e.g., ABC-123, 123-ABC)
        '/^([A-Z]{3})\s*([0-9]{3})$/'                                   => 0.97,
        '/^([0-9]{3})\s*([A-Z]{3})$/'                                   => 0.97,

        // 11. Australia Victoria / NSW style (e.g., 1AB 2CD, ABC 12D)
        '/^([0-9]{1})\s*([A-Z]{2})\s*([0-9]{1})\s*([A-Z]{2})$/'         => 0.96,

        // 12. Netherlands / EU dash formats (e.g. 12-AB-34, 12-ABC-3, AB-123-C)
        '/^([0-9]{1,3})\s*([A-Z]{1,3})\s*([0-9]{1,3})$/'               => 0.95,
        '/^([A-Z]{1,3})\s*([0-9]{1,3})\s*([A-Z]{1,3})$/'               => 0.95,

        // 13. General International alphanumeric (4 to 9 alphanumeric chars containing both digits and letters)
        '/^(?=.*[A-Z])(?=.*[0-9])[A-Z0-9]{4,9}$/'                       => 0.90
    ];

    // Check all lines and tokens
    foreach ($lines as $line) {
        // Strip noise while preserving Unicode letters, digits, and spaces
        $cleanLine = mb_strtoupper(trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $line)));
        if (empty($cleanLine)) continue;

        // Check full line (both compact and spaced)
        $compactLine = preg_replace('/\s+/u', '', $cleanLine);

        foreach ($platePatterns as $pattern => $baseConf) {
            if (preg_match($pattern, $compactLine) || preg_match($pattern, $cleanLine)) {
                $candidates[] = [
                    'plate'      => $compactLine,
                    'confidence' => $baseConf,
                    'raw'        => $cleanLine
                ];
                break;
            }
        }

        // Also check individual words / space-separated tokens
        $tokens = preg_split('/\s+/u', $cleanLine);
        foreach ($tokens as $token) {
            $tokenClean = preg_replace('/[^\p{L}\p{N}]/u', '', $token);
            $tLen = mb_strlen($tokenClean);
            if ($tLen >= 4 && $tLen <= 8 && preg_match('/[A-Z\p{L}]/u', $tokenClean) && preg_match('/[0-9]/', $tokenClean)) {
                $candidates[] = [
                    'plate'      => $tokenClean,
                    'confidence' => 0.92,
                    'raw'        => $token
                ];
            }
        }
    }

    // If matches found, sort by confidence and length suitability
    if (!empty($candidates)) {
        usort($candidates, function($a, $b) {
            if ($a['confidence'] == $b['confidence']) {
                return mb_strlen($b['plate']) <=> mb_strlen($a['plate']);
            }
            return ($b['confidence'] < $a['confidence']) ? -1 : 1;
        });
        return $candidates[0];
    }

    // Fallback: If raw text has any alphanumeric sequence that looks plausible (3-10 chars)
    $rawAlpha = mb_strtoupper(trim(preg_replace('/[^\p{L}\p{N}]/u', '', $rawText)));
    if (mb_strlen($rawAlpha) >= 3 && mb_strlen($rawAlpha) <= 10 && preg_match('/[\p{L}\p{N}]/u', $rawAlpha)) {
        return [
            'plate'      => $rawAlpha,
            'confidence' => 0.80,
            'raw'        => $rawText
        ];
    }

    return [
        'plate'      => '',
        'confidence' => 0.0,
        'raw'        => $rawText
    ];
}
