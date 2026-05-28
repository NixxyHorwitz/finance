<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Smart Category Function
function smartCategorize($description, $pdo) {
    $desc = strtolower($description);
    $rules = [
        "Food & Beverage" => ["makan", "minum", "kopi", "cafe", "roti", "snack", "gofood", "grabfood", "shopeefood", "resto", "warteg", "nasi"],
        "Transportation" => ["bensin", "parkir", "tol", "gojek", "grab", "maxim", "kereta", "krl", "bus", "tiket"],
        "Shopping" => ["belanja", "baju", "sepatu", "shopee", "tokopedia", "indomaret", "alfamart", "supermarket"],
        "Entertainment" => ["nonton", "bioskop", "game", "steam", "netflix", "spotify", "main"],
        "Bills" => ["listrik", "air", "internet", "wifi", "pulsa", "kuota", "cicilan", "kos", "sewa"],
        "Income" => ["gaji", "bonus", "thr", "cair", "jual", "untung"]
    ];

    $matchedCategory = "Lainnya";
    foreach ($rules as $cat => $keywords) {
        foreach ($keywords as $kw) {
            if (strpos($desc, $kw) !== false) {
                $matchedCategory = $cat;
                break 2;
            }
        }
    }
    
    // Upsert Category
    $stmt = $pdo->prepare("SELECT id FROM Category WHERE name = ?");
    $stmt->execute([$matchedCategory]);
    $catRow = $stmt->fetch();
    
    if ($catRow) {
        return $catRow['id'];
    } else {
        $newId = generateUUID();
        $stmtIns = $pdo->prepare("INSERT INTO Category (id, name, keywords) VALUES (?, ?, '')");
        $stmtIns->execute([$newId, $matchedCategory]);
        return $newId;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_transaction') {
        $type = $_POST['type'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $description = $_POST['description'] ?? '';
        $walletId = $_POST['walletId'] ?? '';
        $relatedWalletId = $_POST['relatedWalletId'] ?? null;

        if (!$type || $amount <= 0 || !$description || !$walletId) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields or invalid amount']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Verify wallet
            $stmt = $pdo->prepare("SELECT id FROM Wallet WHERE id = ? AND userId = ?");
            $stmt->execute([$walletId, $userId]);
            if (!$stmt->fetch()) throw new Exception("Unauthorized wallet");

            $categoryId = null;
            if ($type !== 'TRANSFER') {
                $categoryId = smartCategorize($description, $pdo);
            }

            // Create Transaction
            $txId = generateUUID();
            $stmtTx = $pdo->prepare("INSERT INTO Transaction (id, userId, walletId, type, amount, description, date, categoryId, relatedWalletId) VALUES (?, ?, ?, ?, ?, ?, NOW(3), ?, ?)");
            $stmtTx->execute([$txId, $userId, $walletId, $type, $amount, $description, $categoryId, $relatedWalletId]);

            // Update Balances
            if ($type === 'INCOME') {
                $pdo->prepare("UPDATE Wallet SET balance = balance + ? WHERE id = ?")->execute([$amount, $walletId]);
            } elseif ($type === 'EXPENSE') {
                $pdo->prepare("UPDATE Wallet SET balance = balance - ? WHERE id = ?")->execute([$amount, $walletId]);
            } elseif ($type === 'TRANSFER') {
                $pdo->prepare("UPDATE Wallet SET balance = balance - ? WHERE id = ?")->execute([$amount, $walletId]);
                $pdo->prepare("UPDATE Wallet SET balance = balance + ? WHERE id = ?")->execute([$amount, $relatedWalletId]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } 
    elseif ($action === 'set_balance') {
        $walletId = $_POST['walletId'] ?? '';
        $balance = (float)($_POST['balance'] ?? 0);

        try {
            $stmt = $pdo->prepare("UPDATE Wallet SET balance = ? WHERE id = ? AND userId = ?");
            $stmt->execute([$balance, $walletId, $userId]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if ($action === 'delete_transaction') {
        parse_str(file_get_contents("php://input"), $delVars);
        $txId = $delVars['id'] ?? '';

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM Transaction WHERE id = ? AND userId = ?");
            $stmt->execute([$txId, $userId]);
            $tx = $stmt->fetch();

            if (!$tx) throw new Exception("Transaction not found");

            // Revert balances
            $amt = (float)$tx['amount'];
            if ($tx['type'] === 'INCOME') {
                $pdo->prepare("UPDATE Wallet SET balance = balance - ? WHERE id = ?")->execute([$amt, $tx['walletId']]);
            } elseif ($tx['type'] === 'EXPENSE') {
                $pdo->prepare("UPDATE Wallet SET balance = balance + ? WHERE id = ?")->execute([$amt, $tx['walletId']]);
            } elseif ($tx['type'] === 'TRANSFER') {
                $pdo->prepare("UPDATE Wallet SET balance = balance + ? WHERE id = ?")->execute([$amt, $tx['walletId']]);
                $pdo->prepare("UPDATE Wallet SET balance = balance - ? WHERE id = ?")->execute([$amt, $tx['relatedWalletId']]);
            }

            $pdo->prepare("DELETE FROM Transaction WHERE id = ?")->execute([$txId]);
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>
