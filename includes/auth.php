<?php
declare(strict_types=1);

/**
 * Contrat M1 : requireRole('etudiant') ou requireRole(['pedago','admin']).
 * Redirige vers la connexion si non connecté, 403 si le rôle ne correspond pas.
 * Retourne la ligne users de l'utilisateur connecté.
 */
function requireRole(string|array $roles): array
{
    $roles = (array)$roles;
    $user = currentUser();
    if ($user === null) {
        flash('warning', 'Veuillez vous connecter.');
        redirect('/login.php');
    }
    if (!in_array($user['role'], $roles, true) || $user['statut'] === 'suspendu') {
        http_response_code(403);
        exit('Accès interdit.');
    }
    return $user;
}

function currentUser(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = getPdo()->prepare('SELECT id, email, role, statut FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

/** id de la ligne etudiants / entreprises / responsables_pedago du compte connecté (anti-IDOR). */
function currentProfileId(string $table): ?int
{
    if (!in_array($table, ['etudiants', 'entreprises', 'responsables_pedago'], true) || empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = getPdo()->prepare("SELECT id FROM $table WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int)$id;
}

// TODO M1 (T05-T07) : login(), logout(), verrouillage après 5 échecs, session_regenerate_id().
// TODO M2 : inscription, vérification email, reset mot de passe.
