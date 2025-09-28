<?php
require_once __DIR__ . '/lang.php';
?>

<?php
require_once 'TtpParser.php';

$error_message = '';
$parsed_data = null;

if (isset($_FILES['ttpFile']) && $_FILES['ttpFile']['error'] === UPLOAD_ERR_OK) {
    $file_tmp_path = $_FILES['ttpFile']['tmp_name'];
    $file_name = $_FILES['ttpFile']['name'];

    if (pathinfo($file_name, PATHINFO_EXTENSION) === 'ttp') {
        try {
            $parser = new TtpParser($file_tmp_path);
            $parsed_data = $parser->parse();
        } catch (Exception $e) {
            $error_message = "解析中にエラーが発生しました (An error occurred during parsing): " . $e->getMessage();
        }
    } else {
        $error_message = "エラー: .ttpファイルをアップロードしてください。 (Error: Please upload a .ttp file.)";
    }
} else {
    $error_message = "ファイルのアップロードに失敗しました。 (File upload failed.)";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('app.title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 900px; margin-top: 50px; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .data-section { margin-bottom: 2rem; }
        .data-section h2 { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 15px; }
        .list-group-item { word-break: break-all; }
        .hex-value { font-family: 'Courier New', Courier, monospace; background-color: #e9ecef; padding: 2px 6px; border-radius: 4px; }
    </style>
	<div class="text-end mb-3">
        <small><?= t('nav.language') ?>:
            <a href="?lang=en"><?= t('lang.english') ?></a> |
            <a href="?lang=ja"><?= t('lang.japanese') ?></a>
        </small>
    </div>
</head>
<body>
    <div class="container">
        <h1>解析結果 (Analysis results)</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <a href="index.php" class="btn btn-primary">戻る (Return)</a>
        <?php elseif ($parsed_data): ?>
            <div class="data-section">
                <h2><span class="badge bg-primary"><?= t('section.player_info') ?></span></h2>
                <ul class="list-group">
                    <li class="list-group-item"><strong>キャラクター名 (Character Name):</strong> <?php echo htmlspecialchars($parsed_data['player_info']['name'] ?? '見つかりません'); ?></li>
                    <li class="list-group-item"><strong>プレイヤーID (Player ID):</strong> <?php echo htmlspecialchars($parsed_data['player_info']['eos_id'] ?? '見つかりません'); ?></li>
                </ul>
            </div>

            <div class="data-section">
                <h2><span class="badge bg-warning text-dark">ステータス候補 (16進数) (Status candidate (hexadecimal))</span></h2>
                <p class="text-muted">キーワードの直前の4バイトを読み込んだ値です。実際のステータスとは異なる場合があります。 (This is the value read from the 4 bytes immediately before the keyword. It may differ from the actual status.)</p>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>キーワード (Keyword)</th>
                            <th>ステータス候補 (Hex)</th>
							<th>値 (UInt32 LE)</th> 
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($parsed_data['statuses'] as $status): ?>
                        <?php
                        $hex = $status['potential_status_hex'] ?? '';
                        $bin = ($hex && preg_match('/^[0-9A-Fa-f]{8}$/', $hex)) ? hex2bin($hex) : false;
                    
                        // Little-endian unsigned 32-bit
                        $valLE = ($bin !== false) ? unpack('V', $bin)[1] : null;
                    
                        // (Optional) Big-endian and signed views if you want them:
                        // $valBE = ($bin !== false) ? unpack('N', $bin)[1] : null;
                        // $signedLE = ($valLE !== null && $valLE > 0x7FFFFFFF) ? $valLE - 0x100000000 : $valLE;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($status['name']) ?></td>
                            <td><span class="hex-value"><?= htmlspecialchars(strtoupper($hex)) ?></span></td>
                            <td>
                                <?php if ($valLE !== null): ?>
                                    <?= htmlspecialchars((string)$valLE) ?>
                                    <!-- (Optional): <small class="text-muted">BE <?= '<?= htmlspecialchars((string)$valBE) ?>' ?></small> -->
                                    <!-- (Optional): <small class="text-muted">Int32 <?= '<?= htmlspecialchars((string)$signedLE) ?>' ?></small> -->
                                <?php else: ?>
                                    <span class="text-danger">invalid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    
                </table>
            </div>

            <div class="data-section">
                <h2><span class="badge bg-success"><?= t('section.quests_pois') ?></span></h2>
                <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($parsed_data['quests_and_pois'] as $item): ?>
                        <li class="list-group-item py-1"><?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="data-section">
                <h2><span class="badge bg-info"><?= t('section.statuses') ?></span></h2>
                <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                    <?php
                    $skills = $parsed_data['skills_and_perks'] ?? ($parsed_data['statuses'] ?? []);
                    if (!is_array($skills)) { $skills = []; }
                    foreach ($skills as $item): ?>
                        <li class="list-group-item py-1">
                            <?php echo htmlspecialchars(is_array($item) ? ($item['name'] ?? (string)$item) : (string)$item); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <a href="index.php" class="btn btn-secondary mt-3"><?= t('section.quests_pois') ?></a>
        <?php endif; ?>
		
        <?php if ($parsed_data): ?>
          <hr class="my-4">
          <h2><span class="badge bg-success">編集して保存</span></h2>
          <form action="save.php" method="post" class="mt-3">
            <!-- Embed the original uploaded file as base64 so save.php can patch it -->
            <input type="hidden" name="orig_file_b64"
                   value="<?php echo htmlspecialchars(base64_encode(file_get_contents($file_tmp_path)), ENT_QUOTES, 'UTF-8'); ?>">

            <div class="mb-2 text-muted">各行は「キーワード」と、その直前の4バイト(HEX)です。</div>

            <?php $statuses = $parsed_data['statuses'] ?? []; ?>
            <?php if (is_array($statuses) && count($statuses)): ?>
              <?php foreach ($statuses as $i => $row): 
                    $name = is_array($row) ? ($row['name'] ?? '') : (string)$row;
                    $hex  = is_array($row) ? ($row['potential_status_hex'] ?? '') : '';
              ?>
                <div class="input-group mb-2">
                  <span class="input-group-text" style="min-width: 260px;">
                    <?php echo htmlspecialchars($name); ?>
                  </span>
                  <input type="hidden" name="statuses[<?php echo $i; ?>][name]"
                         value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="text" class="form-control" placeholder="8 hex chars (e.g. 01000000)"
                         name="statuses[<?php echo $i; ?>][value_hex]"
                         value="<?php echo htmlspecialchars($hex, ENT_QUOTES, 'UTF-8'); ?>"
                         maxlength="8" pattern="[0-9A-Fa-f]{8}" required>
                </div>
              <?php endforeach; ?>
              <button type="submit" class="btn btn-success mt-2">編集した .ttp をダウンロード</button>
            <?php else: ?>
              <div class="alert alert-warning">編集可能なステータスが見つかりませんでした。</div>
            <?php endif; ?>
          </form>
        <?php endif; ?>
    </div>
</body>
</html>