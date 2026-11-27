<?php
/** Vérifie l'installation locale : http://localhost/plateforme-stages/public/healthcheck.php */
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

$checks = [];
try {
    $n = (int)getPdo()->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
    $checks['Base de données'] = $n > 0 ? "OK ($n tables)" : 'Connectée mais vide : importer sql/schema.sql';
} catch (Throwable $ex) {
    $checks['Base de données'] = 'ÉCHEC : ' . $ex->getMessage();
}
foreach (['cv', 'lettres', 'justificatifs', 'rapports', 'logos', 'photos', 'mails'] as $d) {
    $checks["storage/$d inscriptible"] = is_writable(STORAGE_PATH . "/$d") ? 'OK' : 'ÉCHEC';
}
$checks['PHP >= 8.1'] = PHP_VERSION_ID >= 80100 ? 'OK (' . PHP_VERSION . ')' : 'ÉCHEC (' . PHP_VERSION . ')';
$checks['BASE_URL détectée'] = BASE_URL;
$checks['Extension fileinfo'] = extension_loaded('fileinfo') ? 'OK' : 'ÉCHEC';

foreach ($checks as $k => $v) {
    echo str_pad($k, 32) . $v . "\n";
}
