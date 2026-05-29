<?php
// ─────────────────────────────────────────────────
//  SESSION CONFIG — must be set before session_start()
// ─────────────────────────────────────────────────
$sessionLifetime = 60 * 60 * 24 * 30; // 30 hari
ini_set('session.gc_maxlifetime',  $sessionLifetime);
ini_set('session.cookie_lifetime', $sessionLifetime);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ─────────────────────────────────────────────────
//  DATABASE — remote host
// ─────────────────────────────────────────────────
$host    = 'tontonkuy.my.id';
$db      = 'tontonku_finance';
$user    = 'tontonku_finance';
$pass    = 'tontonku_finance';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// ─────────────────────────────────────────────────
//  REMEMBER-ME: auto-restore session from cookie
//  Works independently of PHP session lifetime.
// ─────────────────────────────────────────────────
const REMEMBER_COOKIE   = 'nf_auth';
const REMEMBER_DAYS     = 30;
const REMEMBER_LIFETIME = 60 * 60 * 24 * REMEMBER_DAYS;

/**
 * Restore session from a persistent remember-me cookie.
 * Called on every page load when no active session exists.
 */
function restoreSessionFromCookie(PDO $pdo): bool {
    if (!isset($_COOKIE[REMEMBER_COOKIE])) return false;

    $raw = $_COOKIE[REMEMBER_COOKIE];
    // Cookie format: "id:token"
    $parts = explode(':', $raw, 2);
    if (count($parts) !== 2) { clearRememberCookie(); return false; }

    [$tokenId, $tokenSecret] = $parts;

    $stmt = $pdo->prepare(
        "SELECT rt.token, rt.userId, rt.expires_at, u.username
         FROM RememberToken rt
         JOIN User u ON u.id = rt.userId
         WHERE rt.id = ?"
    );
    $stmt->execute([$tokenId]);
    $row = $stmt->fetch();

    if (!$row) { clearRememberCookie(); return false; }

    // Check expiry
    if (strtotime($row['expires_at']) < time()) {
        $pdo->prepare("DELETE FROM RememberToken WHERE id = ?")->execute([$tokenId]);
        clearRememberCookie();
        return false;
    }

    // Constant-time token compare
    if (!hash_equals($row['token'], hash('sha256', $tokenSecret))) {
        clearRememberCookie();
        return false;
    }

    // Valid — restore session
    session_regenerate_id(true);
    $_SESSION['user_id']  = $row['userId'];
    $_SESSION['username'] = $row['username'];

    // Rotate token for security (sliding window)
    $newSecret = bin2hex(random_bytes(32));
    $newHash   = hash('sha256', $newSecret);
    $newExpiry = date('Y-m-d H:i:s', time() + REMEMBER_LIFETIME);

    $pdo->prepare(
        "UPDATE RememberToken SET token = ?, expires_at = ? WHERE id = ?"
    )->execute([$newHash, $newExpiry, $tokenId]);

    // Re-issue cookie with rotated secret
    setRememberCookie($tokenId, $newSecret, REMEMBER_LIFETIME);

    return true;
}

/**
 * Issue a new persistent remember-me token for a user.
 * Call this after a successful login.
 */
function issueRememberToken(PDO $pdo, string $userId): void {
    // Delete old tokens for this user (keep only latest)
    $pdo->prepare("DELETE FROM RememberToken WHERE userId = ?")->execute([$userId]);

    $tokenId     = generateUUID();
    $tokenSecret = bin2hex(random_bytes(32)); // 64 hex chars
    $tokenHash   = hash('sha256', $tokenSecret);
    $expires     = date('Y-m-d H:i:s', time() + REMEMBER_LIFETIME);

    $pdo->prepare(
        "INSERT INTO RememberToken (id, userId, token, expires_at) VALUES (?, ?, ?, ?)"
    )->execute([$tokenId, $userId, $tokenHash, $expires]);

    setRememberCookie($tokenId, $tokenSecret, REMEMBER_LIFETIME);
}

/**
 * Revoke the remember-me cookie and delete from DB.
 * Call on logout.
 */
function revokeRememberToken(PDO $pdo): void {
    if (!isset($_COOKIE[REMEMBER_COOKIE])) return;
    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
    if (count($parts) === 2) {
        $pdo->prepare("DELETE FROM RememberToken WHERE id = ?")->execute([$parts[0]]);
    }
    clearRememberCookie();
}

function setRememberCookie(string $id, string $secret, int $lifetime): void {
    $value = $id . ':' . $secret;
    setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => time() + $lifetime,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clearRememberCookie(): void {
    setcookie(REMEMBER_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// ─────────────────────────────────────────────────
//  AUTO-RESTORE if no active session
// ─────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    restoreSessionFromCookie($pdo);
}

// ─────────────────────────────────────────────────
//  HELPERS
// ─────────────────────────────────────────────────
function generateUUID(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
?>
