<?php
require_once 'db.php';

try {
    // 1. Add sort_order column if not exists
    $pdo->exec("ALTER TABLE Wallet ADD COLUMN sort_order INT NOT NULL DEFAULT 0");
    echo "Column sort_order added successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column sort_order already exists.<br>";
    } else {
        echo "Error adding column: " . $e->getMessage() . "<br>";
    }
}

// 2. Assign default order based on existing logic or creation order
$users = $pdo->query("SELECT id FROM User")->fetchAll();
foreach ($users as $u) {
    $userId = $u['id'];
    // Get wallets for user
    $wallets = $pdo->prepare("SELECT id, name FROM Wallet WHERE userId = ? ORDER BY id ASC");
    $wallets->execute([$userId]);
    $userWallets = $wallets->fetchAll();
    
    // Sort them initially in a sensible way (similar to old FIELD logic if possible, or just sequential)
    $preferredOrder = ['Cash', 'Dana', 'Gopay', 'ShopeePay', 'Bank Jago', 'Savings'];
    usort($userWallets, function($a, $b) use ($preferredOrder) {
        $posA = array_search($a['name'], $preferredOrder);
        $posB = array_search($b['name'], $preferredOrder);
        if ($posA === false) $posA = 999;
        if ($posB === false) $posB = 999;
        if ($posA === $posB) return $a['id'] <=> $b['id'];
        return $posA <=> $posB;
    });

    foreach ($userWallets as $index => $w) {
        $stmt = $pdo->prepare("UPDATE Wallet SET sort_order = ? WHERE id = ?");
        $stmt->execute([$index + 1, $w['id']]);
    }
}

echo "Wallets reordered successfully.";
