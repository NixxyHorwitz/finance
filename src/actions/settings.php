<?php
require_once __DIR__.'/../../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Helper: upsert user setting
function upsertSetting(PDO $pdo, string $userId, string $key, string $value): void {
    $pdo->prepare("INSERT INTO UserSetting (id, userId, `key`, value) VALUES (UUID(), ?, ?, ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)")
        ->execute([$userId, $key, $value]);
}

function getSetting(PDO $pdo, string $userId, string $key): ?string {
    $s = $pdo->prepare("SELECT value FROM UserSetting WHERE userId = ? AND `key` = ?");
    $s->execute([$userId, $key]);
    $r = $s->fetch();
    return $r ? $r['value'] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'get_setting') {
        $key = $_GET['key'] ?? '';
        echo json_encode(['success' => true, 'value' => getSetting($pdo, $userId, $key)]);
        exit;
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'change_username') {
        $newUsername = trim($_POST['username'] ?? '');
        if (strlen($newUsername) < 3) {
            echo json_encode(['success' => false, 'message' => 'Username minimal 3 karakter.']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id FROM User WHERE username = ? AND id != ?");
        $stmt->execute([$newUsername, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan.']);
            exit;
        }
        $pdo->prepare("UPDATE User SET username = ? WHERE id = ?")->execute([$newUsername, $userId]);
        $_SESSION['username'] = $newUsername;
        echo json_encode(['success' => true, 'message' => 'Username berhasil diubah.']);

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        if (strlen($new) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter.']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT passwordHash FROM User WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($current, $user['passwordHash'])) {
            echo json_encode(['success' => false, 'message' => 'Password saat ini tidak sesuai.']);
            exit;
        }
        $pdo->prepare("UPDATE User SET passwordHash = ? WHERE id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah.']);

    } elseif ($action === 'update_wallet') {
        $walletId = $_POST['walletId'] ?? '';
        $name     = trim($_POST['name'] ?? '');
        if (strlen($name) < 1) {
            echo json_encode(['success' => false, 'message' => 'Nama tidak boleh kosong.']);
            exit;
        }
        $pdo->prepare("UPDATE Wallet SET name = ? WHERE id = ? AND userId = ?")
            ->execute([$name, $walletId, $userId]);
        echo json_encode(['success' => true, 'message' => 'Nama wallet diperbarui.']);

    } elseif ($action === 'save_setting') {
        $key   = trim($_POST['key']   ?? '');
        $value = trim($_POST['value'] ?? '');
        if (!$key) {
            echo json_encode(['success' => false, 'message' => 'Key tidak boleh kosong.']);
            exit;
        }
        upsertSetting($pdo, $userId, $key, $value);
        echo json_encode(['success' => true, 'message' => 'Pengaturan disimpan.']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenal.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>
