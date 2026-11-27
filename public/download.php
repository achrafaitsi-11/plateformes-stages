<?php
/**
 * Téléchargement contrôlé des fichiers de storage/ (jamais d'accès direct).
 *   download.php?t=cv&id=12        CV : propriétaire, entreprise destinataire d'une candidature, pedago, admin
 *   download.php?t=logo&id=3       logo d'entreprise : public
 *   download.php?t=justificatif&id=3  justificatif entreprise : propriétaire ou admin
 * TODO M1/M6 : lettres, rapports, attestations (même schéma : vérifier le droit, puis servir).
 */
require __DIR__ . '/../includes/bootstrap.php';

function envoyerFichier(string $dossier, ?string $nom, string $nomAffiche): never
{
    $base = realpath(STORAGE_PATH . '/' . $dossier);
    $chemin = $nom ? realpath($base . '/' . basename($nom)) : false;
    if ($base === false || $chemin === false || !str_starts_with($chemin, $base . DIRECTORY_SEPARATOR) || !is_file($chemin)) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($chemin) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($chemin));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $nomAffiche) . '"');
    readfile($chemin);
    exit;
}

function refuser(): never
{
    http_response_code(403);
    exit('Accès interdit.');
}

$type = (string)($_GET['t'] ?? '');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Fichier introuvable.');
}
$pdo = getPdo();

if ($type === 'logo') {
    $s = $pdo->prepare('SELECT logo FROM entreprises WHERE id = ?');
    $s->execute([$id]);
    envoyerFichier('logos', $s->fetchColumn() ?: null, 'logo');
}

$user = requireRole(['etudiant', 'entreprise', 'pedago', 'admin']);

if ($type === 'cv') {
    $s = $pdo->prepare('SELECT cv.fichier, cv.etudiant_id FROM cv WHERE cv.id = ?');
    $s->execute([$id]);
    $cv = $s->fetch();
    if (!$cv) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $ok = in_array($user['role'], ['admin', 'pedago'], true);
    if ($user['role'] === 'etudiant') {
        $ok = currentProfileId('etudiants') === (int)$cv['etudiant_id'];
    } elseif ($user['role'] === 'entreprise') {
        $q = $pdo->prepare(
            'SELECT 1 FROM candidatures c JOIN offres o ON o.id = c.offre_id
             WHERE c.cv_id = ? AND o.entreprise_id = ? LIMIT 1'
        );
        $q->execute([$id, currentProfileId('entreprises')]);
        $ok = (bool)$q->fetchColumn();
    }
    $ok || refuser();
    envoyerFichier('cv', $cv['fichier'], 'cv-' . $id . '.pdf');
}

if ($type === 'justificatif') {
    $s = $pdo->prepare('SELECT justificatif, user_id FROM entreprises WHERE id = ?');
    $s->execute([$id]);
    $e = $s->fetch();
    if (!$e) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    ($user['role'] === 'admin' || (int)$e['user_id'] === (int)$user['id']) || refuser();
    envoyerFichier('justificatifs', $e['justificatif'], 'justificatif-' . $id);
}

http_response_code(404);
exit('Type de fichier inconnu.');
