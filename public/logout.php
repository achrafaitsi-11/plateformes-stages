<?php
require __DIR__ . '/../includes/bootstrap.php';

// POST uniquement + CSRF : un simple lien ne doit pas pouvoir déconnecter quelqu'un.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}
csrf_check();
logout();
session_start();
flash('success', 'Vous êtes déconnecté.');
redirect('/login.php');
