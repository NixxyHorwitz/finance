<?php
require_once 'db.php';
requireLogin();

$userId = $_SESSION['user_id'];

$stmtW = $pdo->prepare("SELECT * FROM Wallet WHERE userId = ? ORDER BY FIELD(name,'Cash','Dana','Gopay','ShopeePay','Savings')");
$stmtW->execute([$userId]);
$wallets = $stmtW->fetchAll();

$totalBalance = array_sum(array_column($wallets, 'balance'));

$stmtT = $pdo->prepare("
    SELECT t.*, w.name as walletName, rw.name as relatedWalletName, c.name as categoryName 
    FROM `Transaction` t 
    LEFT JOIN Wallet w ON t.walletId = w.id 
    LEFT JOIN Wallet rw ON t.relatedWalletId = rw.id 
    LEFT JOIN Category c ON t.categoryId = c.id
    WHERE t.userId = ? 
    ORDER BY t.date DESC
    LIMIT 50
");
$stmtT->execute([$userId]);
$transactions = $stmtT->fetchAll();

// Chart data
$chartData = [];
foreach ($transactions as $t) {
    if ($t['type'] === 'TRANSFER') continue;
    $d = date('d/m', strtotime($t['date']));
    if (!isset($chartData[$d])) $chartData[$d] = ['Income'=>0,'Expense'=>0];
    if ($t['type'] === 'INCOME') $chartData[$d]['Income'] += $t['amount'];
    if ($t['type'] === 'EXPENSE') $chartData[$d]['Expense'] += $t['amount'];
}
$chartData = array_reverse($chartData, true);
$chartLabels = array_keys($chartData);
$chartIncome = array_column(array_values($chartData), 'Income');
$chartExpense = array_column(array_values($chartData), 'Expense');

function formatRp($n) { return 'Rp '.number_format($n,0,',','.'); }
function shortRp($n) {
    if ($n >= 1000000) return 'Rp '.number_format($n/1000000,1,',','.').'jt';
    if ($n >= 1000) return 'Rp '.number_format($n/1000,0,',','.').'rb';
    return 'Rp '.$n;
}

function walletIcon($name) {
    $n = strtolower($name);
    if (strpos($n,'dana')!==false)    return '<img src="public/dana.png" class="wallet-icon" alt="Dana">';
    if (strpos($n,'gopay')!==false)   return '<img src="public/gopay.png" class="wallet-icon" alt="Gopay">';
    if (strpos($n,'shopee')!==false)  return '<img src="public/shopeepay.png" class="wallet-icon" alt="ShopeePay">';
    if (strpos($n,'saving')!==false)  return '<div class="wallet-icon-emoji" style="background:#dcfce7;">🐷</div>';
    return '<div class="wallet-icon-emoji" style="background:#fef9c3;">💵</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – Neofinance</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body>
  <div class="container">

    <!-- Header -->
    <div class="header">
      <div>
        <div class="header-brand">Neofinance</div>
        <div class="header-sub">Halo, <?= htmlspecialchars($_SESSION['username']) ?> 👋</div>
      </div>
      <a href="logout.php" class="btn btn-ghost btn-sm">Keluar</a>
    </div>

    <!-- Balance -->
    <div class="balance-hero">
      <div>
        <div class="label">Total Saldo</div>
        <div class="amount" id="totalAmt" data-val="<?= formatRp($totalBalance) ?>"><?= formatRp($totalBalance) ?></div>
      </div>
      <button onclick="toggleBal()" class="btn btn-ghost btn-icon" id="eyeBtn" title="Sembunyikan">👁</button>
    </div>

    <!-- Wallets -->
    <div class="section-head">
      <span class="section-title">Dompet</span>
    </div>
    <div class="wallet-grid">
      <?php foreach ($wallets as $w): ?>
      <div class="wallet-card">
        <div class="wallet-edit" onclick="openBal('<?= $w['id'] ?>','<?= addslashes($w['name']) ?>',<?= $w['balance'] ?>)">✏️</div>
        <?= walletIcon($w['name']) ?>
        <div class="wallet-name"><?= htmlspecialchars($w['name']) ?></div>
        <div class="wallet-amount bal-item" data-val="<?= shortRp($w['balance']) ?>"><?= shortRp($w['balance']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Transactions & Chart -->
    <div class="section-head">
      <span class="section-title">Transaksi</span>
      <button onclick="openTx()" class="btn btn-sm">+ Tambah</button>
    </div>

    <!-- Chart -->
    <div class="chart-wrap">
      <canvas id="chart"></canvas>
    </div>

    <!-- History -->
    <div class="card-flat mb-3">
      <?php if (empty($transactions)): ?>
        <div class="empty">Belum ada transaksi.<br>Yuk tambahkan catatan pertamamu!</div>
      <?php else: ?>
        <div class="tx-list">
          <?php foreach ($transactions as $t):
            $sign = $t['type']==='INCOME' ? '+' : ($t['type']==='EXPENSE' ? '-' : '');
            $cls  = $t['type']==='INCOME' ? 'income' : ($t['type']==='EXPENSE' ? 'expense' : '');
            $meta = date('d M', strtotime($t['date'])) . ' · ' . $t['walletName'];
            if ($t['type']==='TRANSFER') $meta .= ' → '.$t['relatedWalletName'];
          ?>
          <div class="tx-item">
            <div class="tx-left">
              <div class="tx-desc"><?= htmlspecialchars($t['description']) ?></div>
              <div class="tx-meta"><?= $meta ?></div>
              <?php if ($t['categoryName'] && $t['type']!=='TRANSFER'): ?>
                <div class="tx-badge"><?= htmlspecialchars($t['categoryName']) ?></div>
              <?php endif; ?>
            </div>
            <div class="tx-right">
              <span class="tx-amount <?= $cls ?>"><?= $sign.shortRp($t['amount']) ?></span>
              <button onclick="delTx('<?= $t['id'] ?>')" class="btn btn-ghost btn-icon btn-sm" style="background:var(--bg);font-size:12px;">🗑</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /container -->

  <!-- ═══ Modal: Add Transaction ═══ -->
  <div id="txOverlay" class="overlay" onclick="closeTx(event)">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Tambah Transaksi</span>
        <span class="modal-close" onclick="closeTxModal()">✕</span>
      </div>

      <div class="tab-switcher mb-2">
        <button class="tab-btn active" onclick="setType('EXPENSE',this)">Keluar</button>
        <button class="tab-btn" onclick="setType('INCOME',this)">Masuk</button>
        <button class="tab-btn" onclick="setType('TRANSFER',this)">Transfer</button>
      </div>

      <div id="txAlert" class="alert alert-danger"></div>

      <form id="txForm">
        <input type="hidden" name="type" id="txType" value="EXPENSE">

        <div class="form-group">
          <label>Dari Dompet</label>
          <select name="walletId" required>
            <?php foreach($wallets as $w): ?>
              <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" id="toWalletGroup" style="display:none">
          <label>Ke Dompet</label>
          <select name="relatedWalletId">
            <?php foreach($wallets as $w): ?>
              <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Jumlah (Rp)</label>
          <input type="number" name="amount" placeholder="0" min="1" required>
        </div>

        <div class="form-group">
          <label>Keterangan</label>
          <input type="text" name="description" placeholder="contoh: makan siang, gofood…" required>
          <span style="font-size:0.72rem;color:var(--muted);margin-top:2px;">Kategori otomatis dari kata kunci 🪄</span>
        </div>

        <button type="submit" class="btn btn-full">Simpan</button>
      </form>
    </div>
  </div>

  <!-- ═══ Modal: Set Balance ═══ -->
  <div id="balOverlay" class="overlay" onclick="closeBal(event)">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Atur Saldo — <span id="balName"></span></span>
        <span class="modal-close" onclick="closeBalModal()">✕</span>
      </div>

      <div id="balAlert" class="alert alert-danger"></div>

      <form id="balForm">
        <input type="hidden" name="walletId" id="balWalletId">
        <div class="form-group">
          <label>Saldo Baru (Rp)</label>
          <input type="number" name="balance" id="balAmount" placeholder="0" min="0" required>
        </div>
        <button type="submit" class="btn btn-full">Simpan</button>
      </form>
    </div>
  </div>

  <script>
    // ─── Chart ────────────────────────────────────────
    const ctx = document.getElementById('chart');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
          { label: 'Pemasukan', data: <?= json_encode($chartIncome) ?>, backgroundColor: '#2dc974', borderRadius: 4 },
          { label: 'Pengeluaran', data: <?= json_encode($chartExpense) ?>, backgroundColor: '#ff5c5c', borderRadius: 4 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { labels: { font: { family:'Inter', size:11, weight:'700' }, color:'#111', boxWidth:12, padding:12 } }
        },
        scales: {
          x: { grid: { display:false }, ticks: { font:{family:'Inter',size:10}, color:'#888' }, border:{display:false} },
          y: { grid: { color:'#f0f0f0' }, ticks: { font:{family:'Inter',size:10}, color:'#888', callback: v => v>=1e6?v/1e6+'jt':v>=1e3?v/1e3+'rb':v }, border:{display:false} }
        }
      }
    });

    // ─── Toggle Balance ───────────────────────────────
    let visible = true;
    function toggleBal() {
      visible = !visible;
      document.getElementById('totalAmt').textContent = visible
        ? document.getElementById('totalAmt').dataset.val : 'Rp ••••••';
      document.querySelectorAll('.bal-item').forEach(el =>
        el.textContent = visible ? el.dataset.val : '••••'
      );
      document.getElementById('eyeBtn').textContent = visible ? '👁' : '🙈';
    }

    // ─── Tx Modal ─────────────────────────────────────
    function openTx() { document.getElementById('txOverlay').classList.add('open'); }
    function closeTxModal() { document.getElementById('txOverlay').classList.remove('open'); }
    function closeTx(e) { if(e.target===document.getElementById('txOverlay')) closeTxModal(); }

    function setType(t, btn) {
      document.getElementById('txType').value = t;
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('toWalletGroup').style.display = t==='TRANSFER'?'block':'none';
    }

    document.getElementById('txForm').addEventListener('submit', async e => {
      e.preventDefault();
      const alert = document.getElementById('txAlert');
      alert.classList.remove('show');
      const fd = new FormData(e.target);
      const btn = e.target.querySelector('button[type=submit]');
      btn.disabled = true; btn.textContent = 'Menyimpan...';
      try {
        const res = await fetch('transaction_actions.php?action=add_transaction', { method:'POST', body:fd });
        const d = await res.json();
        if (d.success) location.reload();
        else { alert.textContent = d.message; alert.classList.add('show'); btn.disabled=false; btn.textContent='Simpan'; }
      } catch { alert.textContent='Gagal terhubung.'; alert.classList.add('show'); btn.disabled=false; btn.textContent='Simpan'; }
    });

    // ─── Balance Modal ────────────────────────────────
    function openBal(id, name, bal) {
      document.getElementById('balWalletId').value = id;
      document.getElementById('balName').textContent = name;
      document.getElementById('balAmount').value = bal;
      document.getElementById('balOverlay').classList.add('open');
    }
    function closeBalModal() { document.getElementById('balOverlay').classList.remove('open'); }
    function closeBal(e) { if(e.target===document.getElementById('balOverlay')) closeBalModal(); }

    document.getElementById('balForm').addEventListener('submit', async e => {
      e.preventDefault();
      const alert = document.getElementById('balAlert');
      alert.classList.remove('show');
      const fd = new FormData(e.target);
      const btn = e.target.querySelector('button[type=submit]');
      btn.disabled = true; btn.textContent = 'Menyimpan...';
      try {
        const res = await fetch('transaction_actions.php?action=set_balance', { method:'POST', body:fd });
        const d = await res.json();
        if (d.success) location.reload();
        else { alert.textContent = d.message; alert.classList.add('show'); btn.disabled=false; btn.textContent='Simpan'; }
      } catch { alert.textContent='Gagal terhubung.'; alert.classList.add('show'); btn.disabled=false; btn.textContent='Simpan'; }
    });

    // ─── Delete Tx ────────────────────────────────────
    async function delTx(id) {
      if (!confirm('Hapus transaksi ini? Saldo akan dikembalikan.')) return;
      const res = await fetch('transaction_actions.php?action=delete_transaction', {
        method:'DELETE', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+id
      });
      const d = await res.json();
      if (d.success) location.reload();
      else alert(d.message);
    }
  </script>
</body>
</html>
