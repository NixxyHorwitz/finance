<?php
require_once 'db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi.']);
        exit;
    }

    if ($action === 'register') {
        // Check if username taken
        $stmt = $pdo->prepare("SELECT id FROM User WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan.']);
            exit;
        }

        $userId = generateUUID();
        $hash   = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $pdo->prepare("INSERT INTO User (id, username, passwordHash) VALUES (?, ?, ?)")
                ->execute([$userId, $username, $hash]);

            // Default wallets
            $wallets = ['Cash', 'Dana', 'Gopay', 'ShopeePay', 'Savings'];
            $stmtW   = $pdo->prepare("INSERT INTO Wallet (id, userId, name, balance) VALUES (?, ?, ?, 0)");
            foreach ($wallets as $w) {
                $stmtW->execute([generateUUID(), $userId, $w]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Registrasi gagal: ' . $e->getMessage()]);
        }

    } elseif ($action === 'login') {
        $stmt = $pdo->prepare("SELECT id, username, passwordHash FROM User WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['passwordHash'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Issue persistent 30-day remember-me token (custom cookie, not PHP session)
            issueRememberToken($pdo, $user['id']);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
        }
    }
}
?>
