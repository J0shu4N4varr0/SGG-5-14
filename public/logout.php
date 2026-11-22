<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

// Solo se cierra la sesión con un POST válido (evita que otro sitio te desconecte con un enlace).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

header('Location: index.php');
exit;
