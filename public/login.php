<?php
require __DIR__ . '/../includes/bootstrap.php';

if (currentUser() !== null) {
    redirect(dashboardPath(currentUser()['role']));
}

$erreur = null;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim((string)($_POST['email'] ?? ''));
    $erreur = login($email, (string)($_POST['password'] ?? ''));
    if ($erreur === null) {
        redirect(dashboardPath(currentUser()['role']));
    }
}

$pageTitle = 'Connexion';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="h3 mb-3">Connexion</h1>
    <?php if ($erreur): ?>
      <div class="alert alert-danger"><?= e($erreur) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Mot de passe</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Se connecter</button>
    </form>
    <p class="mt-3 small">
      <a href="<?= e(BASE_URL) ?>/forgot_password.php">Mot de passe oublié ?</a> ·
      <a href="<?= e(BASE_URL) ?>/register.php">Créer un compte</a>
    </p>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
