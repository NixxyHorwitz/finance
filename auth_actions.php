<?php
require_once 'db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
        exit;
    }

    if ($action === 'register') {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM User WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit;
        }

        $userId = generateUUID();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO User (id, username, passwordHash) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $username, $hash]);

            // Create default wallets
            $wallets = ['Cash', 'Dana', 'Gopay', 'ShopeePay', 'Savings'];
            $stmtWallet = $pdo->prepare("INSERT INTO Wallet (id, userId, name, balance) VALUES (?, ?, ?, 0)");
            
            foreach ($wallets as $wallet) {
                $stmtWallet->execute([generateUUID(), $userId, $wallet]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
    } 
    elseif ($action === 'login') {
        $stmt = $pdo->prepare("SELECT id, username, passwordHash FROM User WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['passwordHash'])) {
            // Set session for 30 days
            ini_set('session.gc_maxlifetime', 2592000);
            session_set_cookie_params(2592000);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        }
    }
}
?>
