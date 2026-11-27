<?php
declare(strict_types=1);

/** Échappe une valeur pour l'affichage HTML (à utiliser sur TOUTE sortie). */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** Message éphémère affiché au prochain chargement : flash('success', '...'). */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Retourne et vide les messages flash. */
function getFlashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function journaliser(string $action, ?string $detail = null): void
{
    $stmt = getPdo()->prepare('INSERT INTO journal_audit (user_id, action, detail, ip) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_SESSION['user_id'] ?? null, $action, $detail, $_SERVER['REMOTE_ADDR'] ?? null]);
}
