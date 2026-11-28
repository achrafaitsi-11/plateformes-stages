<?php
/** PAGE D'ESSAI LOCALE (dev uniquement) : appelle notify(). Supprimée avant la PR. */
require __DIR__ . '/../includes/bootstrap.php';
if (APP_ENV !== 'dev' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}
requireRole(['etudiant', 'entreprise', 'pedago', 'admin']);
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['csrf' => csrf_token()]);
    exit;
}
csrf_check();
$to = (int)($_POST['to'] ?? 0);
notify($to, (string)($_POST['type'] ?? 'test'), (string)($_POST['msg'] ?? ''), isset($_POST['lien']) ? (string)$_POST['lien'] : null);
$id = (int)getPdo()->lastInsertId();
$row = getPdo()->prepare('SELECT id, user_id, type, CHAR_LENGTH(message) AS longueur, lien, lue FROM notifications WHERE id = ?');
$row->execute([$id]);
echo json_encode($row->fetch());
