<?php
require_once 'db.php';
requireLogin();

$sw = $pdo->prepare("SELECT id, name FROM Wallet WHERE userId = ? ORDER BY FIELD(name,'Cash','Dana','Gopay','ShopeePay','Savings')");
$sw->execute([$_SESSION['user_id']]);
$wallets = $sw->fetchAll();

$walletsJson = json_encode($wallets);
$pageTitle   = 'Neofinance';
include '_head.php';
?>
<body>

<div class="wrap">

  <!-- Header -->
  <div class="app-header">
    <div>
      <div class="brand">Neo<span>finance</span></div>
      <div class="header-sub">Halo, <?= htmlspecialchars($_SESSION['username']) ?> ðŸ‘‹</div>
    </div>
  </div>

  <!-- Balance Hero -->
  <div class="balance-hero mb-2">
    <div class="balance-row">
      <div>
        <div class="balance-label">Total Saldo</div>
        <div class="balance-amt" id="totalBalance">â€”</div>
      </div>
      <div class="eye-btn" onclick="toggleVis()" id="eyeBtn">ðŸ‘</div>
    </div>
    <div class="balance-stats">
      <div class="stat-item">
        <div class="stat-label">ðŸ’š Pemasukan bulan ini</div>
        <div class="stat-val" id="totalInc">â€”</div>
      </div>
      <div class="stat-item">
        <div class="stat-label">ðŸ”´ Pengeluaran bulan ini</div>
        <div class="stat-val" id="totalExp">â€”</div>
      </div>
    </div>
  </div>

  <!-- Wallets -->
  <div class="sec-head mb-1">
    <span class="sec-label">Dompet</span>
  </div>
  <div class="wallet-grid mb-2" id="walletGrid">
    <div class="spinner show" style="grid-column:span 3;padding:1.5rem">Memuatâ€¦</div>
  </div>

  <!-- Chart -->
  <div class="sec-head mb-1">
    <span class="sec-label">Grafik</span>
    <div class="chart-tabs">
      <button class="chart-tab active" onclick="setChartPeriod(7,this)">7 hari</button>
      <button class="chart-tab" onclick="setChartPeriod(30,this)">30 hari</button>
    </div>
  </div>
  <div class="chart-box mb-2 sec-card" style="padding:0.875rem;height:210px">
    <canvas id="chart"></canvas>
  </div>

  <!-- Transactions -->
  <div class="sec-head mb-1">
    <span class="sec-label">Riwayat Transaksi</span>
  </div>
  <div class="sec-card tx-wrap" id="txWrap">
    <div class="spinner show" id="txSpinner">Memuat transaksiâ€¦</div>
  </div>

</div><!-- /wrap -->

<nav class="bottom-nav">
  <div class="nav-item active" id="nav-home" onclick="scrollToTop()">
    <div class="nav-icon">ðŸ </div>
    <div class="nav-label">Beranda</div>
  </div>
  <a class="nav-item" href="flow.php">
    <div class="nav-icon">ðŸ“Š</div>
    <div class="nav-label">Aliran</div>
  </a>
  <div class="nav-fab" onclick="openAddModal()">ï¼‹</div>
  <div class="nav-item" id="nav-history" onclick="scrollToHistory()">
    <div class="nav-icon">ðŸ“‹</div>
    <div class="nav-label">Riwayat</div>
  </div>
  <a class="nav-item" href="settings.php">
    <div class="nav-icon">âš™ï¸</div>
    <div class="nav-label">Pengaturan</div>
  </a>
</nav>

<!-- Toast -->
<div class="toast-wrap" id="toastWrap"></div>

<!-- â•â•â•â•â•â•â•â•â•â•â• MODAL: ADD RECORD â•â•â•â•â•â•â•â•â•â•â• -->
<div class="overlay" id="addOverlay" onclick="if(event.target===this)closeAddModal()">
  <div class="modal" id="addModal">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <span class="modal-title">Catat Transaksi</span>
      <span class="modal-close" onclick="closeAddModal()">âœ•</span>
    </div>
    <div class="modal-body">

      <!-- Type Tabs -->
      <div class="type-tabs mb-1">
        <button class="type-tab active-expense" data-type="EXPENSE" onclick="setType(this)">
          <span class="tab-icon">ðŸ’¸</span>Keluar
        </button>
        <button class="type-tab" data-type="INCOME" onclick="setType(this)">
          <span class="tab-icon">ðŸ’°</span>Masuk
        </button>
        <button class="type-tab" data-type="TRANSFER" onclick="setType(this)">
          <span class="tab-icon">â†”ï¸</span>Transfer
        </button>
      </div>

      <div id="addAlert" class="alert alert-err"></div>

      <!-- Wallet From -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.4rem">
        <div class="wallet-selector-label" style="margin-bottom:0">Dari Dompet</div>
        <div class="mini-eye-btn" id="modalEyeBtn" onclick="toggleModalBal()" title="Toggle tampilkan saldo">ðŸ‘</div>
      </div>
      <div class="wallet-selector" id="fromWalletSel"></div>
      <input type="hidden" id="selectedFromWallet">

      <!-- Wallet To (Transfer) -->
      <div id="toWalletBlock" style="display:none">
        <div class="wallet-selector-label">Ke Dompet</div>
        <div class="wallet-selector" id="toWalletSel"></div>
        <input type="hidden" id="selectedToWallet">
      </div>

      <!-- Amount Display -->
      <div class="wallet-selector-label">Jumlah</div>
      <div class="amount-display mb-1">
        <div class="amount-prefix">Rp</div>
        <div class="amount-value placeholder" id="amountDisplay">0</div>
      </div>

      <!-- Quick Amounts -->
      <div class="quick-amounts mb-1">
        <button class="quick-btn" onclick="quickAmt(10000)">+10rb</button>
        <button class="quick-btn" onclick="quickAmt(20000)">+20rb</button>
        <button class="quick-btn" onclick="quickAmt(50000)">+50rb</button>
        <button class="quick-btn" onclick="quickAmt(100000)">+100rb</button>
        <button class="quick-btn" onclick="quickAmt(200000)">+200rb</button>
        <button class="quick-btn" onclick="quickAmt(500000)">+500rb</button>
        <button class="quick-btn" onclick="quickAmt(1000000)">+1jt</button>
      </div>

      <!-- Description ABOVE numpad -->
      <div class="fgroup mb-1" id="descGroup">
        <label>Keterangan <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted)">(auto-kategori ðŸª„)</span></label>
        <input type="text" id="descInput" placeholder="contoh: makan siang, bensin, gofoodâ€¦">
      </div>

      <!-- Numpad (conditional) -->
      <div id="numpadWrap">
        <div class="numpad">
          <button class="nk" onclick="numpad(1)">1</button>
          <button class="nk" onclick="numpad(2)">2</button>
          <button class="nk" onclick="numpad(3)">3</button>
          <button class="nk" onclick="numpad(4)">4</button>
          <button class="nk" onclick="numpad(5)">5</button>
          <button class="nk" onclick="numpad(6)">6</button>
          <button class="nk" onclick="numpad(7)">7</button>
          <button class="nk" onclick="numpad(8)">8</button>
          <button class="nk" onclick="numpad(9)">9</button>
          <button class="nk nk-0" onclick="numpad(0)">0</button>
          <button class="nk nk-del" onclick="numpadDel()">âŒ«</button>
        </div>
      </div>

      <!-- Native input (fallback) -->
      <div id="nativeAmtWrap" style="display:none" class="fgroup mb-2">
        <label>Jumlah (Rp)</label>
        <input type="number" id="nativeAmt" placeholder="0" min="1" oninput="syncNativeAmt(this.value)">
      </div>

      <button class="btn btn-full btn-lg" id="addSubmitBtn" onclick="submitRecord()" style="margin-top:0.25rem;">
        ðŸ’¾ Simpan Transaksi
      </button>

    </div>
  </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â• MODAL: SET BALANCE â•â•â•â•â•â•â•â•â•â•â• -->
<div class="overlay" id="balOverlay" onclick="if(event.target===this)closeBalModal()">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <div>
        <div class="modal-title">Atur Saldo</div>
        <div id="balModalSub" style="font-size:0.72rem;color:var(--muted);margin-top:2px;"></div>
      </div>
      <span class="modal-close" onclick="closeBalModal()">âœ•</span>
    </div>
    <div class="modal-body">
      <div id="balAlert" class="alert alert-err"></div>
      <div class="amount-display mb-1">
        <div class="amount-prefix">Rp</div>
        <div class="amount-value" id="balDisplay">0</div>
      </div>
      <div class="quick-amounts mb-1">
        <button class="quick-btn" onclick="balQuick(10000)">10rb</button>
        <button class="quick-btn" onclick="balQuick(50000)">50rb</button>
        <button class="quick-btn" onclick="balQuick(100000)">100rb</button>
        <button class="quick-btn" onclick="balQuick(500000)">500rb</button>
        <button class="quick-btn" onclick="balQuick(1000000)">1jt</button>
        <button class="quick-btn" onclick="balQuick(5000000)">5jt</button>
      </div>
      <div class="numpad">
        <button class="nk" onclick="balNumpad(1)">1</button>
        <button class="nk" onclick="balNumpad(2)">2</button>
        <button class="nk" onclick="balNumpad(3)">3</button>
        <button class="nk" onclick="balNumpad(4)">4</button>
        <button class="nk" onclick="balNumpad(5)">5</button>
        <button class="nk" onclick="balNumpad(6)">6</button>
        <button class="nk" onclick="balNumpad(7)">7</button>
        <button class="nk" onclick="balNumpad(8)">8</button>
        <button class="nk" onclick="balNumpad(9)">9</button>
        <button class="nk nk-0" onclick="balNumpad(0)">0</button>
        <button class="nk nk-del" onclick="balNumpadDel()">âŒ«</button>
      </div>
      <input type="hidden" id="balWalletId">
      <button class="btn btn-full btn-lg" id="balSubmitBtn" onclick="submitBalance()" style="margin-top:0.5rem;">
        ðŸ’¾ Simpan Saldo
      </button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  CONSTANTS & STATE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
const WALLETS    = <?= $walletsJson ?>;
const LS_VIS     = 'nf_bal_vis';    // '1' = visible, '0' = hidden
const LS_KEYPAD  = 'nf_keypad';     // '1' = use keypad, '0' = native

let state = { wallets:[], transactions:[], visible:true };
let chartInst = null;
let chartPeriod = 7;
let currentType = 'EXPENSE';
let amountRaw   = '';
let balRaw      = '';
let useKeypad   = true;
let modalBalVisible = true; // local toggle inside add modal

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  INIT FROM LOCALSTORAGE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function initPrefs() {
  const vis = localStorage.getItem(LS_VIS);
  state.visible = vis === null ? true : vis === '1';
  modalBalVisible = state.visible; // init modal visibility from global state
  document.getElementById('eyeBtn').textContent = state.visible ? 'ðŸ‘' : 'ðŸ™ˆ';

  const kp = localStorage.getItem(LS_KEYPAD);
  useKeypad = kp === null ? true : kp === '1';
  applyKeypadMode();
}

function applyKeypadMode() {
  document.getElementById('numpadWrap').style.display    = useKeypad ? 'block' : 'none';
  document.getElementById('nativeAmtWrap').style.display = useKeypad ? 'none'  : 'block';
}

// Toggle balance visibility inside modal only
function toggleModalBal() {
  modalBalVisible = !modalBalVisible;
  document.getElementById('modalEyeBtn').textContent = modalBalVisible ? 'ðŸ‘' : 'ðŸ™ˆ';
  renderWalletSelectors(); // re-render with new visibility
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  FORMAT UTILS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
const fmt  = n => Number(n).toLocaleString('id-ID');
const fmtR = n => 'Rp ' + fmt(n);
const fmtS = n => {
  if (n >= 1e9) return 'Rp '+(n/1e9).toLocaleString('id-ID',{maximumFractionDigits:1})+'M';
  if (n >= 1e6) return 'Rp '+(n/1e6).toLocaleString('id-ID',{maximumFractionDigits:1})+'jt';
  if (n >= 1e3) return 'Rp '+(n/1e3).toLocaleString('id-ID',{maximumFractionDigits:0})+'rb';
  return 'Rp '+n;
};
const fmtD = d => new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short'});
const esc  = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  LOAD DATA
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function loadData() {
  try {
    const res = await fetch('api_data.php');
    const d = await res.json();
    if (!d.success) { toast('Gagal memuat data','err'); return; }
    state.wallets = d.wallets;
    state.transactions = d.transactions;
    render();
  } catch { toast('Koneksi gagal','err'); }
}

function render() {
  renderBalance();
  renderWallets();
  renderChart();
  renderTxList();
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  BALANCE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function renderBalance() {
  const total = state.wallets.reduce((s,w)=>s+w.balance,0);
  const el = document.getElementById('totalBalance');
  el.dataset.val = fmtR(total);
  el.textContent = state.visible ? fmtR(total) : 'Rp â€¢â€¢â€¢â€¢â€¢â€¢';

  const now = new Date();
  const inc = state.transactions.filter(t=>t.type==='INCOME' &&new Date(t.date).getMonth()===now.getMonth()).reduce((s,t)=>s+t.amount,0);
  const exp = state.transactions.filter(t=>t.type==='EXPENSE'&&new Date(t.date).getMonth()===now.getMonth()).reduce((s,t)=>s+t.amount,0);
  document.getElementById('totalInc').textContent = state.visible ? fmtS(inc) : 'â€¢â€¢â€¢â€¢';
  document.getElementById('totalExp').textContent = state.visible ? fmtS(exp) : 'â€¢â€¢â€¢â€¢';
}

function toggleVis() {
  state.visible = !state.visible;
  localStorage.setItem(LS_VIS, state.visible ? '1' : '0');
  document.getElementById('eyeBtn').textContent = state.visible ? 'ðŸ‘' : 'ðŸ™ˆ';
  renderBalance();
  document.querySelectorAll('.bal-item').forEach(e => e.textContent = state.visible ? e.dataset.val : 'â€¢â€¢â€¢â€¢');
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  WALLET HELPERS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
const iMap   = { dana:'public/dana.png', gopay:'public/gopay.png', shopeepay:'public/shopeepay.png', jago:'public/jago.png' };
const iCls   = { dana:'dana', gopay:'gopay', shopeepay:'shopeepay', saving:'savings', jago:'jago', cash:'cash' };

function getWalletIcon(name) {
  const n = name.toLowerCase();
  for (const k of Object.keys(iMap)) {
    if (n.includes(k)) return `<img src="${iMap[k]}" class="wallet-icon" alt="${name}">`;
  }
  const e  = n.includes('saving') ? 'ðŸ·' : 'ðŸ’µ';
  const bg = n.includes('saving') ? '#FFF8E7' : '#F4F0FF';
  return `<div class="wallet-emoji" style="background:${bg}">${e}</div>`;
}
function getWalletCls(name) {
  const n = name.toLowerCase();
  for (const k of Object.keys(iCls)) { if (n.includes(k)) return iCls[k]; }
  return '';
}

function renderWallets() {
  const grid = document.getElementById('walletGrid');
  if (!state.wallets.length) { grid.innerHTML='<div class="empty" style="grid-column:span 3">Belum ada dompet</div>'; return; }
  grid.innerHTML = state.wallets.map(w => {
    const bal = state.visible ? fmtS(w.balance) : 'â€¢â€¢â€¢â€¢';
    return `<div class="wallet-card ${getWalletCls(w.name)}">
      <div class="wallet-edit-btn" onclick="openBalModal('${w.id}','${w.name.replace(/'/g,"\\'")}',${w.balance})">âœï¸</div>
      ${getWalletIcon(w.name)}
      <div class="wallet-name">${esc(w.name)}</div>
      <div class="wallet-bal bal-item" data-val="${fmtS(w.balance)}">${bal}</div>
    </div>`;
  }).join('');
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  CHART â€” 7 or 30 day period
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function setChartPeriod(days, btn) {
  chartPeriod = days;
  document.querySelectorAll('.chart-tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  renderChart();
}

function renderChart() {
  // Build last N days array
  const days = [];
  for (let i = chartPeriod - 1; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    days.push(d.toLocaleDateString('id-ID',{day:'numeric',month:'short'}));
  }

  const map = {};
  days.forEach(d => map[d] = {i:0, e:0});
  state.transactions.forEach(t => {
    if (t.type==='TRANSFER') return;
    const label = fmtD(t.date);
    if (!map[label]) return; // outside period
    if (t.type==='INCOME')  map[label].i += t.amount;
    if (t.type==='EXPENSE') map[label].e += t.amount;
  });

  if (chartInst) { chartInst.destroy(); chartInst=null; }
  chartInst = new Chart(document.getElementById('chart'), {
    type:'bar',
    data:{
      labels: days,
      datasets:[
        {label:'Pemasukan',   data:days.map(d=>map[d].i), backgroundColor:'#06D6A0', borderRadius:4},
        {label:'Pengeluaran', data:days.map(d=>map[d].e), backgroundColor:'#FF5757', borderRadius:4}
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{legend:{labels:{font:{family:'Inter',size:10,weight:'700'},color:'#0D0D0D',boxWidth:10,padding:10}}},
      scales:{
        x:{grid:{display:false},ticks:{font:{family:'Inter',size:chartPeriod>7?8:10},color:'#aaa',maxRotation:45},border:{display:false}},
        y:{grid:{color:'#F0F0F0'},border:{display:false},ticks:{font:{family:'Inter',size:9},color:'#aaa',callback:v=>v>=1e6?v/1e6+'jt':v>=1e3?v/1e3+'rb':v}}
      }
    }
  });
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  TX LIST
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function renderTxList() {
  const wrap = document.getElementById('txWrap');
  document.getElementById('txSpinner')?.classList.remove('show');
  if (!state.transactions.length) {
    wrap.innerHTML=`<div class="empty"><div class="empty-icon">ðŸ“­</div><div class="empty-txt">Belum ada transaksi</div><div class="empty-sub">Tekan tombol + untuk mencatat</div></div>`;
    return;
  }
  const icons = {INCOME:'ðŸ’°',EXPENSE:'ðŸ’¸',TRANSFER:'â†”ï¸'};
  const clsMap= {INCOME:'income',EXPENSE:'expense',TRANSFER:'transfer'};
  wrap.innerHTML = state.transactions.map(t=>{
    const sign   = t.type==='INCOME'?'+':t.type==='EXPENSE'?'-':'';
    const amtCls = t.type==='INCOME'?'income':t.type==='EXPENSE'?'expense':'';
    const meta   = fmtD(t.date)+' Â· '+t.walletName+(t.type==='TRANSFER'?' â†’ '+t.relatedWalletName:'');
    const badge  = (t.categoryName&&t.type!=='TRANSFER')?`<div class="tx-badge">${esc(t.categoryName)}</div>`:'';
    return `<div class="tx-item">
      <div class="tx-icon ${clsMap[t.type]}">${icons[t.type]}</div>
      <div class="tx-body">
        <div class="tx-desc">${esc(t.description)}</div>
        <div class="tx-meta">${meta}</div>${badge}
      </div>
      <div class="tx-right">
        <span class="tx-amt ${amtCls}">${sign}${fmtS(t.amount)}</span>
        <button class="btn btn-ghost btn-icon btn-xs" onclick="deleteTx('${t.id}')">ðŸ—‘</button>
      </div>
    </div>`;
  }).join('');
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  NUMPAD â€” AMOUNT
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// Single source of truth: amountRaw â†’ update display + sync native input
function updateAmtDisplay() {
  const el = document.getElementById('amountDisplay');
  const v  = parseInt(amountRaw||'0', 10);
  el.textContent = v ? fmt(v) : '0';
  el.classList.toggle('placeholder', !v);
  // Sync native input (won't trigger oninput because we set .value directly)
  if (!useKeypad) {
    const ni = document.getElementById('nativeAmt');
    if (ni) ni.value = v || '';
  }
}
function numpad(n)   { if(amountRaw.length>=13||(!amountRaw&&n===0))return; amountRaw+=String(n); updateAmtDisplay(); }
function numpadDel() { amountRaw=amountRaw.slice(0,-1); updateAmtDisplay(); }
// quickAmt is additive â€” also syncs native input via updateAmtDisplay
function quickAmt(n) { amountRaw=String(parseInt(amountRaw||'0',10)+n); updateAmtDisplay(); }
// Called by native input oninput â€” only update amountRaw & display text (avoid loop)
function syncNativeAmt(v) {
  amountRaw = v ? String(parseInt(v,10)) : '';
  const el = document.getElementById('amountDisplay');
  const num = parseInt(amountRaw||'0',10);
  el.textContent = num ? fmt(num) : '0';
  el.classList.toggle('placeholder', !num);
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  NUMPAD â€” BALANCE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function updateBalDisplay() {
  const el = document.getElementById('balDisplay');
  const v  = parseInt(balRaw||'0',10);
  el.textContent = v ? fmt(v) : '0';
  el.classList.toggle('placeholder',!v);
}
function balNumpad(n)  { if(balRaw.length>=13||(!balRaw&&n===0))return; balRaw+=String(n); updateBalDisplay(); }
function balNumpadDel(){ balRaw=balRaw.slice(0,-1); updateBalDisplay(); }
function balQuick(n)   { balRaw=String(n); updateBalDisplay(); }

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  WALLET SELECTOR (modal)
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function renderWalletSelectors() {
  // Use state.wallets (has balance) â€” fall back to WALLETS if state not loaded yet
  const source = state.wallets.length ? state.wallets : WALLETS;
  const make = (cid, hid, defaultIdx=0) => {
    const c = document.getElementById(cid);
    if (!c) return;
    c.innerHTML = source.map((w,i) => {
      const n = w.name.toLowerCase();
      let icon='';
      for (const k of Object.keys(iMap)){if(n.includes(k)){icon=`<img src="${iMap[k]}" class="wsel-emoji" alt="${w.name}">`;break;}}
      if(!icon){const e=n.includes('saving')?'ðŸ·':'ðŸ’µ';icon=`<div class="wsel-emoji">${e}</div>`;}
      // Show balance based on modal-local visibility toggle
      const balStr = (w.balance !== undefined && modalBalVisible)
        ? `<span class="wsel-bal">${fmtS(w.balance)}</span>`
        : (w.balance !== undefined ? `<span class="wsel-bal">â€¢â€¢â€¢â€¢</span>` : '');
      return `<div class="wsel-item${i===defaultIdx?' selected':''}" onclick="selectWallet('${cid}','${hid}','${w.id}',this)">${icon}<span class="wsel-name">${w.name}</span>${balStr}</div>`;
    }).join('');
    if (source[defaultIdx]) document.getElementById(hid).value = source[defaultIdx].id;
  };
  make('fromWalletSel','selectedFromWallet',0);
  make('toWalletSel','selectedToWallet', source.length>1?1:0);
}
function selectWallet(cid, hid, wId, el) {
  document.querySelectorAll('#'+cid+' .wsel-item').forEach(i=>i.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById(hid).value = wId;
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  TYPE TABS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function setType(btn) {
  currentType = btn.dataset.type;
  document.querySelectorAll('.type-tab').forEach(t=>t.classList.remove('active-expense','active-income','active-transfer'));
  btn.classList.add('active-'+currentType.toLowerCase());
  document.getElementById('toWalletBlock').style.display = currentType==='TRANSFER'?'block':'none';
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  ADD MODAL
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function openAddModal() {
  amountRaw=''; updateAmtDisplay();
  document.getElementById('descInput').value='';
  document.getElementById('addAlert').classList.remove('show');
  document.querySelectorAll('.type-tab').forEach((t,i)=>{
    t.classList.remove('active-expense','active-income','active-transfer');
    if(i===0)t.classList.add('active-expense');
  });
  currentType='EXPENSE';
  document.getElementById('toWalletBlock').style.display='none';
  if(document.getElementById('nativeAmt'))document.getElementById('nativeAmt').value='';
  applyKeypadMode();
  // Sync modal balance visibility with global state, update eye icon
  modalBalVisible = state.visible;
  document.getElementById('modalEyeBtn').textContent = modalBalVisible ? 'ðŸ‘' : 'ðŸ™ˆ';
  renderWalletSelectors();
  document.getElementById('addOverlay').classList.add('open');
}
function closeAddModal() { document.getElementById('addOverlay').classList.remove('open'); }

async function submitRecord() {
  const alertEl = document.getElementById('addAlert');
  alertEl.classList.remove('show');
  const btn = document.getElementById('addSubmitBtn');

  let amount = 0;
  if (useKeypad) {
    amount = parseInt(amountRaw||'0',10);
  } else {
    amount = parseInt(document.getElementById('nativeAmt').value||'0',10);
  }
  const desc  = document.getElementById('descInput').value.trim();
  const fromW = document.getElementById('selectedFromWallet').value;
  const toW   = document.getElementById('selectedToWallet').value;

  if (!amount||amount<=0){ alertEl.textContent='Masukkan jumlah terlebih dahulu.'; alertEl.classList.add('show'); return; }
  if (!desc)             { alertEl.textContent='Keterangan tidak boleh kosong.';   alertEl.classList.add('show'); return; }
  if (currentType==='TRANSFER'&&fromW===toW){ alertEl.textContent='Pilih dompet tujuan berbeda.'; alertEl.classList.add('show'); return; }

  btn.disabled=true; btn.textContent='Menyimpanâ€¦';
  const fd = new FormData();
  fd.append('type',currentType); fd.append('amount',amount); fd.append('description',desc); fd.append('walletId',fromW);
  if (currentType==='TRANSFER') fd.append('relatedWalletId',toW);
  try {
    const res = await fetch('transaction_actions.php?action=add_transaction',{method:'POST',body:fd});
    const d = await res.json();
    if (d.success){ closeAddModal(); toast('Transaksi disimpan âœ“','ok'); await loadData(); }
    else { alertEl.textContent=d.message; alertEl.classList.add('show'); }
  } catch { alertEl.textContent='Koneksi gagal.'; alertEl.classList.add('show'); }
  btn.disabled=false; btn.textContent='ðŸ’¾ Simpan Transaksi';
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  BALANCE MODAL
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function openBalModal(id, name, bal) {
  balRaw = bal>0 ? String(Math.round(bal)) : '';
  updateBalDisplay();
  document.getElementById('balWalletId').value=id;
  document.getElementById('balModalSub').textContent=name;
  document.getElementById('balAlert').classList.remove('show');
  document.getElementById('balOverlay').classList.add('open');
}
function closeBalModal() { document.getElementById('balOverlay').classList.remove('open'); }

async function submitBalance() {
  const alertEl=document.getElementById('balAlert'); alertEl.classList.remove('show');
  const btn=document.getElementById('balSubmitBtn');
  const balance=parseInt(balRaw||'0',10);
  const walletId=document.getElementById('balWalletId').value;
  btn.disabled=true; btn.textContent='Menyimpanâ€¦';
  const fd=new FormData(); fd.append('walletId',walletId); fd.append('balance',balance);
  try {
    const res=await fetch('transaction_actions.php?action=set_balance',{method:'POST',body:fd});
    const d=await res.json();
    if(d.success){closeBalModal();toast('Saldo diperbarui âœ“','ok');await loadData();}
    else{alertEl.textContent=d.message;alertEl.classList.add('show');}
  } catch{alertEl.textContent='Koneksi gagal.';alertEl.classList.add('show');}
  btn.disabled=false; btn.textContent='ðŸ’¾ Simpan Saldo';
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  DELETE TX
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function deleteTx(id) {
  if(!confirm('Hapus? Saldo akan dikembalikan.'))return;
  try {
    const res=await fetch('transaction_actions.php?action=delete_transaction',{method:'DELETE',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});
    const d=await res.json();
    if(d.success){toast('Dihapus');await loadData();}
    else toast(d.message,'err');
  } catch{toast('Gagal menghapus','err');}
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  TOAST
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function toast(msg, type='') {
  const c=document.getElementById('toastWrap');
  const t=document.createElement('div');
  t.className='toast'+(type?' '+type:'');
  t.textContent=msg; c.appendChild(t);
  setTimeout(()=>t.remove(),2600);
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  NAV
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function setActiveNav(id) {
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  const el=document.getElementById(id); if(el)el.classList.add('active');
}
function scrollToTop()    { window.scrollTo({top:0,behavior:'smooth'}); setActiveNav('nav-home'); }
function scrollToHistory(){ document.getElementById('txWrap').scrollIntoView({behavior:'smooth',block:'start'}); setActiveNav('nav-history'); }
function scrollToWallet() { document.getElementById('walletGrid').scrollIntoView({behavior:'smooth',block:'start'}); setActiveNav('nav-wallet'); }

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  INIT
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
initPrefs();
loadData();
</script>
</body>
</html>

