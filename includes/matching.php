<?php
declare(strict_types=1);

/**
 * Contrat M5 : calculerScore(array $etudiant, array $offre): int (0 à 100)
 * Pondération du cahier des charges : 40 % compétences, 15 % domaine,
 * 10 % chacun pour niveau / localisation / mode, 5 % chacun pour durée / type / rémunération.
 * Une offre sans aucune compétence requise ne doit pas provoquer de division par zéro.
 */
function calculerScore(array $etudiant, array $offre): int
{
    // TODO M5 (T55) : implémentation. Stub neutre en attendant.
    return 0;
}
