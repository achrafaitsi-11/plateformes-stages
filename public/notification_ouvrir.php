<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = requireRole(['etudiant', 'entreprise', 'pedago', 'admin']);

// Marque la notification comme lue puis redirige vers sa page (chemin interne uniquement).
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$n = $id ? marquerNotificationLue($id, (int)$user['id']) : null;

if ($n && $n['lien'] !== null && lienInterneValide($n['lien'])) {
    redirect($n['lien']);
}
redirect('/notifications.php');
