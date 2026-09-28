<?php
/**
 * API Endpoint: /api/scan.php
 * Receives camera frame snapshot or uploaded image and returns OCR license plate text
 */

require_once __DIR__ . '/../includes/helpers.php';

// Enforce rate limiting
check_rate_limit('scan', RATE_LIMIT_MAX, RATE_LIMIT_WINDOW);

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed. Use POST.', 'METHOD_NOT_ALLOWED', 405);
}

// 1. Extract image payload (File upload or Base64 data URL)
$imageData = null;
$mimeType = null;
$imageWidth = 0;
$imageHeight = 0;

if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_error('Image upload failed. Error code: ' . $file['error'], 'UPLOAD_ERROR', 400);
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        json_error('Image file exceeds maximum 8MB size limit.', 'FILE_TOO_LARGE', 400);
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        json_error('Invalid image file format.', 'INVALID_IMAGE', 400);
    }

    $mimeType = $imageInfo['mime'];
    $imageWidth = $imageInfo[0];
    $imageHeight = $imageInfo[1];
    $rawBinary = file_get_contents($file['tmp_name']);
    $imageData = 'data:' . $mimeType . ';base64,' . base64_encode($rawBinary);

} else {
    // Check POST field or JSON payload
    $rawInput = file_get_contents('php://input');
    $json = json_decode($rawInput, true);

    $base64Input = $_POST['image'] ?? ($json['image'] ?? null);

    if (empty($base64Input)) {
        json_error('No image data provided. Please capture a frame or upload an image file.', 'MISSING_IMAGE', 400);
    }

    if (preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/i', $base64Input, $matches)) {
        $mimeType = 'image/' . ($matches[1] === 'jpg' ? 'jpeg' : $matches[1]);
        $binary = base64_decode($matches[2]);
        if (!$binary) {
            json_error('Corrupted base64 image data.', 'INVALID_IMAGE', 400);
        }
        $imageInfo = @getimagesizefromstring($binary);
        if ($imageInfo) {
            $imageWidth = $imageInfo[0];
            $imageHeight = $imageInfo[1];
        }
        $imageData = $base64Input;
    } else {
        json_error('Unsupported image encoding. Base64 JPEG, PNG, or WebP required.', 'INVALID_IMAGE_FORMAT', 400);
    }
}

// 2. Perform Optical Character Recognition (OCR)
$ocrApiKey = OCR_API_KEY;
$ocrApiUrl = OCR_API_URL;

$ocrParams = [
    'apikey'            => $ocrApiKey,
    'base64Image'       => $imageData,
    'filetype'          => 'JPG',
    'isOverlayRequired' => 'true',
    'OCREngine'         => '2',
    'scale'             => 'true',
    'detectOrientation' => 'true'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ocrApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $ocrParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, OCR_TIMEOUT_SECONDS);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$ocrResponse = curl_exec($ch);
$curlError = curl_error($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($curlError || $httpStatus !== 200 || !$ocrResponse) {
    error_log("OCR API connection failed: {$curlError}, HTTP status: {$httpStatus}");
    json_error(
        'Unable to read license plate clearly. Please adjust lighting or reposition the camera.',
        'PLATE_UNREADABLE',
        422,
        ['actionable_tip' => 'Ensure the vehicle license plate is clearly centered, brightly illuminated, and free of glare.']
    );
}

$ocrData = json_decode($ocrResponse, true);

if (!isset($ocrData['OCRExitCode']) || $ocrData['OCRExitCode'] != 1 || empty($ocrData['ParsedResults'])) {
    $errDetail = $ocrData['ErrorMessage'][0] ?? 'No plate detected';
    error_log("OCR returned failure code: " . json_encode($ocrData));
    json_error(
        'Unable to read license plate clearly. Please adjust lighting or reposition the camera.',
        'NO_PLATE_DETECTED',
        422,
        [
            'raw_error'      => $errDetail,
            'actionable_tip' => 'Position the camera closer to the plate and hold steady.'
        ]
    );
}

$parsedResult = $ocrData['ParsedResults'][0];
$rawText = trim($parsedResult['ParsedText'] ?? '');

if (empty($rawText)) {
    json_error(
        'Unable to read license plate clearly. Please adjust lighting or reposition the camera.',
        'NO_PLATE_DETECTED',
        422,
        ['actionable_tip' => 'No legible text found. Adjust vehicle angle or lighting.']
    );
}

// 3. Extract license plate and determine bounding box overlay
$lines = $parsedResult['TextOverlay']['Lines'] ?? [];
$extracted = extract_plate_from_ocr($rawText, $lines);
$plateNumber = $extracted['plate'];
$confidence = $extracted['confidence'];

if (empty($plateNumber) || strlen($plateNumber) < 2) {
    json_error(
        'Unable to read license plate clearly. Please adjust lighting or reposition the camera.',
        'PLATE_UNREADABLE',
        422,
        [
            'detected_text'  => $rawText,
            'actionable_tip' => 'Ensure the license plate characters are clearly visible without obstruction.'
        ]
    );
}

// 4. Calculate bounding box coordinates for overlay display
$box = null;
if (!empty($lines)) {
    foreach ($lines as $line) {
        $lineText = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $line['LineText'] ?? ''));
        if (strpos($lineText, $plateNumber) !== false || strpos($plateNumber, $lineText) !== false) {
            if (!empty($line['Words'])) {
                $minLeft = 999999;
                $minTop  = 999999;
                $maxRight = 0;
                $maxBottom = 0;

                foreach ($line['Words'] as $w) {
                    $l = $w['Left'] ?? 0;
                    $t = $w['Top'] ?? 0;
                    $r = $l + ($w['Width'] ?? 0);
                    $b = $t + ($w['Height'] ?? 0);

                    if ($l < $minLeft)   $minLeft = $l;
                    if ($t < $minTop)    $minTop = $t;
                    if ($r > $maxRight)  $maxRight = $r;
                    if ($b > $maxBottom) $maxBottom = $b;
                }

                $box = [
                    'x'      => (int)$minLeft,
                    'y'      => (int)$minTop,
                    'width'  => (int)($maxRight - $minLeft),
                    'height' => (int)($maxBottom - $minTop)
                ];
                break;
            }
        }
    }
}

// If no word box found, compute an aesthetic centered box based on image dimensions
if (!$box && $imageWidth > 0 && $imageHeight > 0) {
    $box = [
        'x'      => (int)($imageWidth * 0.20),
        'y'      => (int)($imageHeight * 0.40),
        'width'  => (int)($imageWidth * 0.60),
        'height' => (int)($imageHeight * 0.20)
    ];
}

// 5. Successful Scan Response
json_response([
    'status' => 'success',
    'data'   => [
        'plate_number' => $plateNumber,
        'confidence'   => round($confidence, 2),
        'box'          => $box,
        'raw_text'     => $rawText,
        'image_meta'   => [
            'width'  => $imageWidth,
            'height' => $imageHeight
        ]
    ]
]);
