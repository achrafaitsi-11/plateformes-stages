<?php
declare(strict_types=1);

/**
 * Contrat M1 : notify($userId, $type, $message, $lien)
 * Version stub (1 déc.) : insère en base. La version complète (email simulé, compteur) arrive le 4 déc.
 */
function notify(int $userId, string $type, string $message, ?string $lien = null): void
{
    $stmt = getPdo()->prepare('INSERT INTO notifications (user_id, type, message, lien) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $type, mb_substr($message, 0, 255), $lien]);
}
