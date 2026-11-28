<?php
require __DIR__ . '/../includes/bootstrap.php';

// Le jeton est dans l'URL : ne pas le transmettre aux sites tiers via l'en-tête Referer.
header('Referrer-Policy: no-referrer');

$jeton = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$userId = userIdPourJetonReset($jeton);
$erreur = null;

if ($userId !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mdp = (string)($_POST['password'] ?? '');
    if ($mdp !== (string)($_POST['password2'] ?? '')) {
        $erreur = 'Les deux mots de passe ne sont pas identiques.';
    } else {
        $erreur = validerMotDePasse($mdp);
    }
    if ($erreur === null) {
        $pdo = getPdo();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash = ?, tentatives_echec = 0, bloque_jusqua = NULL WHERE id = ?')
            ->execute([password_hash($mdp, PASSWORD_DEFAULT), $userId]);
        $pdo->prepare('UPDATE password_resets SET utilise = 1 WHERE user_id = ?')->execute([$userId]);
        $pdo->commit();
        $_SESSION['user_id'] = $userId; // pour que journaliser() enregistre le bon utilisateur
        journaliser('reset_effectue');
        unset($_SESSION['user_id']);
        flash('success', 'Mot de passe modifié. Vous pouvez vous connecter.');
        redirect('/login.php');
    }
}

$pageTitle = 'Nouveau mot de passe';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="h3 mb-3">Nouveau mot de passe</h1>
    <?php if ($userId === null): ?>
      <div class="alert alert-danger">Ce lien est invalide, expiré ou déjà utilisé.</div>
      <p><a href="<?= e(BASE_URL) ?>/forgot_password.php">Demander un nouveau lien</a></p>
    <?php else: ?>
      <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= e($erreur) ?></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($jeton) ?>">
        <div class="mb-3">
          <label class="form-label" for="password">Nouveau mot de passe</label>
          <input class="form-control" type="password" id="password" name="password" required autofocus>
          <div class="form-text">8 caractères minimum, avec au moins une lettre et un chiffre.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="password2">Confirmer le mot de passe</label>
          <input class="form-control" type="password" id="password2" name="password2" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Enregistrer</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
