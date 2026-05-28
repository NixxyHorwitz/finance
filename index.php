<?php
require_once 'db.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Fetch Wallets
$stmtW = $pdo->prepare("SELECT * FROM Wallet WHERE userId = ?");
$stmtW->execute([$userId]);
$wallets = $stmtW->fetchAll();

$totalBalance = 0;
foreach ($wallets as $w) $totalBalance += $w['balance'];

// Fetch Transactions
$stmtT = $pdo->prepare("
    SELECT t.*, w.name as walletName, rw.name as relatedWalletName, c.name as categoryName 
    FROM Transaction t 
    LEFT JOIN Wallet w ON t.walletId = w.id 
    LEFT JOIN Wallet rw ON t.relatedWalletId = rw.id 
    LEFT JOIN Category c ON t.categoryId = c.id
    WHERE t.userId = ? 
    ORDER BY t.date DESC
");
$stmtT->execute([$userId]);
$transactions = $stmtT->fetchAll();

// Prepare Chart Data (Daily Income/Expense)
$chartDataRaw = [];
foreach ($transactions as $t) {
    if ($t['type'] === 'TRANSFER') continue;
    $date = date('d M', strtotime($t['date']));
    if (!isset($chartDataRaw[$date])) {
        $chartDataRaw[$date] = ['Income' => 0, 'Expense' => 0];
    }
    if ($t['type'] === 'INCOME') $chartDataRaw[$date]['Income'] += $t['amount'];
    if ($t['type'] === 'EXPENSE') $chartDataRaw[$date]['Expense'] += $t['amount'];
}
$chartLabels = array_reverse(array_keys($chartDataRaw));
$chartIncome = [];
$chartExpense = [];
foreach ($chartLabels as $label) {
    $chartIncome[] = $chartDataRaw[$label]['Income'];
    $chartExpense[] = $chartDataRaw[$label]['Expense'];
}

function formatRupiah($num) {
    return "Rp " . number_format($num, 0, ',', '.');
}

function getLogo($name) {
    $n = strtolower($name);
    if (strpos($n, 'dana') !== false) return '<img src="public/dana.png" class="logo-icon">';
    if (strpos($n, 'gopay') !== false) return '<img src="public/gopay.png" class="logo-icon">';
    if (strpos($n, 'shopee') !== false) return '<img src="public/shopeepay.png" class="logo-icon">';
    if (strpos($n, 'saving') !== false) return '<div class="logo-icon flex justify-center items-center" style="background-color: var(--tertiary); font-size: 20px;">🐷</div>';
    return '<div class="logo-icon flex justify-center items-center" style="background-color: var(--primary); font-size: 20px;">💵</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Neobrutalism Finance</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl text-bold" style="text-transform: uppercase;">Neofinance</h1>
                <p class="text-bold" style="color: #555;">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></p>
            </div>
            <a href="logout.php" class="neo-btn neo-btn-white flex items-center gap-2" style="padding: 0.5rem 1rem;">
                Logout
            </a>
        </div>

        <!-- Total Balance -->
        <div class="neo-box mb-6 flex justify-between items-center" style="background-color: var(--primary);">
            <div>
                <p class="text-lg text-bold mb-2">Total Balance</p>
                <h2 class="text-3xl text-bold" id="totalBalanceText" data-val="<?= formatRupiah($totalBalance) ?>">
                    <?= formatRupiah($totalBalance) ?>
                </h2>
            </div>
            <button onclick="toggleBalance()" class="neo-btn neo-btn-white" style="padding: 0.5rem; border-radius: 50%;">
                👁️
            </button>
        </div>

        <!-- Wallets Grid -->
        <h2 class="text-2xl text-bold mb-4">My Wallets</h2>
        <div class="wallet-grid mb-8">
            <?php foreach ($wallets as $w): ?>
                <div class="neo-box flex-col justify-between wallet-card">
                    <div class="flex justify-between items-center mb-4">
                        <?= getLogo($w['name']) ?>
                        <span class="text-bold text-lg"><?= htmlspecialchars($w['name']) ?></span>
                    </div>
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-sm" style="font-weight: 600;">Balance</p>
                            <p class="text-xl text-bold wallet-bal" data-val="<?= formatRupiah($w['balance']) ?>">
                                <?= formatRupiah($w['balance']) ?>
                            </p>
                        </div>
                        <button onclick="openBalanceModal('<?= $w['id'] ?>', '<?= addslashes($w['name']) ?>', <?= $w['balance'] ?>)" class="neo-btn neo-btn-white" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                            ✏️
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Bar -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl text-bold">Transactions & Analytics</h2>
            <button onclick="document.getElementById('txModal').classList.add('active')" class="neo-btn neo-btn-tertiary flex items-center gap-2">
                ➕ Add Record
            </button>
        </div>

        <!-- Chart -->
        <div class="neo-box mb-8" style="height: 350px;">
            <canvas id="financeChart"></canvas>
        </div>

        <!-- History -->
        <div class="neo-box">
            <h3 class="text-xl text-bold mb-4">Recent History</h3>
            <?php if(empty($transactions)): ?>
                <p class="text-center text-bold" style="color: #555;">No transactions found.</p>
            <?php else: ?>
                <div class="flex-col gap-4 flex">
                    <?php foreach ($transactions as $t): ?>
                        <div class="flex justify-between items-center" style="border-bottom: 2px solid #000; padding-bottom: 0.5rem;">
                            <div>
                                <p class="text-bold"><?= htmlspecialchars($t['description']) ?></p>
                                <p class="text-sm" style="font-weight: 600; color: #555;">
                                    <?= date('d M Y', strtotime($t['date'])) ?> • 
                                    <?php if($t['type'] === 'TRANSFER'): ?>
                                        <?= $t['walletName'] ?> ➔ <?= $t['relatedWalletName'] ?>
                                    <?php else: ?>
                                        <?= $t['walletName'] ?>
                                    <?php endif; ?>
                                </p>
                                <?php if($t['categoryId'] && $t['type'] !== 'TRANSFER'): ?>
                                    <span class="badge mt-1"><?= htmlspecialchars($t['categoryName']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-4">
                                <?php 
                                    $c = 'text-bold'; $sign = '';
                                    if($t['type'] === 'INCOME') { $c = 'amount-income'; $sign = '+'; }
                                    elseif($t['type'] === 'EXPENSE') { $c = 'amount-expense'; $sign = '-'; }
                                ?>
                                <span class="text-xl <?= $c ?>"><?= $sign . formatRupiah($t['amount']) ?></span>
                                <button onclick="deleteTx('<?= $t['id'] ?>')" class="neo-btn neo-btn-secondary" style="padding: 0.25rem;" title="Delete">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Transaction Modal -->
    <div id="txModal" class="neo-overlay">
        <div class="neo-modal">
            <button onclick="document.getElementById('txModal').classList.remove('active')" class="neo-btn neo-btn-white close-btn">❌</button>
            <h2 class="text-2xl text-bold mb-4">Add Transaction</h2>
            <form id="txForm" class="flex-col gap-4 flex">
                <div class="flex gap-2">
                    <label style="flex:1"><input type="radio" name="type" value="EXPENSE" checked onchange="txTypeChange()"> Expense</label>
                    <label style="flex:1"><input type="radio" name="type" value="INCOME" onchange="txTypeChange()"> Income</label>
                    <label style="flex:1"><input type="radio" name="type" value="TRANSFER" onchange="txTypeChange()"> Transfer</label>
                </div>
                <div>
                    <label class="text-bold mb-2" style="display: block;">From Wallet</label>
                    <select name="walletId" class="neo-select" required>
                        <?php foreach($wallets as $w) echo "<option value='{$w['id']}'>{$w['name']}</option>"; ?>
                    </select>
                </div>
                <div id="toWalletDiv" style="display: none;">
                    <label class="text-bold mb-2" style="display: block;">To Wallet</label>
                    <select name="relatedWalletId" class="neo-select">
                        <?php foreach($wallets as $w) echo "<option value='{$w['id']}'>{$w['name']}</option>"; ?>
                    </select>
                </div>
                <div>
                    <label class="text-bold mb-2" style="display: block;">Amount</label>
                    <input type="number" name="amount" class="neo-input" min="1" required>
                </div>
                <div>
                    <label class="text-bold mb-2" style="display: block;">Description (Smart Category)</label>
                    <input type="text" name="description" class="neo-input" required>
                </div>
                <button type="submit" class="neo-btn mt-4">SAVE TRANSACTION</button>
            </form>
        </div>
    </div>

    <!-- Set Balance Modal -->
    <div id="balModal" class="neo-overlay">
        <div class="neo-modal">
            <button onclick="document.getElementById('balModal').classList.remove('active')" class="neo-btn neo-btn-white close-btn">❌</button>
            <h2 class="text-2xl text-bold mb-4">Set Balance</h2>
            <p id="balWalletName" class="mb-4 text-bold" style="color: var(--primary);"></p>
            <form id="balForm" class="flex-col gap-4 flex">
                <input type="hidden" name="walletId" id="balWalletId">
                <div>
                    <label class="text-bold mb-2" style="display: block;">New Balance</label>
                    <input type="number" name="balance" id="balAmount" class="neo-input" min="0" required>
                </div>
                <button type="submit" class="neo-btn mt-4">SAVE BALANCE</button>
            </form>
        </div>
    </div>

    <script>
        // Chart.js init
        const ctx = document.getElementById('financeChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    { label: 'Income', data: <?= json_encode($chartIncome) ?>, backgroundColor: 'var(--tertiary)', borderColor: '#000', borderWidth: 2 },
                    { label: 'Expense', data: <?= json_encode($chartExpense) ?>, backgroundColor: 'var(--secondary)', borderColor: '#000', borderWidth: 2 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { font: { family: 'Outfit', weight: 'bold' }, color: '#000' } },
                    y: { ticks: { font: { family: 'Outfit', weight: 'bold' }, color: '#000' } }
                },
                plugins: { legend: { labels: { font: { family: 'Outfit', weight: 'bold' }, color: '#000' } } }
            }
        });

        // Toggle Balance
        let balVisible = true;
        function toggleBalance() {
            balVisible = !balVisible;
            document.getElementById('totalBalanceText').innerText = balVisible ? document.getElementById('totalBalanceText').getAttribute('data-val') : 'Rp •••••••••';
            document.querySelectorAll('.wallet-bal').forEach(el => {
                el.innerText = balVisible ? el.getAttribute('data-val') : 'Rp •••••';
            });
        }

        // Modals Logic
        function txTypeChange() {
            const type = document.querySelector('input[name="type"]:checked').value;
            document.getElementById('toWalletDiv').style.display = (type === 'TRANSFER') ? 'block' : 'none';
            document.querySelector('select[name="relatedWalletId"]').required = (type === 'TRANSFER');
        }

        function openBalanceModal(id, name, bal) {
            document.getElementById('balWalletId').value = id;
            document.getElementById('balWalletName').innerText = "Wallet: " + name;
            document.getElementById('balAmount').value = bal;
            document.getElementById('balModal').classList.add('active');
        }

        // AJAX Submissions
        document.getElementById('txForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const res = await fetch('transaction_actions.php?action=add_transaction', { method:'POST', body: fd });
            const data = await res.json();
            if(data.success) location.reload();
            else alert(data.message);
        });

        document.getElementById('balForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const res = await fetch('transaction_actions.php?action=set_balance', { method:'POST', body: fd });
            const data = await res.json();
            if(data.success) location.reload();
            else alert(data.message);
        });

        async function deleteTx(id) {
            if(!confirm("Are you sure? Balance will be adjusted.")) return;
            const res = await fetch('transaction_actions.php?action=delete_transaction', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            });
            const data = await res.json();
            if(data.success) location.reload();
            else alert(data.message);
        }
    </script>
</body>
</html>
