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

    if ($name === '' || $hex === '') {
        $skipped[] = ['name' => $name, 'reason' => 'empty name or value'];
        continue;
    }
    if (!preg_match('/^[0-9A-F]{8}$/', $hex)) {
        $skipped[] = ['name' => $name, 'reason' => 'value_hex must be 8 hex chars'];
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

    // Apply the 4-byte overwrite
    $content = substr_replace($content, $bytes, $status_pos, 4);
    $applied[] = ['name' => $name, 'pos' => $status_pos, 'hex' => $hex];
}

// Build a filename
$download_name = 'edited_' . date('Ymd_His') . '.ttp';

// Stream as file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . strlen($content));
echo $content;

// (Optional: you could log $applied / $skipped to a sidecar file.)
