<?php
require_once __DIR__ . '/lang.php';      // starts session
require_once __DIR__ . '/TtpParser.php';

$error_message  = '';
$parsed_data    = null;
$orig_file_b64  = $_SESSION['orig_file_b64'] ?? null;

// Allow a reset link to clear the current analysis.
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['parsed_data'], $_SESSION['orig_file_b64']);
    header('Location: index.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_FILES['ttpFile']) && $_FILES['ttpFile']['error'] === UPLOAD_ERR_OK) {

    $file_tmp_path = $_FILES['ttpFile']['tmp_name'];
    $file_name     = $_FILES['ttpFile']['name'];

    if (strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) === 'ttp') {
        try {
            $parser       = new TtpParser($file_tmp_path);
            $parsed_data  = $parser->parse();

            // Persist for later GETs (language switches, refresh, etc.)
            $_SESSION['parsed_data']   = $parsed_data;
            $_SESSION['orig_file_b64'] = base64_encode(file_get_contents($file_tmp_path));
            $orig_file_b64             = $_SESSION['orig_file_b64'];

        } catch (Exception $e) {
            $error_message = t('upload.parsing.error') . ' ' . $e->getMessage();
        }
    } else {
        $error_message = t('upload.parsing.format');
    }
} else {
    // GET or no upload: Reuse any existing analysis from session.
    $parsed_data = $_SESSION['parsed_data'] ?? null;
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
<style>
.text-monospace { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
.is-invalid { border-color:#dc3545; }
.is-valid   { border-color:#198754; }
</style>
<body>
    <div class="container">
        <h1><?= t('section.main.container') ?></h1>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <a href="index.php" class="btn btn-primary"><?= t('upload.error') ?></a>
        <?php elseif ($parsed_data): ?>
            <?php
            $player = $parsed_data['player_info'] ?? [];
            $name   = isset($player['name'])    ? htmlspecialchars($player['name'], ENT_QUOTES, 'UTF-8')    : t('common.not_found');
            $eosId  = isset($player['eos_id'])  ? htmlspecialchars($player['eos_id'], ENT_QUOTES, 'UTF-8')  : t('common.not_found');
            ?>
            <div class="data-section">
              <h2><span class="badge bg-primary"><?= t('section.player_info') ?></span></h2>
              <dl class="row mb-0">
                <dt class="col-sm-4"><?= t('player.name') ?></dt>
                <dd class="col-sm-8"><?= $name ?></dd>

                <dt class="col-sm-4"><?= t('player.id') ?></dt>
                <dd class="col-sm-8"><span class="text-monospace"><?= $eosId ?></span></dd>
              </dl>
            </div>

            <div class="data-section">
                <form action="save.php" method="post" class="data-section" id="status-edit-form">
                    <h2><span class="badge bg-warning text-dark"><?= t('section.data') ?></span></h2>
                    <p class="text-muted large mb-2">
                        <?= t('section.data.sub_one') ?><br>
                        <?= t('section.data.sub_two') ?>
                    </p>

                    <!-- Keep the original file bytes so save.php can patch them -->
                    <input type="hidden" name="orig_file_b64"
                        value="<?= htmlspecialchars($orig_file_b64 ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?= t('table.keyword') ?></th>
                                <th><?= t('table.value.hex') ?></th>
                                <th><?= t('table.value.uint') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($parsed_data['statuses'] ?? []) as $i => $status):
                                $name = $status['name'] ?? '';
                                $hex  = strtoupper($status['potential_status_hex'] ?? '');
                                $dec  = (ctype_xdigit($hex) && strlen($hex) === 8) ? hexdec($hex) : '';
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($name) ?>
                                    <input type="hidden" name="statuses[<?= $i ?>][name]"
                                           value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                </td>

                                <td style="min-width:260px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">0x</span>
                                        <input
                                            id="hex_<?= $i ?>"
                                            name="statuses[<?= $i ?>][value_hex]"
                                            class="form-control text-monospace"
                                            value="<?= htmlspecialchars($hex) ?>"
                                            pattern="[0-9A-Fa-f]{8}"
                                            maxlength="8"
                                            autocomplete="off"
                                            inputmode="latin"
                                            required
                                        >
                                    </div>
                                </td>

                                <td style="min-width:200px;">
                                    <input
                                        id="dec_<?= $i ?>"
                                        name="statuses[<?= $i ?>][value_u32_be]"
                                        type="number"
                                        class="form-control form-control-sm"
                                        min="0" max="4294967295"
                                        value="<?= htmlspecialchars((string)$dec) ?>"
                                        autocomplete="off"
                                    >
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit" class="btn btn-success mt-2"><?= t('save.download') ?></button>
                </form>
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
        <?php endif; ?>
    </div>
<script>
(function(){
  function clamp32(n){
    n = Number(String(n).replace(/[^\d]/g,'')) || 0;
    if (n < 0) n = 0;
    if (n > 4294967295) n = 4294967295;
    return Math.floor(n);
  }
  function hexSan(h){
    return (h || '').replace(/[^0-9a-fA-F]/g,'').slice(0,8).toUpperCase();
  }
  function toHex8BE(n){
    return (clamp32(n) >>> 0).toString(16).padStart(8,'0').toUpperCase();
  }

  const hexInputs = document.querySelectorAll('input[id^="hex_"]');
  let updating = false;

  hexInputs.forEach(hexInput => {
    const id = hexInput.id.split('_')[1];
    const decInput = document.getElementById('dec_' + id);

    // Hex -> Dec
    hexInput.addEventListener('input', () => {
      if (updating) return; updating = true;
      const clean = hexSan(hexInput.value);
      hexInput.value = clean;

      if (clean.length === 8) {
        const val = parseInt(clean, 16); // interpret as BE
        decInput.value = String(val);
        hexInput.classList.remove('is-invalid'); hexInput.classList.add('is-valid');
      } else {
        hexInput.classList.remove('is-valid'); hexInput.classList.add('is-invalid');
      }
      updating = false;
    });

    // Dec -> Hex
    decInput.addEventListener('input', () => {
      if (updating) return; updating = true;
      const val = clamp32(decInput.value);
      decInput.value = String(val);
      hexInput.value = toHex8BE(val);       // keep hex authoritative
      hexInput.classList.remove('is-invalid'); hexInput.classList.add('is-valid');
      updating = false;
    });
  });
})();
</script>
</body>
</html>