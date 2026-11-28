<?php
declare(strict_types=1);

/**
 * Notifications internes (cloche du menu + page notifications.php).
 * Contrat M1 : notify($userId, $type, $message, $lien)
 *   $type    : code court (ex. 'candidature_statut', 'compte_valide', 'message', 'candidature_nouvelle')
 *   $message : texte affiché (tronqué à 255 caractères, toujours échappé à l'affichage)
 *   $lien    : chemin INTERNE commençant par '/' (ex. '/etudiant/mes_candidatures.php'), sinon ignoré
 */

/** Libellés affichés pour les types connus ; un type inconnu s'affiche tel quel. */
const NOTIFICATION_TYPES = [
    'candidature_nouvelle' => 'Nouvelle candidature',
    'candidature_statut'   => 'Candidature',
    'compte_valide'        => 'Compte',
    'compte_rejete'        => 'Compte',
    'message'              => 'Message',
    'stage'                => 'Stage',
];

/** Accepte uniquement un chemin interne ('/page.php?x=1'), jamais une URL externe ni '//hote'. */
function lienInterneValide(?string $lien): bool
{
    return $lien === null || preg_match('#^/(?!/)[^\s\\\\]{0,240}$#', $lien) === 1;
}

function notify(int $userId, string $type, string $message, ?string $lien = null): void
{
    if (!lienInterneValide($lien)) {
        $lien = null; // jamais de lien externe stocké (pas de redirection ouverte)
    }
    $stmt = getPdo()->prepare('INSERT INTO notifications (user_id, type, message, lien) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, mb_substr($type, 0, 40), mb_substr($message, 0, 255), $lien]);
}

function nombreNotificationsNonLues(int $userId): int
{
    $stmt = getPdo()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lue = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function listerNotifications(int $userId, int $limite = 50): array
{
    $stmt = getPdo()->prepare('SELECT id, type, message, lien, lue, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . max(1, min(200, $limite)));
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Marque une notification comme lue ; la condition user_id empêche de toucher celle d'un autre. */
function marquerNotificationLue(int $id, int $userId): ?array
{
    $stmt = getPdo()->prepare('SELECT id, lien FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $n = $stmt->fetch();
    if ($n) {
        getPdo()->prepare('UPDATE notifications SET lue = 1 WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
    }
    return $n ?: null;
}

function marquerToutesNotificationsLues(int $userId): void
{
    getPdo()->prepare('UPDATE notifications SET lue = 1 WHERE user_id = ? AND lue = 0')->execute([$userId]);
}
