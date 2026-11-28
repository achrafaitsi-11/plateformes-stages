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
    // Cache valable pour un user_id donné : se recharge après login() ou logout().
    static $cacheId = null;
    static $user = null;
    $id = $_SESSION['user_id'] ?? null;
    if ($id !== $cacheId) {
        $cacheId = $id;
        $user = null;
        if ($id !== null) {
            $stmt = getPdo()->prepare('SELECT id, email, role, statut FROM users WHERE id = ?');
            $stmt->execute([$id]);
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

const LOGIN_MAX_ECHECS = 5;
const LOGIN_BLOCAGE_MINUTES = 15;

/**
 * Tente une connexion. Retourne null si OK, sinon un message d'erreur affichable.
 * Message volontairement identique pour « email inconnu » et « mauvais mot de passe »
 * (pas d'énumération des comptes), avec un hachage factice pour garder un temps de réponse proche.
 */
function login(string $email, string $password): ?string
{
    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT id, password_hash, role, statut, tentatives_echec, (bloque_jusqua IS NOT NULL AND bloque_jusqua > NOW()) AS bloque FROM users WHERE email = ?');
    $stmt->execute([mb_strtolower(trim($email))]);
    $u = $stmt->fetch();

    $generique = 'Email ou mot de passe incorrect.';
    if (!$u) {
        password_verify($password, '$2y$10$usesomesillystringforsaleuZLMeXgy0ehTRXF9o9rvDmK4h2JGe');
        return $generique;
    }
    if ((int)$u['bloque'] === 1) {
        return 'Compte temporairement bloqué après trop d\'échecs. Réessayez dans quelques minutes.';
    }
    if (!password_verify($password, $u['password_hash'])) {
        $n = (int)$u['tentatives_echec'] + 1;
        if ($n >= LOGIN_MAX_ECHECS) {
            $pdo->prepare('UPDATE users SET tentatives_echec = 0, bloque_jusqua = DATE_ADD(NOW(), INTERVAL ' . LOGIN_BLOCAGE_MINUTES . ' MINUTE) WHERE id = ?')
                ->execute([$u['id']]);
            journaliser('login_bloque', 'user_id=' . $u['id']);
        } else {
            $pdo->prepare('UPDATE users SET tentatives_echec = ? WHERE id = ?')->execute([$n, $u['id']]);
        }
        return $generique;
    }
    if ($u['statut'] === 'non_verifie') {
        return 'Veuillez d\'abord vérifier votre adresse email.';
    }
    if (in_array($u['statut'], ['rejete', 'suspendu'], true)) {
        return 'Ce compte n\'est pas autorisé à se connecter. Contactez l\'administration.';
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    $pdo->prepare('UPDATE users SET tentatives_echec = 0, bloque_jusqua = NULL, derniere_connexion = NOW() WHERE id = ?')
        ->execute([$u['id']]);
    journaliser('login_ok');
    return null;
}

function logout(): void
{
    journaliser('logout');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Page d'accueil de chaque rôle ; repli sur l'accueil tant que la page du rôle n'existe pas. */
function dashboardPath(string $role): string
{
    $map = [
        'etudiant'   => '/etudiant/index.php',
        'entreprise' => '/entreprise/index.php',
        'pedago'     => '/pedago/index.php',
        'admin'      => '/admin/index.php',
    ];
    $path = $map[$role] ?? '/index.php';
    return is_file(dirname(__DIR__) . '/public' . $path) ? $path : '/index.php';
}

/**
 * Règles de mot de passe, partagées (inscription M2, réinitialisation M1).
 * Retourne null si le mot de passe est accepté, sinon un message affichable.
 */
function validerMotDePasse(string $mdp): ?string
{
    if (mb_strlen($mdp) < 8) {
        return 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    if (mb_strlen($mdp) > 72) {
        return 'Le mot de passe ne doit pas dépasser 72 caractères.';
    }
    if (!preg_match('/[A-Za-z]/', $mdp) || !preg_match('/\d/', $mdp)) {
        return 'Le mot de passe doit contenir au moins une lettre et un chiffre.';
    }
    return null;
}

/** Crée un jeton de réinitialisation (valable 30 min, usage unique) et retourne le jeton en clair. */
function creerJetonReset(int $userId): string
{
    $pdo = getPdo();
    // Un seul jeton valide à la fois par utilisateur.
    $pdo->prepare('UPDATE password_resets SET utilise = 1 WHERE user_id = ? AND utilise = 0')->execute([$userId]);
    $jeton = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expire_le) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
        ->execute([$userId, hash('sha256', $jeton)]);
    return $jeton;
}

/** Retourne l'id de l'utilisateur si le jeton est valide (existe, non utilisé, non expiré), sinon null. */
function userIdPourJetonReset(string $jeton): ?int
{
    if (!preg_match('/^[0-9a-f]{64}$/', $jeton)) {
        return null;
    }
    $stmt = getPdo()->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND utilise = 0 AND expire_le > NOW()');
    $stmt->execute([hash('sha256', $jeton)]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int)$id;
}

// TODO M2 : inscription, vérification d'email.
