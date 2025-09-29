<?php
// save.php - writes a modified .ttp from posted edits.
// Usage: form should POST 'orig_file_b64' and 'statuses' (array of ['name' => string, 'value_hex' => 8-hex])

error_reporting(E_ALL);
ini_set('display_errors', 1);

function bad_request($msg) {
    http_response_code(400);
    echo "<h3>Error</h3><pre>" . htmlspecialchars($msg) . "</pre>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bad_request('POST required.');
}

$orig_b64 = $_POST['orig_file_b64'] ?? null;
if (!$orig_b64) {
    bad_request('Missing orig_file_b64.');
}

$content = base64_decode($orig_b64, true);
if ($content === false) {
    bad_request('Invalid base64 for orig_file_b64.');
}

// Edits arrive as: statuses[i][name], statuses[i][value_hex]
$statuses = $_POST['statuses'] ?? [];
if (!is_array($statuses)) $statuses = [];

$applied = [];
$skipped = [];

foreach ($statuses as $row) {
    $name = isset($row['name']) ? (string)$row['name'] : '';
    $hex  = isset($row['value_hex']) ? strtoupper(trim((string)$row['value_hex'])) : '';
    $dec  = isset($row['value_u32_be']) ? trim((string)$row['value_u32_be']) : '';

    if ($name === '') {
        $skipped[] = ['name' => $name, 'reason' => 'empty name'];
        continue;
    }

    // Validate hex if present
    $hexOk = ($hex !== '' && preg_match('/^[0-9A-F]{8}$/', $hex));

    // If hex is missing/invalid, but decimal is provided & valid (0..4294967295), derive hex (big-endian text).
    if (!$hexOk && $dec !== '' && preg_match('/^\d+$/', $dec)) {
        $n = (int)$dec; // safe on 64-bit PHP; 0..4294967295 fits
        if ($n >= 0 && $n <= 4294967295) {
            $hex   = strtoupper(str_pad(dechex($n), 8, '0', STR_PAD_LEFT));
            $hexOk = true;
        }
    }

    if (!$hexOk) {
        $skipped[] = ['name' => $name, 'reason' => 'invalid hex/decimal'];
        continue;
    }

    // Find keyword in the original binary, then assume the 4 bytes immediately BEFORE are the status.
    $pos = strpos($content, $name);
    if ($pos === false) {
        $skipped[] = ['name' => $name, 'reason' => 'keyword not found'];
        continue;
    }
    $status_pos = $pos - 4;
    if ($status_pos < 0 || $status_pos + 4 > strlen($content)) {
        $skipped[] = ['name' => $name, 'reason' => 'computed status position out of range'];
        continue;
    }

    $bytes = hex2bin($hex);
    if ($bytes === false || strlen($bytes) !== 4) {
        $skipped[] = ['name' => $name, 'reason' => 'hex2bin failed'];
        continue;
    }

    // Apply the 4-byte overwrite.
    $content = substr_replace($content, $bytes, $status_pos, 4);

    // (Optional) include decimal for audit/debug.
    $applied[] = [
        'name' => $name,
        'pos'  => $status_pos,
        'hex'  => $hex,
        // 'u32_be' => hexdec($hex), // uncomment if useful.
    ];
}

// Build a filename.
$download_name = 'edited_' . date('Ymd_His') . '.ttp';

// Stream as file download.
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . strlen($content));
echo $content;