<?php
/** Variables attendues avant l'include : $pageTitle (string). */
$pageTitle = $pageTitle ?? 'Plateforme de stages';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · Plateforme de stages</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= e(BASE_URL) ?>/index.php">Plateforme de stages</a>
    <ul class="navbar-nav ms-auto">
      <?php if ($user): ?>
        <li class="nav-item"><span class="nav-link"><?= e($user['email']) ?> (<?= e($user['role']) ?>)</span></li>
        <li class="nav-item">
          <form method="post" action="<?= e(BASE_URL) ?>/logout.php" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-link nav-link" type="submit">Déconnexion</button>
          </form>
        </li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="<?= e(BASE_URL) ?>/login.php">Connexion</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(BASE_URL) ?>/register.php">Inscription</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>
<main class="container my-4">
<?php foreach (getFlashes() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
