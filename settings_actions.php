<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'change_username') {
        $newUsername = trim($_POST['username'] ?? '');
        if (strlen($newUsername) < 3) {
            echo json_encode(['success' => false, 'message' => 'Username minimal 3 karakter.']);
            exit;
        }
        // Check if taken
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
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE User SET passwordHash = ? WHERE id = ?")->execute([$hash, $userId]);
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah.']);

    } elseif ($action === 'update_wallet') {
        $walletId = $_POST['walletId'] ?? '';
        $name     = trim($_POST['name'] ?? '');
        if (strlen($name) < 1) {
            echo json_encode(['success' => false, 'message' => 'Nama tidak boleh kosong.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE Wallet SET name = ? WHERE id = ? AND userId = ?");
        $stmt->execute([$name, $walletId, $userId]);
        echo json_encode(['success' => true, 'message' => 'Nama wallet diperbarui.']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenal.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>
