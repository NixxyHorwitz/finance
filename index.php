<?php
require_once 'db.php';
requireLogin();

// Prefetch wallets for the modal selects only
$sw = $pdo->prepare("SELECT id, name FROM Wallet WHERE userId = ? ORDER BY FIELD(name,'Cash','Dana','Gopay','ShopeePay','Savings')");
$sw->execute([$_SESSION['user_id']]);
$wallets = $sw->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Neofinance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="wrap">

  <!-- Header -->
  <header class="app-header">
    <div>
      <div class="brand">Neofinance</div>
      <div class="header-info">Halo, <?= htmlspecialchars($_SESSION['username']) ?> 👋</div>
    </div>
    <a href="logout.php" class="btn btn-ghost btn-xs">Keluar</a>
  </header>

  <!-- Balance Hero -->
  <div class="balance-card" id="balanceCard">
    <div>
      <div class="balance-label">Total Saldo</div>
      <div class="balance-amt" id="totalBalance">Memuat…</div>
    </div>
    <button class="btn btn-ghost btn-icon btn-xs" id="eyeBtn" onclick="toggleVis()">👁</button>
  </div>

  <!-- Wallets -->
  <div class="row-head mb-1">
    <span class="section-label">Dompet</span>
  </div>
  <div class="wallet-grid" id="walletGrid">
    <!-- injected by JS -->
  </div>

  <!-- Transactions Header -->
  <div class="row-head mb-1">
    <span class="section-label">Transaksi</span>
    <button class="btn btn-xs" onclick="openTxModal()">+ Tambah</button>
  </div>

  <!-- Chart -->
  <div class="chart-box mb-2">
    <canvas id="chart"></canvas>
  </div>

  <!-- Transaction List -->
  <div class="tx-wrap" id="txWrap">
    <div class="spinner show" id="txSpinner">Memuat transaksi…</div>
  </div>

</div><!-- /wrap -->

<!-- Toast -->
<div class="toast-container" id="toastContainer"></div>

<!-- ══════════════ Modal: Add Transaction ══════════════ -->
<div class="overlay" id="txOverlay" onclick="overlayClose(event,'txOverlay',closeTxModal)">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">Tambah Transaksi</span>
      <span class="modal-close" onclick="closeTxModal()">✕</span>
    </div>

    <div class="tabs mb-1">
      <button class="tab active" onclick="setTxType('EXPENSE',this)">Pengeluaran</button>
      <button class="tab" onclick="setTxType('INCOME',this)">Pemasukan</button>
      <button class="tab" onclick="setTxType('TRANSFER',this)">Transfer</button>
    </div>

    <div id="txAlert" class="alert alert-err"></div>

    <form id="txForm" onsubmit="submitTx(event)">
      <input type="hidden" name="type" id="txType" value="EXPENSE">

      <div class="fgroup">
        <label>Dari Dompet</label>
        <select name="walletId" id="fromWallet" required>
          <?php foreach($wallets as $w): ?>
            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="fgroup" id="toWalletGroup" style="display:none">
        <label>Ke Dompet</label>
        <select name="relatedWalletId" id="toWallet">
          <?php foreach($wallets as $w): ?>
            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="fgroup">
        <label>Jumlah (Rp)</label>
        <input type="number" name="amount" id="txAmount" placeholder="0" min="1" required>
      </div>

      <div class="fgroup">
        <label>Keterangan</label>
        <input type="text" name="description" id="txDesc" placeholder="contoh: makan siang, gofood…" required>
        <span style="font-size:0.65rem;color:var(--muted);margin-top:2px;">Kategori otomatis dari kata kunci 🪄</span>
      </div>

      <button type="submit" class="btn btn-full" id="txSubmit">Simpan</button>
    </form>
  </div>
</div>

<!-- ══════════════ Modal: Set Balance ══════════════ -->
<div class="overlay" id="balOverlay" onclick="overlayClose(event,'balOverlay',closeBalModal)">
  <div class="modal">
    <div class="modal-head">
      <div>
        <div class="modal-title">Atur Saldo</div>
        <div id="balModalSub" style="font-size:0.72rem;color:var(--muted);margin-top:2px;"></div>
      </div>
      <span class="modal-close" onclick="closeBalModal()">✕</span>
    </div>

    <div id="balAlert" class="alert alert-err"></div>

    <form id="balForm" onsubmit="submitBal(event)">
      <input type="hidden" name="walletId" id="balWalletId">
      <div class="fgroup">
        <label>Saldo Baru (Rp)</label>
        <input type="number" name="balance" id="balAmount" placeholder="0" min="0" required>
      </div>
      <button type="submit" class="btn btn-full" id="balSubmit">Simpan</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ════════════════════════════════════════════
//  STATE
// ════════════════════════════════════════════
let state = { wallets: [], transactions: [], visible: true };
let chartInstance = null;

// ════════════════════════════════════════════
//  FORMAT
// ════════════════════════════════════════════
const fmt = n => 'Rp ' + Number(n).toLocaleString('id-ID');
const fmtShort = n => {
  if (n >= 1e9) return 'Rp '+Number(n/1e9).toLocaleString('id-ID',{maximumFractionDigits:1})+'M';
  if (n >= 1e6) return 'Rp '+Number(n/1e6).toLocaleString('id-ID',{maximumFractionDigits:1})+'jt';
  if (n >= 1e3) return 'Rp '+Number(n/1e3).toLocaleString('id-ID',{maximumFractionDigits:0})+'rb';
  return 'Rp '+n;
};
const fmtDate = d => new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short'});

// ════════════════════════════════════════════
//  FETCH DATA
// ════════════════════════════════════════════
async function loadData() {
  try {
    const res = await fetch('api_data.php');
    const data = await res.json();
    if (!data.success) { toast('Gagal memuat data', true); return; }
    state.wallets = data.wallets;
    state.transactions = data.transactions;
    render();
  } catch(e) {
    toast('Koneksi gagal', true);
  }
}

// ════════════════════════════════════════════
//  RENDER
// ════════════════════════════════════════════
function render() {
  renderBalance();
  renderWallets();
  renderChart();
  renderTxList();
}

function renderBalance() {
  const total = state.wallets.reduce((s,w) => s + w.balance, 0);
  const el = document.getElementById('totalBalance');
  el.dataset.val = fmt(total);
  el.textContent = state.visible ? fmt(total) : 'Rp ••••••';
}

const walletIconMap = { 'dana':'public/dana.png', 'gopay':'public/gopay.png', 'shopeepay':'public/shopeepay.png' };
function walletIconHTML(name) {
  const n = name.toLowerCase();
  for (const k of Object.keys(walletIconMap)) {
    if (n.includes(k)) return `<img src="${walletIconMap[k]}" class="wallet-icon" alt="${name}">`;
  }
  const emoji = n.includes('saving') ? '🐷' : '💵';
  const bg    = n.includes('saving') ? '#dcfce7' : '#fef9c3';
  return `<div class="wallet-emoji" style="background:${bg}">${emoji}</div>`;
}

function renderWallets() {
  const grid = document.getElementById('walletGrid');
  grid.innerHTML = state.wallets.map(w => `
    <div class="wallet-card">
      <div class="wallet-edit-btn" onclick="openBalModal('${w.id}','${w.name.replace(/'/g,"\\'")}',${w.balance})">✏️</div>
      ${walletIconHTML(w.name)}
      <div class="wallet-name">${w.name}</div>
      <div class="wallet-bal bal-item" data-val="${fmtShort(w.balance)}">
        ${state.visible ? fmtShort(w.balance) : '••••'}
      </div>
    </div>
  `).join('');
}

function renderChart() {
  const map = {};
  [...state.transactions].reverse().forEach(t => {
    if (t.type === 'TRANSFER') return;
    const d = fmtDate(t.date);
    if (!map[d]) map[d] = { i:0, e:0 };
    if (t.type==='INCOME')  map[d].i += t.amount;
    if (t.type==='EXPENSE') map[d].e += t.amount;
  });
  const labels = Object.keys(map).slice(-14); // last 14 days
  const inc    = labels.map(l => map[l].i);
  const exp    = labels.map(l => map[l].e);

  if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
  const ctx = document.getElementById('chart');
  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label:'Pemasukan',   data:inc, backgroundColor:'#22c55e', borderRadius:4, borderSkipped:false },
        { label:'Pengeluaran', data:exp, backgroundColor:'#ff4d4d', borderRadius:4, borderSkipped:false }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins: { legend:{ labels:{ font:{family:'Inter',size:10,weight:'700'}, color:'#111', boxWidth:10, padding:10 } } },
      scales: {
        x:{ grid:{display:false}, ticks:{font:{family:'Inter',size:9}, color:'#aaa'}, border:{display:false} },
        y:{ grid:{color:'#f0f0f0'}, border:{display:false},
            ticks:{ font:{family:'Inter',size:9}, color:'#aaa',
                    callback: v => v>=1e6 ? v/1e6+'jt' : v>=1e3 ? v/1e3+'rb' : v } }
      }
    }
  });
}

const txIcons = { INCOME:'💰', EXPENSE:'💸', TRANSFER:'↔️' };
const txIconCls = { INCOME:'income', EXPENSE:'expense', TRANSFER:'transfer' };

function renderTxList() {
  const wrap = document.getElementById('txWrap');
  document.getElementById('txSpinner').classList.remove('show');
  if (!state.transactions.length) {
    wrap.innerHTML = '<div class="empty">Belum ada transaksi.<br>Yuk mulai catat pengeluaranmu!</div>';
    return;
  }
  wrap.innerHTML = state.transactions.map(t => {
    const sign  = t.type==='INCOME' ? '+' : t.type==='EXPENSE' ? '-' : '';
    const cls   = t.type==='INCOME' ? 'income' : t.type==='EXPENSE' ? 'expense' : '';
    const meta  = fmtDate(t.date) + ' · ' + t.walletName + (t.type==='TRANSFER' ? ' → '+t.relatedWalletName : '');
    const badge = (t.categoryName && t.type!=='TRANSFER') ? `<div class="tx-cat">${t.categoryName}</div>` : '';
    return `
      <div class="tx-item">
        <div class="tx-icon ${txIconCls[t.type]}">${txIcons[t.type]}</div>
        <div class="tx-body">
          <div class="tx-desc">${escHtml(t.description)}</div>
          <div class="tx-meta">${meta}</div>
          ${badge}
        </div>
        <div class="tx-right">
          <span class="tx-amt ${cls}">${sign}${fmtShort(t.amount)}</span>
          <button class="btn btn-ghost btn-icon btn-xs" onclick="deleteTx('${t.id}')" title="Hapus">🗑</button>
        </div>
      </div>`;
  }).join('');
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ════════════════════════════════════════════
//  TOGGLE VISIBILITY
// ════════════════════════════════════════════
function toggleVis() {
  state.visible = !state.visible;
  document.getElementById('eyeBtn').textContent = state.visible ? '👁' : '🙈';
  // Total
  const el = document.getElementById('totalBalance');
  el.textContent = state.visible ? el.dataset.val : 'Rp ••••••';
  // Wallet amounts
  document.querySelectorAll('.bal-item').forEach(el =>
    el.textContent = state.visible ? el.dataset.val : '••••'
  );
}

// ════════════════════════════════════════════
//  TX MODAL
// ════════════════════════════════════════════
function openTxModal() {
  document.getElementById('txOverlay').classList.add('open');
  document.getElementById('txDesc').focus();
}
function closeTxModal() {
  document.getElementById('txOverlay').classList.remove('open');
  document.getElementById('txForm').reset();
  document.getElementById('txAlert').classList.remove('show');
  // reset tabs
  document.querySelectorAll('#txOverlay .tab').forEach((t,i) => t.classList.toggle('active', i===0));
  document.getElementById('txType').value = 'EXPENSE';
  document.getElementById('toWalletGroup').style.display = 'none';
}

function setTxType(type, btn) {
  document.getElementById('txType').value = type;
  document.querySelectorAll('#txOverlay .tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('toWalletGroup').style.display = type==='TRANSFER' ? 'block' : 'none';
}

async function submitTx(e) {
  e.preventDefault();
  const alertEl = document.getElementById('txAlert');
  alertEl.classList.remove('show');
  const btn = document.getElementById('txSubmit');
  btn.disabled = true; btn.textContent = 'Menyimpan…';
  try {
    const res  = await fetch('transaction_actions.php?action=add_transaction', { method:'POST', body:new FormData(e.target) });
    const data = await res.json();
    if (data.success) {
      closeTxModal();
      toast('Transaksi berhasil disimpan ✓');
      await loadData();
    } else {
      alertEl.textContent = data.message;
      alertEl.classList.add('show');
    }
  } catch { alertEl.textContent = 'Koneksi gagal.'; alertEl.classList.add('show'); }
  btn.disabled = false; btn.textContent = 'Simpan';
}

// ════════════════════════════════════════════
//  BALANCE MODAL
// ════════════════════════════════════════════
function openBalModal(id, name, bal) {
  document.getElementById('balWalletId').value = id;
  document.getElementById('balModalSub').textContent = name;
  document.getElementById('balAmount').value = bal;
  document.getElementById('balOverlay').classList.add('open');
  document.getElementById('balAmount').focus();
}
function closeBalModal() {
  document.getElementById('balOverlay').classList.remove('open');
  document.getElementById('balAlert').classList.remove('show');
}

async function submitBal(e) {
  e.preventDefault();
  const alertEl = document.getElementById('balAlert');
  alertEl.classList.remove('show');
  const btn = document.getElementById('balSubmit');
  btn.disabled = true; btn.textContent = 'Menyimpan…';
  try {
    const res  = await fetch('transaction_actions.php?action=set_balance', { method:'POST', body:new FormData(e.target) });
    const data = await res.json();
    if (data.success) {
      closeBalModal();
      toast('Saldo berhasil diubah ✓');
      await loadData();
    } else {
      alertEl.textContent = data.message;
      alertEl.classList.add('show');
    }
  } catch { alertEl.textContent = 'Koneksi gagal.'; alertEl.classList.add('show'); }
  btn.disabled = false; btn.textContent = 'Simpan';
}

// ════════════════════════════════════════════
//  DELETE TX
// ════════════════════════════════════════════
async function deleteTx(id) {
  if (!confirm('Hapus transaksi ini? Saldo akan disesuaikan kembali.')) return;
  try {
    const res  = await fetch('transaction_actions.php?action=delete_transaction', {
      method:'DELETE',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'id='+id
    });
    const data = await res.json();
    if (data.success) { toast('Transaksi dihapus'); await loadData(); }
    else toast(data.message, true);
  } catch { toast('Gagal menghapus', true); }
}

// ════════════════════════════════════════════
//  OVERLAY CLICK OUTSIDE
// ════════════════════════════════════════════
function overlayClose(e, id, fn) {
  if (e.target === document.getElementById(id)) fn();
}

// ════════════════════════════════════════════
//  TOAST
// ════════════════════════════════════════════
function toast(msg, isErr=false) {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = 'toast' + (isErr ? ' err' : '');
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => t.remove(), 2800);
}

// ════════════════════════════════════════════
//  INIT
// ════════════════════════════════════════════
loadData();
</script>

</body>
</html>
