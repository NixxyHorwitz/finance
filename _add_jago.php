<?php
require_once 'db.php';
requireLogin();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];

// Check if Jago wallet already exists
$stmt = $pdo->prepare("SELECT id FROM Wallet WHERE userId = ? AND LOWER(name) LIKE '%jago%'");
$stmt->execute([$userId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Wallet Bank Jago sudah ada.']);
    exit;
}

$id = generateUUID();
$pdo->prepare("INSERT INTO Wallet (id, userId, name, balance) VALUES (?, ?, 'Bank Jago', 0)")
    ->execute([$id, $userId]);

echo json_encode(['success' => true, 'message' => 'Bank Jago berhasil ditambahkan! Silakan hapus file ini.']);
?>
