<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = requireRole(['etudiant', 'entreprise', 'pedago', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'tout_lire') {
        marquerToutesNotificationsLues((int)$user['id']);
        flash('success', 'Toutes les notifications sont marquées comme lues.');
    }
    redirect('/notifications.php');
}

$notifications = listerNotifications((int)$user['id']);
$nonLues = nombreNotificationsNonLues((int)$user['id']);

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Notifications <?php if ($nonLues > 0): ?><span class="badge bg-danger"><?= (int)$nonLues ?></span><?php endif; ?></h1>
  <?php if ($nonLues > 0): ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="tout_lire">
      <button class="btn btn-outline-secondary btn-sm" type="submit">Tout marquer comme lu</button>
    </form>
  <?php endif; ?>
</div>

<?php if (!$notifications): ?>
  <div class="alert alert-light border">Aucune notification pour le moment.</div>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($notifications as $n): ?>
      <?php
        $libelle = NOTIFICATION_TYPES[$n['type']] ?? $n['type'];
        $date = (new DateTime($n['created_at']))->format('d/m/Y H:i');
        $href = BASE_URL . '/notification_ouvrir.php?id=' . (int)$n['id'];
      ?>
      <a class="list-group-item list-group-item-action <?= $n['lue'] ? '' : 'list-group-item-primary' ?>" href="<?= e($href) ?>">
        <div class="d-flex justify-content-between">
          <span><span class="badge bg-secondary me-2"><?= e($libelle) ?></span><?= e($n['message']) ?></span>
          <small class="text-muted ms-3 text-nowrap"><?= e($date) ?></small>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
