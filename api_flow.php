<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$days   = (int)($_GET['days'] ?? 7);
$days   = max(1, min(90, $days)); // clamp 1–90

$since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

// Wallets
$sw = $pdo->prepare("SELECT id, name, balance FROM Wallet WHERE userId = ?");
$sw->execute([$userId]);
$wallets = $sw->fetchAll();
foreach ($wallets as &$w) $w['balance'] = (float)$w['balance'];

// All expenses + incomes in period (no LIMIT)
$st = $pdo->prepare("
    SELECT t.id, t.type, t.amount, t.description, t.date,
           t.walletId, t.relatedWalletId,
           w.name  AS walletName,
           rw.name AS relatedWalletName,
           c.name  AS categoryName
    FROM `Transaction` t
    LEFT JOIN Wallet   w  ON t.walletId = w.id
    LEFT JOIN Wallet   rw ON t.relatedWalletId = rw.id
    LEFT JOIN Category c  ON t.categoryId = c.id
    WHERE t.userId = ? AND t.date >= ?
    ORDER BY t.date DESC
");
$st->execute([$userId, $since]);
$transactions = $st->fetchAll();
foreach ($transactions as &$t) $t['amount'] = (float)$t['amount'];

echo json_encode([
    'success'      => true,
    'wallets'      => $wallets,
    'transactions' => $transactions,
    'period_days'  => $days,
    'since'        => $since,
]);
?>
