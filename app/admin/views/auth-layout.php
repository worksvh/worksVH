<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – Administrace VH Works</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700&amp;family=Montserrat:wght@400;500;600;700&amp;display=swap">
<link rel="stylesheet" href="/assets/css/admin.css?v=<?= e((string) @filemtime(PUBLIC_PATH . '/assets/css/admin.css')) ?>">
</head>
<body class="admin auth">
<main class="auth-box">
  <img src="/assets/img/vh-works-logo.svg" alt="VH Works" width="217" height="36" class="auth-logo">
  <?php foreach (take_flash() as $f): ?>
    <div class="toast toast-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div>
  <?php endforeach ?>
  <?= $body ?>
</main>
</body>
</html>
