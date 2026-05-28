<?php
require_once 'db.php';
requireLogin();

$sw = $pdo->prepare("SELECT id, name FROM Wallet WHERE userId = ? ORDER BY FIELD(name,'Cash','Dana','Gopay','ShopeePay','Savings')");
$sw->execute([$_SESSION['user_id']]);
$wallets = $sw->fetchAll();

$pageTitle = 'Neofinance – Dashboard';
include '_head.php';
?>
<body>

<div class="wrap">

  <header class="app-header">
    <div>
      <div class="brand">Neofinance</div>
      <div class="header-info">Halo, <?= htmlspecialchars($_SESSION['username']) ?> 👋</div>
    </div>
    <a href="logout.php" class="btn btn-ghost btn-xs">Keluar</a>
  </header>

  <div class="balance-card">
    <div>
      <div class="balance-label">Total Saldo</div>
      <div class="balance-amt" id="totalBalance">—</div>
    </div>
    <button class="btn btn-ghost btn-icon btn-xs" id="eyeBtn" onclick="toggleVis()">👁</button>
  </div>

  <div class="row-head mb-1">
    <span class="section-label">Dompet</span>
  </div>
  <div class="wallet-grid" id="walletGrid"></div>

  <div class="row-head mb-1">
    <span class="section-label">Transaksi</span>
    <button class="btn btn-xs" onclick="openTxModal()">+ Tambah</button>
  </div>

  <div class="chart-box mb-2">
    <canvas id="chart"></canvas>
  </div>

  <div class="tx-wrap" id="txWrap">
    <div class="spinner show" id="txSpinner">Memuat data…</div>
  </div>

</div>

<div class="toast-container" id="toastContainer"></div>

<!-- Modal: Tambah Transaksi -->
<div class="overlay" id="txOverlay" onclick="if(event.target===this)closeTxModal()">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">Tambah Transaksi</span>
      <span class="modal-close" onclick="closeTxModal()">✕</span>
    </div>
    <div class="tabs">
      <button class="tab active" onclick="setTxType('EXPENSE',this)">Pengeluaran</button>
      <button class="tab" onclick="setTxType('INCOME',this)">Pemasukan</button>
      <button class="tab" onclick="setTxType('TRANSFER',this)">Transfer</button>
    </div>
    <div id="txAlert" class="alert alert-err"></div>
    <form id="txForm" onsubmit="submitTx(event)">
      <input type="hidden" name="type" id="txType" value="EXPENSE">
      <div class="fgroup">
        <label>Dari Dompet</label>
        <select name="walletId" required>
          <?php foreach($wallets as $w): ?>
            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgroup" id="toWalletGroup" style="display:none">
        <label>Ke Dompet</label>
        <select name="relatedWalletId">
          <?php foreach($wallets as $w): ?>
            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgroup">
        <label>Jumlah (Rp)</label>
        <input type="number" name="amount" placeholder="0" min="1" required>
      </div>
      <div class="fgroup">
        <label>Keterangan</label>
        <input type="text" name="description" placeholder="contoh: makan siang, gofood…" required>
        <span style="font-size:0.65rem;color:var(--muted);margin-top:2px;">Kategori otomatis dari kata kunci 🪄</span>
      </div>
      <button type="submit" class="btn btn-full" id="txSubmit">Simpan</button>
    </form>
  </div>
</div>

<!-- Modal: Set Saldo -->
<div class="overlay" id="balOverlay" onclick="if(event.target===this)closeBalModal()">
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
let state = { wallets: [], transactions: [], visible: true };
let chartInst = null;

const fmt = n => 'Rp ' + Number(n).toLocaleString('id-ID');
const fmtS = n => {
  if (n >= 1e9) return 'Rp ' + (n/1e9).toLocaleString('id-ID',{maximumFractionDigits:1}) + 'M';
  if (n >= 1e6) return 'Rp ' + (n/1e6).toLocaleString('id-ID',{maximumFractionDigits:1}) + 'jt';
  if (n >= 1e3) return 'Rp ' + (n/1e3).toLocaleString('id-ID',{maximumFractionDigits:0}) + 'rb';
  return 'Rp ' + n;
};
const fmtD = d => new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short'});
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

async function loadData() {
  try {
    const res = await fetch('api_data.php');
    const d = await res.json();
    if (!d.success) { toast('Gagal memuat data', true); return; }
    state.wallets = d.wallets;
    state.transactions = d.transactions;
    render();
  } catch { toast('Koneksi gagal', true); }
}

function render() {
  const total = state.wallets.reduce((s,w) => s + w.balance, 0);
  const el = document.getElementById('totalBalance');
  el.dataset.val = fmt(total);
  el.textContent = state.visible ? fmt(total) : 'Rp ••••••';

  // Wallets
  const iMap = { dana:'public/dana.png', gopay:'public/gopay.png', shopeepay:'public/shopeepay.png' };
  document.getElementById('walletGrid').innerHTML = state.wallets.map(w => {
    const n = w.name.toLowerCase();
    let icon = '';
    for (const k of Object.keys(iMap)) {
      if (n.includes(k)) { icon = `<img src="${iMap[k]}" class="wallet-icon" alt="${w.name}">`; break; }
    }
    if (!icon) {
      const e = n.includes('saving') ? '🐷' : '💵';
      const bg = n.includes('saving') ? '#dcfce7' : '#fef9c3';
      icon = `<div class="wallet-emoji" style="background:${bg}">${e}</div>`;
    }
    const bal = state.visible ? fmtS(w.balance) : '••••';
    return `<div class="wallet-card">
      <div class="wallet-edit-btn" onclick="openBalModal('${w.id}','${w.name.replace(/'/g,"\\'")}',${w.balance})">✏️</div>
      ${icon}
      <div class="wallet-name">${esc(w.name)}</div>
      <div class="wallet-bal bal-item" data-val="${fmtS(w.balance)}">${bal}</div>
    </div>`;
  }).join('');

  // Chart
  const map = {};
  [...state.transactions].reverse().forEach(t => {
    if (t.type === 'TRANSFER') return;
    const d = fmtD(t.date);
    if (!map[d]) map[d] = { i:0, e:0 };
    if (t.type==='INCOME')  map[d].i += t.amount;
    if (t.type==='EXPENSE') map[d].e += t.amount;
  });
  const labels = Object.keys(map).slice(-14);
  if (chartInst) { chartInst.destroy(); chartInst = null; }
  chartInst = new Chart(document.getElementById('chart'), {
    type:'bar',
    data: {
      labels,
      datasets: [
        { label:'Pemasukan',   data:labels.map(l=>map[l].i), backgroundColor:'#22c55e', borderRadius:4 },
        { label:'Pengeluaran', data:labels.map(l=>map[l].e), backgroundColor:'#ff4d4d', borderRadius:4 }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ labels:{ font:{family:'Inter',size:10,weight:'700'}, color:'#111', boxWidth:10, padding:8 } } },
      scales:{
        x:{ grid:{display:false}, ticks:{font:{family:'Inter',size:9},color:'#aaa'}, border:{display:false} },
        y:{ grid:{color:'#f0f0f0'}, border:{display:false}, ticks:{ font:{family:'Inter',size:9}, color:'#aaa', callback:v=>v>=1e6?v/1e6+'jt':v>=1e3?v/1e3+'rb':v } }
      }
    }
  });

  // TX List
  const wrap = document.getElementById('txWrap');
  document.getElementById('txSpinner').classList.remove('show');
  if (!state.transactions.length) {
    wrap.innerHTML = '<div class="empty">Belum ada transaksi 💸<br>Yuk mulai catat!</div>';
    return;
  }
  const icons = { INCOME:'💰', EXPENSE:'💸', TRANSFER:'↔️' };
  const cls   = { INCOME:'income', EXPENSE:'expense', TRANSFER:'transfer' };
  wrap.innerHTML = state.transactions.map(t => {
    const sign  = t.type==='INCOME'?'+':t.type==='EXPENSE'?'-':'';
    const amtCls= t.type==='INCOME'?'income':t.type==='EXPENSE'?'expense':'';
    const meta  = fmtD(t.date)+' · '+t.walletName+(t.type==='TRANSFER'?' → '+t.relatedWalletName:'');
    const badge = (t.categoryName && t.type!=='TRANSFER') ? `<div class="tx-cat">${esc(t.categoryName)}</div>` : '';
    return `<div class="tx-item">
      <div class="tx-icon ${cls[t.type]}">${icons[t.type]}</div>
      <div class="tx-body">
        <div class="tx-desc">${esc(t.description)}</div>
        <div class="tx-meta">${meta}</div>${badge}
      </div>
      <div class="tx-right">
        <span class="tx-amt ${amtCls}">${sign}${fmtS(t.amount)}</span>
        <button class="btn btn-ghost btn-icon btn-xs" onclick="deleteTx('${t.id}')">🗑</button>
      </div>
    </div>`;
  }).join('');
}

function toggleVis() {
  state.visible = !state.visible;
  document.getElementById('eyeBtn').textContent = state.visible ? '👁' : '🙈';
  const el = document.getElementById('totalBalance');
  el.textContent = state.visible ? el.dataset.val : 'Rp ••••••';
  document.querySelectorAll('.bal-item').forEach(e => e.textContent = state.visible ? e.dataset.val : '••••');
}

// TX Modal
function openTxModal() { document.getElementById('txOverlay').classList.add('open'); }
function closeTxModal() {
  document.getElementById('txOverlay').classList.remove('open');
  document.getElementById('txForm').reset();
  document.getElementById('txAlert').classList.remove('show');
  document.querySelectorAll('#txOverlay .tab').forEach((t,i) => t.classList.toggle('active',i===0));
  document.getElementById('txType').value = 'EXPENSE';
  document.getElementById('toWalletGroup').style.display = 'none';
}
function setTxType(type, btn) {
  document.getElementById('txType').value = type;
  document.querySelectorAll('#txOverlay .tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('toWalletGroup').style.display = type==='TRANSFER'?'block':'none';
}
async function submitTx(e) {
  e.preventDefault();
  const a = document.getElementById('txAlert'); a.classList.remove('show');
  const b = document.getElementById('txSubmit'); b.disabled=true; b.textContent='Menyimpan…';
  try {
    const r = await fetch('transaction_actions.php?action=add_transaction',{method:'POST',body:new FormData(e.target)});
    const d = await r.json();
    if (d.success) { closeTxModal(); toast('Transaksi disimpan ✓'); await loadData(); }
    else { a.textContent=d.message; a.classList.add('show'); }
  } catch { a.textContent='Koneksi gagal.'; a.classList.add('show'); }
  b.disabled=false; b.textContent='Simpan';
}

// Balance Modal
function openBalModal(id, name, bal) {
  document.getElementById('balWalletId').value = id;
  document.getElementById('balModalSub').textContent = name;
  document.getElementById('balAmount').value = bal;
  document.getElementById('balOverlay').classList.add('open');
}
function closeBalModal() {
  document.getElementById('balOverlay').classList.remove('open');
  document.getElementById('balAlert').classList.remove('show');
}
async function submitBal(e) {
  e.preventDefault();
  const a = document.getElementById('balAlert'); a.classList.remove('show');
  const b = document.getElementById('balSubmit'); b.disabled=true; b.textContent='Menyimpan…';
  try {
    const r = await fetch('transaction_actions.php?action=set_balance',{method:'POST',body:new FormData(e.target)});
    const d = await r.json();
    if (d.success) { closeBalModal(); toast('Saldo diperbarui ✓'); await loadData(); }
    else { a.textContent=d.message; a.classList.add('show'); }
  } catch { a.textContent='Koneksi gagal.'; a.classList.add('show'); }
  b.disabled=false; b.textContent='Simpan';
}

// Delete TX
async function deleteTx(id) {
  if (!confirm('Hapus transaksi ini? Saldo akan dikembalikan.')) return;
  try {
    const r = await fetch('transaction_actions.php?action=delete_transaction',{method:'DELETE',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});
    const d = await r.json();
    if (d.success) { toast('Dihapus'); await loadData(); }
    else toast(d.message, true);
  } catch { toast('Gagal menghapus', true); }
}

// Toast
function toast(msg, err=false) {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = 'toast' + (err?' err':'');
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(()=>t.remove(), 2800);
}

loadData();
</script>
</body>
</html>
