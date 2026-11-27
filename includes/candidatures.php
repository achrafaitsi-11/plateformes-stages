<?php
declare(strict_types=1);

/**
 * Contrat M3 : changerStatutCandidature($id, $statut, $userId)
 * Point UNIQUE pour changer un statut : vérifie la transition, écrit historique_candidature,
 * notifie l'étudiant, et appelle creerStage() quand la candidature passe à 'confirmee'.
 */
function changerStatutCandidature(int $candidatureId, string $statut, int $userId): void
{
    // TODO M3 (T40) : implémentation. Stub volontairement vide pour que les autres puissent l'appeler.
    throw new LogicException('changerStatutCandidature() pas encore implémentée.');
}
