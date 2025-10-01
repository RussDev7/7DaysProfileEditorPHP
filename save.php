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

// --- Remove Quests & POIs ---
$remove_quests = $_POST['remove_quests'] ?? [];
if (!is_array($remove_quests)) $remove_quests = [];
$remove_quests = array_values(array_unique(array_filter(array_map('strval', $remove_quests))));

$removeAll   = !empty($_POST['quests_remove_all']);
$statusOnly  = !empty($_POST['quests_status_only']); // <-- your toggle

$quest_removals = [];

foreach ($remove_quests as $token) {
    $hits = 0;
    $len  = strlen($token);
    if ($len === 0) continue;

    if ($removeAll) {
        $offset = 0;
        while (($pos = strpos($content, $token, $offset)) !== false) {
            // Zero the 4 bytes immediately before the keyword (status reset).
            $status_pos = $pos - 4;
            if ($status_pos >= 0 && $status_pos + 4 <= strlen($content)) {
                $content = substr_replace($content, "\x00\x00\x00\x00", $status_pos, 4);
            }

            // Scrub the keyword in-place unless "status only" is checked.
            if (!$statusOnly) {
                $content = substr_replace($content, str_repeat("\x00", $len), $pos, $len);
            }

            // Move past this occurrence to avoid looping on the same match.
            $offset = $pos + $len;
            $hits++;
        }
    } else {
        $pos = strpos($content, $token);
        if ($pos !== false) {
            $status_pos = $pos - 4;
            if ($status_pos >= 0 && $status_pos + 4 <= strlen($content)) {
                $content = substr_replace($content, "\x00\x00\x00\x00", $status_pos, 4);
            }
            if (!$statusOnly) {
                $content = substr_replace($content, str_repeat("\x00", $len), $pos, $len);
            }
            $hits = 1;
        }
    }

    $quest_removals[] = ['token' => $token, 'hits' => $hits, 'status_only' => $statusOnly];
}

// --- Remove Statuses ---
$remove_statuses = $_POST['remove_statuses'] ?? [];
if (!is_array($remove_statuses)) $remove_statuses = [];
$remove_statuses = array_values(array_unique(array_filter(array_map('strval', $remove_statuses))));

$st_remove_all  = !empty($_POST['statuses_remove_all']);
$st_status_only = !empty($_POST['statuses_status_only']); // If true: zero bytes only, don't scrub text.
$status_removals = [];

foreach ($remove_statuses as $token) {
    $hits = 0;
    $len  = strlen($token);
    if ($len === 0) continue;

    if ($st_remove_all) {
        $offset = 0;
        while (($pos = strpos($content, $token, $offset)) !== false) {
            // Zero the 4 bytes immediately before the keyword (status reset).
            $status_pos = $pos - 4;
            if ($status_pos >= 0 && $status_pos + 4 <= strlen($content)) {
                $content = substr_replace($content, "\x00\x00\x00\x00", $status_pos, 4);
            }
            // Optionally scrub the keyword text in-place (no size change).
            if (!$st_status_only) {
                $content = substr_replace($content, str_repeat("\x00", $len), $pos, $len);
            }
            $offset = $pos + $len;
            $hits++;
        }
    } else {
        $pos = strpos($content, $token);
        if ($pos !== false) {
            $status_pos = $pos - 4;
            if ($status_pos >= 0 && $status_pos + 4 <= strlen($content)) {
                $content = substr_replace($content, "\x00\x00\x00\x00", $status_pos, 4);
            }
            if (!$st_status_only) {
                $content = substr_replace($content, str_repeat("\x00", $len), $pos, $len);
            }
            $hits = 1;
        }
    }

    $status_removals[] = ['token' => $token, 'hits' => $hits];
}

// Build a filename.
$download_name = 'edited_' . date('Ymd_His') . '.ttp';

// Stream as file download.
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . strlen($content));
echo $content;