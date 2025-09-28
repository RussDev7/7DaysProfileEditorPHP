<?php
require_once __DIR__ . '/lang.php';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('app.title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 700px;
            margin-top: 50px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #343a40;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
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
        <h1><?= t('app.title') ?></h1>
        <p><?= t('app.subtitle') ?></p>
        
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="ttpFile" class="form-label"><?= t('upload.select_file') ?>:</label>
                <input class="form-control" type="file" id="ttpFile" name="ttpFile" accept=".ttp" required>
            </div>
            <button type="submit" class="btn btn-primary"><?= t('upload.analyze') ?></button>
        </form>
    </div>
</body>
</html>