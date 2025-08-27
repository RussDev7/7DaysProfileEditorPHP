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
            $error_message = "解析中にエラーが発生しました: " . $e->getMessage();
        }
    } else {
        $error_message = "エラー: .ttpファイルをアップロードしてください。";
    }
} else {
    $error_message = "ファイルのアップロードに失敗しました。";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>解析結果 - TTP File Analyzer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 900px; margin-top: 50px; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .data-section { margin-bottom: 2rem; }
        .data-section h2 { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 15px; }
        .list-group-item { word-break: break-all; }
        .hex-value { font-family: 'Courier New', Courier, monospace; background-color: #e9ecef; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>解析結果</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <a href="index.php" class="btn btn-primary">戻る</a>
        <?php elseif ($parsed_data): ?>
            <div class="data-section">
                <h2><span class="badge bg-primary">プレイヤー情報</span></h2>
                <ul class="list-group">
                    <li class="list-group-item"><strong>キャラクター名:</strong> <?php echo htmlspecialchars($parsed_data['player_info']['name'] ?? '見つかりません'); ?></li>
                    <li class="list-group-item"><strong>プレイヤーID (EOS):</strong> <?php echo htmlspecialchars($parsed_data['player_info']['eos_id'] ?? '見つかりません'); ?></li>
                </ul>
            </div>

            <div class="data-section">
                <h2><span class="badge bg-warning text-dark">ステータス候補 (16進数)</span></h2>
                <p class="text-muted">キーワードの直前の4バイトを読み込んだ値です。実際のステータスとは異なる場合があります。</p>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>キーワード</th>
                            <th>ステータス候補 (Hex)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parsed_data['statuses'] as $status): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($status['name']); ?></td>
                                <td><span class="hex-value"><?php echo htmlspecialchars($status['potential_status_hex']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="data-section">
                <h2><span class="badge bg-success">クエスト & POI</span></h2>
                <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($parsed_data['quests_and_pois'] as $item): ?>
                        <li class="list-group-item py-1"><?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="data-section">
                <h2><span class="badge bg-info">スキル & パーク</span></h2>
                <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                     <?php foreach ($parsed_data['skills_and_perks'] as $item): ?>
                        <li class="list-group-item py-1"><?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <a href="index.php" class="btn btn-secondary mt-3">別のファイルを解析する</a>
        <?php endif; ?>
    </div>
</body>
</html>