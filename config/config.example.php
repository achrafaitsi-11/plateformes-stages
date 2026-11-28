<?php
/**
 * Copier ce fichier en config/config.php (ignoré par git) et adapter les valeurs.
 * Ne jamais mettre de mot de passe réel ni de clé dans un fichier versionné.
 */

// 'dev' affiche les erreurs, 'prod' les écrit dans un log.
const APP_ENV = 'dev';

// Base de données (XAMPP : utilisateur root sans mot de passe par défaut).
const DB_HOST = '127.0.0.1';
const DB_NAME = 'stages';
const DB_USER = 'root';
const DB_PASS = '';

// URL de la racine web (dossier public/), sans slash final.
// Détectée automatiquement à partir de l'emplacement du projet dans htdocs :
// plus besoin de la modifier, quel que soit le nom du dossier.
$docRoot = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$publicDir = str_replace('\\', '/', (string)realpath(__DIR__ . '/../public'));
define('BASE_URL', str_starts_with($publicDir, $docRoot) ? substr($publicDir, strlen($docRoot)) : '/plateformes-stages/public');
unset($docRoot, $publicDir);

// Fichiers envoyés : toujours hors de la racine web.
define('STORAGE_PATH', dirname(__DIR__) . '/storage');

// Emails : 'simulation' écrit le message dans storage/mails/ et l'affiche à l'écran,
// 'smtp' utilise PHPMailer avec les réglages ci-dessous.
const MAIL_MODE = 'simulation';
const SMTP_HOST = '';
const SMTP_PORT = 587;
const SMTP_USER = '';
const SMTP_PASS = '';
const MAIL_FROM = 'no-reply@plateforme-stages.local';

// En production, fixer l'URL publique du site pour les liens envoyés par email :
// const APP_URL = 'https://stages.exemple.ma';

// Limites d'envoi (en octets).
const MAX_CV_BYTES = 2 * 1024 * 1024;
const MAX_JUSTIFICATIF_BYTES = 3 * 1024 * 1024;
const MAX_LOGO_BYTES = 1 * 1024 * 1024;
