<?php
declare(strict_types=1);

/**
 * Contrat M1 : enregistre un fichier envoyé de façon sécurisée.
 *   $dossier : 'cv' | 'lettres' | 'justificatifs' | 'rapports' | 'logos' | 'photos'
 *   $types   : types MIME autorisés, ex. ['application/pdf']
 * Retourne le nom stocké (aléatoire) ou lève une RuntimeException avec un message affichable.
 * Les fichiers vivent dans storage/ (hors racine web) et sont servis via public/download.php.
 */
function uploadFichier(array $file, string $dossier, array $types, int $maxBytes): string
{
    $dossiersOk = ['cv', 'lettres', 'justificatifs', 'rapports', 'logos', 'photos'];
    if (!in_array($dossier, $dossiersOk, true)) {
        throw new RuntimeException('Dossier invalide.');
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Erreur lors de l'envoi du fichier.");
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Fichier trop volumineux.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, $types, true)) {
        throw new RuntimeException('Type de fichier non autorisé.');
    }
    $ext = ['application/pdf' => 'pdf', 'image/png' => 'png', 'image/jpeg' => 'jpg'][$mime] ?? 'bin';
    $nom = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], STORAGE_PATH . "/$dossier/$nom")) {
        throw new RuntimeException("Impossible d'enregistrer le fichier.");
    }
    return $nom;
}
