<?php
require_once __DIR__.'/../../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// Wallets
$s = $pdo->prepare("SELECT * FROM Wallet WHERE userId = ? ORDER BY FIELD(name,'Cash','Dana','Gopay','ShopeePay','Savings')");
$s->execute([$userId]);
$wallets = $s->fetchAll();

foreach ($wallets as &$w) {
    $w['balance'] = (float)$w['balance'];
}

// Transactions (last 50)
$s2 = $pdo->prepare("
    SELECT t.id, t.type, t.amount, t.description, t.date, t.walletId, t.relatedWalletId,
           w.name as walletName, rw.name as relatedWalletName, c.name as categoryName
    FROM `Transaction` t
    LEFT JOIN Wallet w ON t.walletId = w.id
    LEFT JOIN Wallet rw ON t.relatedWalletId = rw.id
    LEFT JOIN Category c ON t.categoryId = c.id
    WHERE t.userId = ?
    ORDER BY t.date DESC
    LIMIT 50
");
$s2->execute([$userId]);
$transactions = $s2->fetchAll();

foreach ($transactions as &$t) {
    $t['amount'] = (float)$t['amount'];
}

echo json_encode([
    'success' => true,
    'wallets' => $wallets,
    'transactions' => $transactions
]);
?>
