<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#b91c1c">
    <title>Login - <?= htmlspecialchars(app_config('name')) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= url('/public/favicon.ico?v=2', false) ?>">
    <link rel="stylesheet" href="<?= asset('pos-template/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=016">
</head>
<body>
<?= $content ?>
<script src="<?= asset('pos-template/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
