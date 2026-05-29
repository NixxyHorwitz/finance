<?php
require_once 'db.php';

// Revoke persistent token from DB + clear cookie
revokeRememberToken($pdo);

// Destroy PHP session too
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => 'Lax',
    ]);
}
session_destroy();

header('Location: login.php');
exit;
?>
