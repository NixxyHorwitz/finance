<?php
require_once 'db.php';
requireLogin();
$pageTitle = 'Aliran Dana';
$navPage   = 'flow';
include 'src/components/_head.php';
?>
<style>
/* === FLOW PAGE EXTRAS === */
.flow-page { max-width:600px; margin:0 auto; padding:0.875rem 0.875rem calc(var(--nav-h) + 2rem); }

.flow-header { display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem; }
.flow-back   { display:inline-flex; align-items:center; gap:0.35rem; text-decoration:none;
               font-size:0.75rem; font-weight:800; color:var(--muted); padding:0.3rem 0.6rem;
               border:2px solid var(--border); border-radius:var(--r-xs); background:var(--surface);
               box-shadow:1px 1px 0 var(--border); transition:all 0.08s; }
.flow-back:hover { transform:translate(-1px,-1px); box-shadow:2px 2px 0 var(--border); }
.flow-page-title { font-size:1.1rem; font-weight:900; letter-spacing:-0.04em; }

/* Period pills */
.period-bar { display:flex; gap:0.4rem; margin-bottom:1rem; }
.period-btn { padding:0.32rem 0.75rem; border:2px solid var(--border); border-radius:100px;
              font-family:inherit; font-size:0.7rem; font-weight:800; cursor:pointer;
              background:var(--surface); color:var(--muted); transition:all 0.1s;
              -webkit-tap-highlight-color:transparent; }
.period-btn.active { background:var(--text); color:#fff; }

/* Total card */
.flow-total { display:flex; align-items:center; justify-content:space-between;
              background:linear-gradient(135deg,#FF4D4D,#FF7A35);
              border:2px solid var(--border); border-radius:var(--r); box-shadow:var(--sh);
              padding:1rem 1.25rem; margin-bottom:1.25rem; color:#fff; }
.flow-total-label { font-size:0.6rem; font-weight:800; text-transform:uppercase;
                    letter-spacing:0.08em; opacity:0.75; margin-bottom:0.2rem; }
.flow-total-amt   { font-size:1.6rem; font-weight:900; letter-spacing:-0.05em; }
.flow-total-icon  { font-size:2.4rem; opacity:0.8; }

/* Wallet legend */
.wallet-legend { display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem; }
.legend-item { display:flex; align-items:center; gap:0.4rem; padding:0.28rem 0.65rem;
               border-radius:100px; border:2px solid; font-size:0.68rem; font-weight:800; }
.legend-dot  { width:8px; height:8px; border-radius:50%; flex-shrink:0; }

/* Flow canvas area */
.flow-canvas { background:var(--surface); border:2px solid var(--border); border-radius:var(--r);
               box-shadow:var(--sh-s); overflow:hidden; margin-bottom:1rem; }
.flow-inner  { display:grid; grid-template-columns:1fr 60px 1fr; align-items:start;
               padding:1rem 0; min-height:180px; }
.flow-col    { display:flex; flex-direction:column; gap:0.5rem; padding:0 0.5rem; }
.flow-mid    { position:relative; align-self:stretch; }
.flow-mid svg { position:absolute; top:0; left:0; width:100%; height:100%; overflow:visible; pointer-events:none; }

/* Wallet nodes */
.flow-wnode  { display:flex; align-items:center; gap:0.5rem; padding:0.5rem 0.65rem;
               border:2px solid; border-radius:100px; position:relative; z-index:2;
               transition:transform 0.1s; cursor:default; }
.flow-wnode:hover { transform:translate(-1px,-1px); }
.flow-wnode-icon { width:22px; height:22px; border-radius:50%; object-fit:cover; flex-shrink:0; }
.flow-wnode-emoji{ width:22px; height:22px; border-radius:50%; display:flex; align-items:center;
                   justify-content:center; font-size:12px; flex-shrink:0; }
.flow-wnode-info { min-width:0; }
.flow-wnode-name { font-size:0.6rem; font-weight:900; text-transform:uppercase; letter-spacing:0.04em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.flow-wnode-amt  { font-size:0.68rem; font-weight:900; }
.flow-wnode-pct  { font-size:0.56rem; font-weight:700; opacity:0.65; }

/* Category nodes */
.flow-cnode  { display:flex; flex-direction:column; align-items:center; gap:0.15rem;
               padding:0.55rem 0.45rem; background:var(--bg); border:2px solid var(--border);
               border-radius:10px; box-shadow:2px 2px 0 var(--border);
               position:relative; z-index:2; text-align:center; cursor:pointer;
               transition:all 0.12s; -webkit-tap-highlight-color:transparent; }
.flow-cnode:hover  { transform:translate(-1px,-1px); box-shadow:3px 3px 0 var(--border); background:var(--yellow); }
.flow-cnode.active { background:var(--yellow); transform:translate(-1px,-1px); }
.flow-cnode-emoji  { font-size:17px; line-height:1; }
.flow-cnode-name   { font-size:0.56rem; font-weight:900; text-transform:uppercase; letter-spacing:0.04em; color:#555; }
.flow-cnode-amt    { font-size:0.7rem; font-weight:900; }
.flow-cnode-pct    { font-size:0.54rem; font-weight:700; color:var(--muted); }

/* Empty state */
.flow-empty { text-align:center; padding:3rem 1rem; color:var(--muted); }

/* Category detail */
.cat-detail { background:var(--surface); border:2px solid var(--border); border-radius:var(--r);
              box-shadow:var(--sh-s); overflow:hidden; margin-bottom:1rem;
              animation:slideDown .2s ease; }
@keyframes slideDown { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.cat-detail-head { display:flex; align-items:center; justify-content:space-between;
                   padding:0.75rem 1rem; background:var(--yellow); border-bottom:2px solid var(--border); }
.cat-detail-title{ font-size:0.88rem; font-weight:900; }
.cat-detail-sub  { font-size:0.68rem; color:#555; margin-top:1px; }

/* Animated SVG lines */
@keyframes flowDash { to { stroke-dashoffset: -14; } }
.flow-line { stroke-dasharray: 8 6; animation: flowDash 1.5s linear infinite; }
</style>
<body>

<div class="flow-page">

  <div class="flow-header">
    <a href="/" class="flow-back">&#x2190; Beranda</a>
    <div class="flow-page-title">&#x1F4B8; Aliran Dana</div>
  </div>

  <!-- Period -->
  <div class="period-bar">
    <button class="period-btn" onclick="setPeriod(1,this)">Hari Ini</button>
    <button class="period-btn active" onclick="setPeriod(7,this)">7 Hari</button>
    <button class="period-btn" onclick="setPeriod(30,this)">30 Hari</button>
  </div>

  <!-- Total -->
  <div class="flow-total" id="flowTotal">
    <div>
      <div class="flow-total-label">Total Pengeluaran</div>
      <div class="flow-total-amt" id="flowTotalAmt">Memuat&#x2026;</div>
    </div>
    <div class="flow-total-icon">&#x1F4B8;</div>
  </div>

  <!-- Wallet color legend -->
  <div class="wallet-legend" id="walletLegend"></div>

  <!-- Flow canvas -->
  <div class="flow-canvas" id="flowCanvas">
    <div class="flow-inner" id="flowInner">
      <div class="flow-col" id="flowWallets"></div>
      <div class="flow-mid"  id="flowMid"><svg id="flowSvg"></svg></div>
      <div class="flow-col" id="flowCats"></div>
    </div>
  </div>

  <p style="font-size:0.68rem;color:var(--muted);text-align:center;margin-bottom:1rem">
    Tap kategori untuk lihat detail transaksi &#x1F446;
  </p>

  <!-- Category detail drill-down -->
  <div id="catDetail"></div>

</div>

<?php $navPage = 'flow'; include 'src/components/_navbar.php'; ?>
<div class="toast-wrap" id="toastWrap"></div>
<?php include 'src/components/_add_modal.php'; ?>
<script src="src/js/add_modal.js"></script>

<script>
// ── Wallet color palette (vivid, distinct) ─────────────────────────
const WCOLORS = ['#4361EE','#FF6B35','#00C896','#FF4D4D','#9B2FBE','#F5A623','#06B6D4','#E91E8C'];
const WLIGHTS = ['#EEF1FF','#FFF0E8','#E6FFF8','#FFE8E8','#F5EEFF','#FFF8E0','#E0F7FA','#FCE4EC'];
const iMap    = { dana:'public/dana.png', gopay:'public/gopay.png', shopeepay:'public/shopeepay.png', jago:'public/jago.png' };

const catEmoji = {
  'makanan':'\uD83C\uDF5C', 'food':'\uD83C\uDF5C', 'makan':'\uD83C\uDF5C', 'warung':'\uD83C\uDF5C', 'resto':'\uD83C\uDF5C',
  'transport':'\uD83D\uDE97', 'bensin':'\u26FD', 'ojek':'\uD83D\uDEF5', 'grab':'\uD83D\uDEF5', 'gojek':'\uD83D\uDEF5',
  'belanja':'\uD83D\uDED2', 'shop':'\uD83D\uDED2',
  'hiburan':'\uD83C\uDFAE', 'game':'\uD83C\uDFAE', 'nonton':'\uD83C\uDFAC',
  'kesehatan':'\uD83D\uDC8A', 'obat':'\uD83D\uDC8A', 'dokter':'\uD83D\uDC68\u200D\u2695\uFE0F',
  'tagihan':'\uD83D\uDCC4', 'listrik':'\u26A1', 'internet':'\uD83D\uDCF6',
  'pendidikan':'\uD83D\uDCDA', 'kecantikan':'\uD83D\uDC84',
  'investasi':'\uD83D\uDCC8', 'perjalanan':'\u2708\uFE0F', 'hotel':'\uD83C\uDFE8',
  'sosial':'\uD83C\uDF81', 'hadiah':'\uD83C\uDF81', 'donasi':'\uD83E\uDD1D',
  'lainnya':'\uD83D\uDCCC',
};
function getCatEmoji(name) {
  const n = (name || '').toLowerCase();
  for (const [k, v] of Object.entries(catEmoji)) { if (n.includes(k)) return v; }
  return '\uD83D\uDCCC';
}

const fmtR = n => 'Rp ' + Number(n).toLocaleString('id-ID');
const fmtS = n => {
  if (n>=1e6) return 'Rp '+(n/1e6).toLocaleString('id-ID',{maximumFractionDigits:1})+'jt';
  if (n>=1e3) return 'Rp '+(n/1e3).toLocaleString('id-ID',{maximumFractionDigits:0})+'rb';
  return 'Rp '+n;
};
const fmtD  = d => new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'});
const esc   = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
const $     = id => document.getElementById(id);

let period   = 7;
let allData  = null;
let activeCat= null;
let walletColorMap = {}; // walletId → color index

// ── Load ──────────────────────────────────────────────────────────
async function loadFlow() {
  $('flowTotalAmt').textContent = 'Memuat\u2026';
  $('walletLegend').innerHTML   = '';
  $('flowWallets').innerHTML    = '';
  $('flowCats').innerHTML       = '';
  $('flowSvg').innerHTML        = '';
  $('catDetail').innerHTML      = '';
  activeCat = null;
  try {
    const res = await fetch('src/api/flow.php?days=' + period);
    allData   = await res.json();
    if (!allData.success) { toast('Gagal memuat','err'); return; }
    renderFlow();
  } catch { toast('Koneksi gagal','err'); }
}

function setPeriod(days, btn) {
  period = days;
  document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  loadFlow();
}

// ── Render ────────────────────────────────────────────────────────
function renderFlow() {
  const expenses = allData.transactions.filter(t => t.type === 'EXPENSE');
  const total    = expenses.reduce((s, t) => s + t.amount, 0);
  $('flowTotalAmt').textContent = fmtR(total);

  if (!expenses.length) {
    $('flowWallets').innerHTML = '';
    $('flowCats').innerHTML    = `<div class="flow-empty">\uD83D\uDCED<br>Tidak ada pengeluaran</div>`;
    return;
  }

  // Wallet totals + color assignment
  const walletMap = {};
  expenses.forEach(t => {
    if (!walletMap[t.walletId]) walletMap[t.walletId] = { id:t.walletId, name:t.walletName, amount:0 };
    walletMap[t.walletId].amount += t.amount;
  });
  const wallets = Object.values(walletMap).sort((a,b) => b.amount - a.amount);
  wallets.forEach((w, i) => { walletColorMap[w.id] = i % WCOLORS.length; });

  // Category totals
  const catMap = {};
  expenses.forEach(t => {
    const cat = t.categoryName || 'Lainnya';
    if (!catMap[cat]) catMap[cat] = { name:cat, amount:0, txs:[] };
    catMap[cat].amount += t.amount;
    catMap[cat].txs.push(t);
  });
  const cats = Object.values(catMap).sort((a,b) => b.amount - a.amount);

  // Wallet→Category pairs
  const pairMap = {};
  expenses.forEach(t => {
    const cat = t.categoryName || 'Lainnya';
    const key = t.walletId + '||' + cat;
    if (!pairMap[key]) pairMap[key] = { walletId:t.walletId, cat, amount:0 };
    pairMap[key].amount += t.amount;
  });
  const pairs = Object.values(pairMap);

  // Legend
  $('walletLegend').innerHTML = wallets.map(w => {
    const ci = walletColorMap[w.id]; const c = WCOLORS[ci]; const cl = WLIGHTS[ci];
    return `<div class="legend-item" style="border-color:${c};background:${cl};color:${c}">
      <div class="legend-dot" style="background:${c}"></div>${esc(w.name)}
    </div>`;
  }).join('');

  // Wallet nodes (left column)
  $('flowWallets').innerHTML = wallets.map((w, i) => {
    const ci = walletColorMap[w.id]; const c = WCOLORS[ci]; const cl = WLIGHTS[ci];
    const pct = Math.round(w.amount / total * 100);
    const n   = (w.name || '').toLowerCase();
    let icon  = '';
    for (const k of Object.keys(iMap)) {
      if (n.includes(k)) { icon = `<img src="${iMap[k]}" class="flow-wnode-icon" alt="">`; break; }
    }
    if (!icon) icon = `<div class="flow-wnode-emoji" style="background:${cl}">${n.includes('saving')?'\uD83D\uDC37':'\uD83D\uDCB5'}</div>`;
    return `<div class="flow-wnode" id="fw${i}" style="border-color:${c};background:${cl}">
      ${icon}
      <div class="flow-wnode-info">
        <div class="flow-wnode-name" style="color:${c}">${esc(w.name)}</div>
        <div class="flow-wnode-amt" style="color:${c}">${fmtS(w.amount)}</div>
        <div class="flow-wnode-pct" style="color:${c}">${pct}%</div>
      </div>
    </div>`;
  }).join('');

  // Category nodes (right column)
  $('flowCats').innerHTML = cats.map((c, i) => {
    const pct = Math.round(c.amount / total * 100);
    return `<div class="flow-cnode" id="fc${i}" onclick="showCatDetail('${c.name.replace(/'/g,"\\'")}',${i})">
      <div class="flow-cnode-emoji">${getCatEmoji(c.name)}</div>
      <div class="flow-cnode-name">${esc(c.name)}</div>
      <div class="flow-cnode-amt">${fmtS(c.amount)}</div>
      <div class="flow-cnode-pct">${pct}%</div>
    </div>`;
  }).join('');

  // Draw SVG lines with stagger
  requestAnimationFrame(() => {
    setTimeout(() => drawAnimatedLines(pairs, wallets, cats, total), 80);
  });
}

// ── SVG animated colored lines ─────────────────────────────────────
function drawAnimatedLines(pairs, wallets, cats, total) {
  const svg    = $('flowSvg');
  const midEl  = $('flowMid');
  const midRect= midEl.getBoundingClientRect();
  const scrollY= window.scrollY || document.documentElement.scrollTop;

  const wrapH  = $('flowInner').offsetHeight;
  svg.setAttribute('viewBox', `0 0 60 ${wrapH}`);
  svg.setAttribute('height', wrapH);
  midEl.style.height = wrapH + 'px';
  svg.innerHTML = '';

  // Group pairs by wallet for stagger
  let pathIdx = 0;

  pairs.forEach(p => {
    const wIdx = wallets.findIndex(w => w.id === p.walletId);
    const cIdx = cats.findIndex(c => c.name === p.cat);
    if (wIdx < 0 || cIdx < 0) return;

    const wEl = $('fw' + wIdx);
    const cEl = $('fc' + cIdx);
    if (!wEl || !cEl) return;

    const wR = wEl.getBoundingClientRect();
    const cR = cEl.getBoundingClientRect();

    const y1 = (wR.top + wR.height/2 + scrollY) - (midRect.top + scrollY);
    const y2 = (cR.top + cR.height/2 + scrollY) - (midRect.top + scrollY);

    const ci       = walletColorMap[p.walletId] ?? 0;
    const color    = WCOLORS[ci];
    const ratio    = p.amount / total;
    const strokeW  = Math.max(1.5, Math.min(12, ratio * 22));
    const opacity  = Math.max(0.35, Math.min(0.85, 0.3 + ratio * 0.9));

    // Path element
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', `M 0 ${y1} C 30 ${y1}, 30 ${y2}, 60 ${y2}`);
    path.setAttribute('stroke', color);
    path.setAttribute('stroke-width', strokeW);
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke-opacity', opacity);
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('class', 'flow-line');
    path.style.animationDuration = (1.2 + (pathIdx % 4) * 0.15) + 's';
    path.style.animationDelay    = (pathIdx * 0.06) + 's';

    // For thicker lines, use proportional dash
    const dash = Math.max(5, strokeW); const gap = Math.max(4, strokeW * 0.6);
    path.setAttribute('stroke-dasharray', `${dash} ${gap}`);
    // Override animation keyframe to match dash sum
    path.style.setProperty('--dash-sum', dash + gap);

    svg.appendChild(path);
    pathIdx++;
  });
}

// ── Category detail ───────────────────────────────────────────────
function showCatDetail(catName, idx) {
  if (activeCat === catName) { closeCatDetail(); return; }
  activeCat = catName;
  document.querySelectorAll('.flow-cnode').forEach(n => n.classList.remove('active'));
  const el = $('fc' + idx); if (el) el.classList.add('active');

  const txs      = allData.transactions.filter(t => t.type === 'EXPENSE' && (t.categoryName || 'Lainnya') === catName);
  const catTotal = txs.reduce((s,t) => s+t.amount, 0);
  const icons    = {INCOME:'\uD83D\uDCB0',EXPENSE:'\uD83D\uDCB8',TRANSFER:'\u2194\uFE0F'};

  $('catDetail').innerHTML = `
    <div class="cat-detail">
      <div class="cat-detail-head">
        <div>
          <div class="cat-detail-title">${getCatEmoji(catName)} ${esc(catName)}</div>
          <div class="cat-detail-sub">${txs.length} transaksi \u00B7 Total ${fmtR(catTotal)}</div>
        </div>
        <button class="btn btn-ghost btn-xs" onclick="closeCatDetail()">\u2715</button>
      </div>
      ${txs.map(t => {
        const ci = walletColorMap[t.walletId] ?? 0;
        const c  = WCOLORS[ci]; const cl = WLIGHTS[ci];
        return `<div class="tx-item">
          <div class="tx-icon expense" style="background:${cl};border-color:${c}">\uD83D\uDCB8</div>
          <div class="tx-body">
            <div class="tx-desc">${esc(t.description)}</div>
            <div class="tx-meta">${fmtD(t.date)} \u00B7
              <span style="color:${c};font-weight:700">${esc(t.walletName)}</span>
            </div>
          </div>
          <div class="tx-right">
            <span class="tx-amt expense">-${fmtS(t.amount)}</span>
          </div>
        </div>`;
      }).join('')}
    </div>`;

  $('catDetail').scrollIntoView({ behavior:'smooth', block:'nearest' });
}

function closeCatDetail() {
  activeCat = null;
  document.querySelectorAll('.flow-cnode').forEach(n => n.classList.remove('active'));
  $('catDetail').innerHTML = '';
}

function toast(msg, type='') {
  const c=document.getElementById('toastWrap'); const t=document.createElement('div');
  t.className='toast'+(type?' '+type:''); t.textContent=msg; c.appendChild(t);
  setTimeout(()=>t.remove(),2600);
}

window.addEventListener('resize', () => { if (allData) renderFlow(); });

// After saving a record via modal, reload flow
window._afterRecordSaved = loadFlow;

loadFlow();
</script>
</body>
</html>