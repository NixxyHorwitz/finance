<?php
require_once 'db.php';
requireLogin();

$sw = $pdo->prepare("SELECT id, name, balance FROM Wallet WHERE userId = ? ORDER BY FIELD(name,'Cash','Dana','Gopay','ShopeePay','Bank Jago','Savings')");
$sw->execute([$_SESSION['user_id']]);
$wallets = $sw->fetchAll();
foreach ($wallets as &$w) $w['balance'] = (float)$w['balance'];
unset($w);

$walletsJson = json_encode($wallets);
$pageTitle   = 'Neofinance';
include 'src/components/_head.php';
?>
<body>

<div class="wrap">

  <!-- Header -->
  <div class="app-header">
    <div>
      <div class="brand">Neo<span>finance</span></div>
      <div class="header-sub">Halo, <?= htmlspecialchars($_SESSION['username']) ?> &#x1F44B;</div>
    </div>
  </div>

  <!-- Balance Hero -->
  <div class="balance-hero mb-2">
    <div class="balance-row">
      <div>
        <div class="balance-label">Total Saldo</div>
        <div class="balance-amt" id="totalBalance">&mdash;</div>
      </div>
      <div class="eye-btn" onclick="toggleVis()" id="eyeBtn">&#x1F441;</div>
    </div>
    <div class="balance-stats">
      <div class="stat-item">
        <div class="stat-label">&#x1F49A; Pemasukan bulan ini</div>
        <div class="stat-val" id="totalInc">&mdash;</div>
      </div>
      <div class="stat-item">
        <div class="stat-label">&#x1F534; Pengeluaran bulan ini</div>
        <div class="stat-val" id="totalExp">&mdash;</div>
      </div>
    </div>
  </div>

  <!-- Wallet Grid -->
  <div class="sec-head">
    <span class="sec-label">Dompet</span>
  </div>
  <div class="wallet-grid mb-2" id="walletGrid">
    <div class="spinner show" style="grid-column:span 3;padding:1.5rem">Memuat&#x2026;</div>
  </div>

  <!-- Chart -->
  <div class="sec-head">
    <span class="sec-label">Ringkasan</span>
    <div class="chart-tabs">
      <button class="chart-tab active" onclick="setChartPeriod(7,this)">7H</button>
      <button class="chart-tab" onclick="setChartPeriod(30,this)">30H</button>
    </div>
  </div>
  <div class="chart-box mb-2"><canvas id="myChart"></canvas></div>

  <!-- Recent Transactions -->
  <div class="sec-head mb-1">
    <span class="sec-label">Transaksi Terbaru</span>
    <a href="history" class="sec-link">Lihat Semua &#x2192;</a>
  </div>
  <div class="tx-wrap sec-card" id="txWrap">
    <div class="spinner show" id="txSpinner">Memuat&#x2026;</div>
  </div>

</div><!-- /wrap -->

<?php $navPage = 'home'; include 'src/components/_navbar.php'; ?>
<div class="toast-wrap" id="toastWrap"></div>
<?php include 'src/components/_add_modal.php'; ?>

<!-- MODAL: SET BALANCE -->
<div class="overlay" id="balOverlay" onclick="if(event.target===this)closeBalModal()">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <div>
        <div class="modal-title">Atur Saldo</div>
        <div id="balModalSub" style="font-size:0.72rem;color:var(--muted);margin-top:2px;"></div>
      </div>
      <span class="modal-close" onclick="closeBalModal()">&#x2715;</span>
    </div>
    <div class="modal-body">
      <div id="balAlert" class="alert alert-err"></div>
      <div class="amount-display mb-1">
        <div class="amount-prefix">Rp</div>
        <div class="amount-value placeholder" id="balDisplay">0</div>
      </div>
      <div class="quick-amounts mb-1">
        <button class="quick-btn" onclick="balQuick(0)">Reset</button>
        <button class="quick-btn" onclick="balQuick(100000)">100rb</button>
        <button class="quick-btn" onclick="balQuick(500000)">500rb</button>
        <button class="quick-btn" onclick="balQuick(1000000)">1jt</button>
        <button class="quick-btn" onclick="balQuick(5000000)">5jt</button>
      </div>
      <div class="numpad mb-1">
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
        <button class="nk nk-del" onclick="balNumpadDel()">&#x232B;</button>
      </div>
      <input type="hidden" id="balWalletId">
      <button class="btn btn-full btn-lg" id="balSubmitBtn" onclick="submitBalance()">
        &#x1F4BE; Simpan Saldo
      </button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="src/js/add_modal.js"></script>
<script>
const WALLETS   = <?= $walletsJson ?>;
const LS_VIS    = 'nf_bal_vis';

let state = { wallets:[], transactions:[], visible:true };
let chartInst   = null;
let chartPeriod = 7;
let balRaw      = '';

// ── Prefs ──────────────────────────────────────────────────────
function initPrefs() {
  const vis = localStorage.getItem(LS_VIS);
  state.visible = vis === null ? true : vis === '1';
  document.getElementById('eyeBtn').textContent = state.visible ? '\uD83D\uDC41' : '\uD83D\uDE48';
}

// ── Formatters ─────────────────────────────────────────────────
const fmt  = n => Number(n).toLocaleString('id-ID');
const fmtR = n => 'Rp ' + fmt(n);
const fmtS = n => {
  if (n>=1e9) return 'Rp '+(n/1e9).toLocaleString('id-ID',{maximumFractionDigits:1})+'M';
  if (n>=1e6) return 'Rp '+(n/1e6).toLocaleString('id-ID',{maximumFractionDigits:1})+'jt';
  if (n>=1e3) return 'Rp '+(n/1e3).toLocaleString('id-ID',{maximumFractionDigits:0})+'rb';
  return 'Rp '+n;
};
const fmtD = d => new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short'});
const esc  = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

// ── Load data ──────────────────────────────────────────────────
async function loadData() {
  try {
    const res = await fetch('src/api/data.php');
    const d   = await res.json();
    if (!d.success) { toast('Gagal memuat data','err'); return; }
    state.wallets      = d.wallets;
    state.transactions = d.transactions;
    renderBalance();
    renderWalletGrid();
    renderChart();
    renderTxRecent();
  } catch { toast('Koneksi gagal','err'); }
}

// ── Balance ────────────────────────────────────────────────────
function renderBalance() {
  const total = state.wallets.reduce((s,w)=>s+w.balance,0);
  const el    = document.getElementById('totalBalance');
  el.textContent = state.visible ? fmtR(total) : 'Rp \u2022\u2022\u2022\u2022\u2022\u2022';
  const now   = new Date();
  const inc   = state.transactions.filter(t=>t.type==='INCOME'&&new Date(t.date).getMonth()===now.getMonth()).reduce((s,t)=>s+t.amount,0);
  const exp   = state.transactions.filter(t=>t.type==='EXPENSE'&&new Date(t.date).getMonth()===now.getMonth()).reduce((s,t)=>s+t.amount,0);
  document.getElementById('totalInc').textContent = state.visible ? fmtS(inc) : '\u2022\u2022\u2022\u2022';
  document.getElementById('totalExp').textContent = state.visible ? fmtS(exp) : '\u2022\u2022\u2022\u2022';
  // Expose global visibility for add_modal.js
  window._globalBalVis = state.visible;
}
function toggleVis() {
  state.visible = !state.visible;
  localStorage.setItem(LS_VIS, state.visible ? '1' : '0');
  document.getElementById('eyeBtn').textContent = state.visible ? '\uD83D\uDC41' : '\uD83D\uDE48';
  window._globalBalVis = state.visible;
  renderBalance();
  document.querySelectorAll('.bal-item').forEach(e => e.textContent = state.visible ? e.dataset.val : '\u2022\u2022\u2022\u2022');
}

// ── Wallet Grid ────────────────────────────────────────────────
const iMap = { dana:'public/dana.png', gopay:'public/gopay.png', shopeepay:'public/shopeepay.png', jago:'public/jago.png' };
const iCls = { dana:'dana', gopay:'gopay', shopeepay:'shopeepay', jago:'jago', saving:'savings', cash:'cash' };
function getWalletIcon(name) {
  const n = name.toLowerCase();
  for (const k of Object.keys(iMap)) { if (n.includes(k)) return `<img src="${iMap[k]}" class="wallet-icon" alt="${name}">`; }
  const e = n.includes('saving') ? '\uD83D\uDC37' : '\uD83D\uDCB5';
  const bg = n.includes('saving') ? '#FFF8E7' : '#F4F0FF';
  return `<div class="wallet-emoji" style="background:${bg}">${e}</div>`;
}
function getWalletCls(name) {
  const n = name.toLowerCase();
  for (const k of Object.keys(iCls)) { if (n.includes(k)) return iCls[k]; }
  return '';
}
function renderWalletGrid() {
  const grid = document.getElementById('walletGrid');
  if (!state.wallets.length) { grid.innerHTML='<div class="empty" style="grid-column:span 3">Belum ada dompet</div>'; return; }
  grid.innerHTML = state.wallets.map(w => {
    const bal = state.visible ? fmtS(w.balance) : '\u2022\u2022\u2022\u2022';
    return `<div class="wallet-card ${getWalletCls(w.name)}" onclick="openBalModal('${w.id}','${w.name.replace(/'/g,"\\'")}',${w.balance})">
      <div class="wallet-edit-btn">\u270F\uFE0F</div>
      ${getWalletIcon(w.name)}
      <div class="wallet-name">${esc(w.name)}</div>
      <div class="wallet-bal bal-item" data-val="${fmtS(w.balance)}">${bal}</div>
    </div>`;
  }).join('');
}

// ── Chart ──────────────────────────────────────────────────────
function setChartPeriod(days, btn) {
  chartPeriod = days;
  document.querySelectorAll('.chart-tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  renderChart();
}
function renderChart() {
  const ctx = document.getElementById('myChart').getContext('2d');
  if (chartInst) chartInst.destroy();
  const now = new Date(); const days = [];
  for (let i=chartPeriod-1;i>=0;i--) { const d=new Date(now); d.setDate(d.getDate()-i); days.push(d.toISOString().slice(0,10)); }
  const map = {}; days.forEach(d=>{map[d]={i:0,e:0};});
  state.transactions.forEach(t=>{
    const d=t.date.slice(0,10); if(!map[d])return;
    if(t.type==='INCOME')  map[d].i+=t.amount;
    if(t.type==='EXPENSE') map[d].e+=t.amount;
  });
  const labels = days.map(d=>{const[,m,dy]=d.split('-');return dy+'/'+m;});
  chartInst = new Chart(ctx,{
    type:'bar',
    data:{labels,datasets:[
      {label:'Pemasukan', data:days.map(d=>map[d].i), backgroundColor:'#00C896', borderRadius:5},
      {label:'Pengeluaran',data:days.map(d=>map[d].e), backgroundColor:'#FF4D4D', borderRadius:5}
    ]},
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{legend:{labels:{font:{family:'Inter',size:10,weight:'800'},color:'#0D0D0D',boxWidth:10,padding:10}}},
      scales:{
        x:{grid:{display:false},ticks:{font:{family:'Inter',size:chartPeriod>7?8:10},color:'#aaa',maxRotation:45},border:{display:false}},
        y:{grid:{color:'#F0F0F0'},border:{display:false},ticks:{font:{family:'Inter',size:9},color:'#aaa',callback:v=>v>=1e6?v/1e6+'jt':v>=1e3?v/1e3+'rb':v}}
      }
    }
  });
}

// ── Recent Transactions ────────────────────────────────────────
function renderTxRecent() {
  const wrap = document.getElementById('txWrap');
  document.getElementById('txSpinner')?.classList.remove('show');
  const recent = state.transactions.slice(0, 5);
  if (!recent.length) {
    wrap.innerHTML = `<div class="empty"><div class="empty-icon">\uD83D\uDCED</div><div class="empty-txt">Belum ada transaksi</div><div class="empty-sub">Tekan + untuk mencatat</div></div>`;
    return;
  }
  const icons={INCOME:'\uD83D\uDCB0',EXPENSE:'\uD83D\uDCB8',TRANSFER:'\u2194\uFE0F'};
  const cls  ={INCOME:'income',EXPENSE:'expense',TRANSFER:'transfer'};
  wrap.innerHTML = recent.map(t=>{
    const sign   = t.type==='INCOME'?'+':t.type==='EXPENSE'?'-':'';
    const amtCls = t.type==='INCOME'?'income':t.type==='EXPENSE'?'expense':'';
    const meta   = fmtD(t.date)+' \u00B7 '+t.walletName+(t.type==='TRANSFER'?' \u2192 '+t.relatedWalletName:'');
    const badge  = (t.categoryName&&t.type!=='TRANSFER')?`<div class="tx-badge">${esc(t.categoryName)}</div>`:'';
    return `<div class="tx-item">
      <div class="tx-icon ${cls[t.type]}">${icons[t.type]}</div>
      <div class="tx-body">
        <div class="tx-desc">${esc(t.description)}</div>
        <div class="tx-meta">${meta}</div>${badge}
      </div>
      <div class="tx-right"><span class="tx-amt ${amtCls}">${sign}${fmtS(t.amount)}</span></div>
    </div>`;
  }).join('');
}

// ── Balance Modal ──────────────────────────────────────────────
function updateBalDisplay(){const el=document.getElementById('balDisplay');const v=parseInt(balRaw||'0',10);el.textContent=v?fmt(v):'0';el.classList.toggle('placeholder',!v);}
function balNumpad(n){if(balRaw.length>=13||(!balRaw&&n===0))return;balRaw+=String(n);updateBalDisplay();}
function balNumpadDel(){balRaw=balRaw.slice(0,-1);updateBalDisplay();}
function balQuick(n){balRaw=String(n);updateBalDisplay();}
function openBalModal(id,name,bal){
  balRaw=bal>0?String(Math.round(bal)):''; updateBalDisplay();
  document.getElementById('balWalletId').value=id;
  document.getElementById('balModalSub').textContent=name;
  document.getElementById('balAlert').classList.remove('show');
  document.getElementById('balOverlay').classList.add('open');
}
function closeBalModal(){document.getElementById('balOverlay').classList.remove('open');}
async function submitBalance(){
  const alertEl=document.getElementById('balAlert');alertEl.classList.remove('show');
  const btn=document.getElementById('balSubmitBtn');
  const balance=parseInt(balRaw||'0',10);
  const walletId=document.getElementById('balWalletId').value;
  btn.disabled=true;btn.textContent='Menyimpan\u2026';
  const fd=new FormData();fd.append('walletId',walletId);fd.append('balance',balance);
  try{
    const res=await fetch('src/actions/transaction.php?action=set_balance',{method:'POST',body:fd});
    const d=await res.json();
    if(d.success){closeBalModal();toast('Saldo diperbarui \u2713','ok');await loadData();}
    else{alertEl.textContent=d.message;alertEl.classList.add('show');}
  }catch{alertEl.textContent='Koneksi gagal.';alertEl.classList.add('show');}
  btn.disabled=false;btn.textContent='\uD83D\uDCBE Simpan Saldo';
}

function toast(msg,type=''){const c=document.getElementById('toastWrap');const t=document.createElement('div');t.className='toast'+(type?' '+type:'');t.textContent=msg;c.appendChild(t);setTimeout(()=>t.remove(),2600);}
function scrollToTop(){window.scrollTo({top:0,behavior:'smooth'});}

// Hook for add_modal.js after save
window._afterRecordSaved = loadData;

initPrefs();
loadData();
</script>
</body>
</html>