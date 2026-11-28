<?php
declare(strict_types=1);

/**
 * URL absolue d'une page du site, pour les liens envoyés par email.
 * Définir APP_URL dans config.php en production (ex. 'https://stages.exemple.ma') :
 * sinon l'hôte de la requête est utilisé (acceptable en démonstration locale uniquement).
 */
function appUrl(string $path): string
{
    $base = defined('APP_URL')
        ? rtrim(APP_URL, '/')
        : ((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return $base . BASE_URL . $path;
}

/**
 * Envoie un email (contrat partagé : M1 mot de passe oublié, M2 vérification d'email).
 *   MAIL_MODE = 'simulation' : le message est écrit dans storage/mails/ et, si $lienDemo est fourni,
 *                              le lien est affiché à l'écran (pratique pour la démo, aucun serveur SMTP requis).
 *   MAIL_MODE = 'smtp'       : TODO (PHPMailer), non nécessaire pour la remise.
 */
function envoyerMail(string $destinataire, string $sujet, string $corps, ?string $lienDemo = null): void
{
    if (MAIL_MODE === 'simulation') {
        $dir = STORAGE_PATH . '/mails';
        if (is_dir($dir) && is_writable($dir)) {
            $nom = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.txt';
            file_put_contents(
                $dir . '/' . $nom,
                "À : $destinataire\nSujet : $sujet\nDate : " . date('c') . "\n\n$corps\n"
            );
        }
        if ($lienDemo !== null) {
            flash('info', 'Mode démonstration (aucun email réel envoyé). Lien : ' . $lienDemo);
        }
        return;
    }
    throw new LogicException("MAIL_MODE 'smtp' pas encore implémenté.");
}
