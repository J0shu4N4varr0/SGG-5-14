<?php
// Funciones compartidas por las páginas públicas.

/** Escapa texto para mostrarlo de forma segura en HTML. */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Token anti-CSRF ligado a la sesión (requiere session_start()). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Comprueba que el POST actual traiga el token de la sesión. */
function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/** Da formato de dinero: 1234.5 -> $1.234,50 */
function dinero($monto): string
{
    return '$' . number_format((float) $monto, 2, ',', '.');
}
