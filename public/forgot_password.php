<?php
require __DIR__ . '/../includes/bootstrap.php';

if (currentUser() !== null) {
    redirect(dashboardPath(currentUser()['role']));
}

$envoye = false;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = getPdo()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        if ($userId !== false) {
            $jeton = creerJetonReset((int)$userId);
            $lien = appUrl('/reset_password.php?token=' . $jeton);
            envoyerMail(
                $email,
                'Réinitialisation de votre mot de passe',
                "Bonjour,\n\nPour choisir un nouveau mot de passe, ouvrez ce lien (valable 30 minutes, usage unique) :\n$lien\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.",
                $lien
            );
            journaliser('reset_demande', 'user_id=' . $userId);
        }
    }
    // Même réponse que le compte existe ou non : pas d'énumération des comptes.
    $envoye = true;
}

$pageTitle = 'Mot de passe oublié';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="h3 mb-3">Mot de passe oublié</h1>
    <?php if ($envoye): ?>
      <div class="alert alert-success">
        Si un compte existe avec cette adresse, un lien de réinitialisation vient d'être envoyé. Il est valable 30 minutes.
      </div>
      <p><a href="<?= e(BASE_URL) ?>/login.php">Retour à la connexion</a></p>
    <?php else: ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="email">Votre adresse email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
        </div>
        <button class="btn btn-primary w-100" type="submit">Envoyer le lien</button>
      </form>
      <p class="mt-3 small"><a href="<?= e(BASE_URL) ?>/login.php">Retour à la connexion</a></p>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
