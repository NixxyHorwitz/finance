<?php
require_once 'db.php';
requireLogin();
$pageTitle = 'Aliran Dana – Neofinance';
include '_head.php';
?>
<body>

<div class="page-wrap">

  <div class="page-header">
    <a href="index.php" class="btn btn-ghost btn-xs">←</a>
    <div class="page-title">💸 Aliran Dana</div>
  </div>

  <!-- Period tabs -->
  <div class="chart-tabs mb-2">
    <button class="chart-tab" onclick="setPeriod(1,this)">Hari Ini</button>
    <button class="chart-tab active" onclick="setPeriod(7,this)">7 Hari</button>
    <button class="chart-tab" onclick="setPeriod(30,this)">30 Hari</button>
  </div>

  <!-- Total card -->
  <div id="flowTotalCard" class="flow-total-card">
    <div>
      <div class="flow-total-label">Total Pengeluaran</div>
      <div class="flow-total-amt" id="flowTotalAmt">Memuat…</div>
    </div>
    <div style="font-size:2.5rem;opacity:0.7">💸</div>
  </div>

  <!-- Flow diagram -->
  <div class="sec-label mb-1" style="display:block">Dompet → Kategori</div>
  <div class="flow-wrap" id="flowWrap">
    <div class="flow-col-left"  id="flowWallets"></div>
    <div class="flow-svg-mid"   id="flowSvgMid"><svg id="flowSvg"></svg></div>
    <div class="flow-col-right" id="flowCats"></div>
  </div>

  <p style="font-size:0.7rem;color:var(--muted);text-align:center;margin-bottom:1rem;">
    Tap kategori untuk lihat detailnya 👆
  </p>

  <!-- Category detail drill-down -->
  <div id="catDetailWrap"></div>

</div>

<!-- Bottom Nav -->
<nav class="bottom-nav">
  <a class="nav-item" href="index.php">
    <div class="nav-icon">🏠</div>
    <div class="nav-label">Beranda</div>
  </a>
  <a class="nav-item active" href="flow.php">
    <div class="nav-icon">📊</div>
    <div class="nav-label">Aliran</div>
  </a>
  <a class="nav-fab" href="index.php#add" onclick="sessionStorage.setItem('openAdd','1')">＋</a>
  <div class="nav-item" style="flex:1"></div>
  <a class="nav-item" href="settings.php">
    <div class="nav-icon">⚙️</div>
    <div class="nav-label">Pengaturan</div>
  </a>
</nav>

<div class="toast-wrap" id="toastWrap"></div>

<script>
let period     = 7;
let allData    = null;
let activeCat  = null;

const fmtR = n => 'Rp ' + Number(n).toLocaleString('id-ID');
const fmtS = n => {
  if(n>=1e6) return 'Rp '+(n/1e6).toLocaleString('id-ID',{maximumFractionDigits:1})+'jt';
  if(n>=1e3) return 'Rp '+(n/1e3).toLocaleString('id-ID',{maximumFractionDigits:0})+'rb';
  return 'Rp '+n;
};
const fmtD = d => new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'});
const esc  = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

const catEmoji = {
  makanan:'🍔', makan:'🍔', food:'🍔', resto:'🍔', warung:'🍔',
  transport:'🚗', bensin:'⛽', ojek:'🛵', parkir:'🅿️', grab:'🛵', gojek:'🛵',
  belanja:'🛍️', shop:'🛍️',
  hiburan:'🎮', game:'🎮', nonton:'🎬',
  kesehatan:'💊', obat:'💊', dokter:'👨‍⚕️',
  tagihan:'📄', listrik:'⚡', air:'💧', internet:'📶',
  pendidikan:'📚', buku:'📖',
  investasi:'📈',
};
function getCatEmoji(name) {
  const n = (name||'').toLowerCase();
  for (const [k,v] of Object.entries(catEmoji)) { if(n.includes(k)) return v; }
  return '📌';
}

const iMap = { dana:'public/dana.png', gopay:'public/gopay.png', shopeepay:'public/shopeepay.png' };
function getWalletEmoji(name) {
  const n=(name||'').toLowerCase();
  if(n.includes('saving'))return'🐷';
  return '💵';
}

// ─── Load ───
async function loadFlow() {
  document.getElementById('flowTotalAmt').textContent = 'Memuat…';
  document.getElementById('flowWallets').innerHTML  = '';
  document.getElementById('flowCats').innerHTML     = '';
  document.getElementById('flowSvg').innerHTML      = '';
  document.getElementById('catDetailWrap').innerHTML = '';
  activeCat = null;

  try {
    const res = await fetch('api_flow.php?days=' + period);
    allData   = await res.json();
    if (!allData.success) { toast('Gagal memuat','err'); return; }
    renderFlow();
  } catch { toast('Koneksi gagal','err'); }
}

function setPeriod(days, btn) {
  period = days;
  document.querySelectorAll('.chart-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  loadFlow();
}

// ─── Render ───
function renderFlow() {
  const expenses = allData.transactions.filter(t => t.type === 'EXPENSE');
  const total    = expenses.reduce((s,t) => s + t.amount, 0);

  document.getElementById('flowTotalAmt').textContent = fmtR(total);

  if (!expenses.length) {
    document.getElementById('flowWallets').innerHTML = '';
    document.getElementById('flowCats').innerHTML =
      '<div class="empty" style="padding:2rem;text-align:center;color:var(--muted);font-size:0.8rem;font-weight:600">Tidak ada pengeluaran dalam periode ini</div>';
    return;
  }

  // Wallet totals
  const walletMap = {};
  expenses.forEach(t => {
    if (!walletMap[t.walletId]) walletMap[t.walletId] = { id:t.walletId, name:t.walletName, amount:0 };
    walletMap[t.walletId].amount += t.amount;
  });
  const wallets = Object.values(walletMap).sort((a,b) => b.amount - a.amount);

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

  // Render wallet nodes
  document.getElementById('flowWallets').innerHTML = wallets.map((w,i) => {
    const pct = Math.round(w.amount / total * 100);
    const n   = (w.name||'').toLowerCase();
    let icon  = '';
    for (const k of Object.keys(iMap)) {
      if (n.includes(k)) { icon = `<img src="${iMap[k]}" style="width:22px;height:22px;border-radius:50%;object-fit:cover;" alt="${w.name}">`; break; }
    }
    if (!icon) icon = `<span class="flow-wn-icon">${getWalletEmoji(w.name)}</span>`;
    return `<div class="flow-wallet-node" id="fw${i}" data-wid="${w.id}">
      ${icon}
      <div class="flow-wn-name">${esc(w.name)}</div>
      <div class="flow-wn-amt">${fmtS(w.amount)}</div>
      <div class="flow-wn-pct">${pct}%</div>
    </div>`;
  }).join('');

  // Render category nodes
  document.getElementById('flowCats').innerHTML = cats.map((c,i) => {
    const pct = Math.round(c.amount / total * 100);
    return `<div class="flow-cat-node" id="fc${i}" data-cat="${esc(c.name)}" onclick="showCatDetail('${c.name.replace(/'/g,"\\'")}', ${i})">
      <span class="flow-cn-emoji">${getCatEmoji(c.name)}</span>
      <div class="flow-cn-name">${esc(c.name)}</div>
      <div class="flow-cn-amt">${fmtS(c.amount)}</div>
      <div class="flow-cn-pct">${pct}%</div>
    </div>`;
  }).join('');

  // Draw SVG lines after layout
  requestAnimationFrame(() => {
    setTimeout(() => drawLines(pairs, wallets, cats, total), 80);
  });
}

// ─── SVG Lines ───
function drawLines(pairs, wallets, cats, total) {
  const svg    = document.getElementById('flowSvg');
  const midEl  = document.getElementById('flowSvgMid');
  const midRect= midEl.getBoundingClientRect();
  const scrollY= window.scrollY || document.documentElement.scrollTop;

  // set SVG height to match wrapper
  const wrapH = document.getElementById('flowWrap').offsetHeight;
  svg.setAttribute('viewBox', `0 0 48 ${wrapH}`);
  svg.setAttribute('height', wrapH);
  midEl.style.height = wrapH + 'px';

  svg.innerHTML = '';

  pairs.forEach(p => {
    const wIdx = wallets.findIndex(w => w.id === p.walletId);
    const cIdx = cats.findIndex(c => c.name === p.cat);
    if (wIdx < 0 || cIdx < 0) return;

    const wEl = document.getElementById('fw' + wIdx);
    const cEl = document.getElementById('fc' + cIdx);
    if (!wEl || !cEl) return;

    const wRect = wEl.getBoundingClientRect();
    const cRect = cEl.getBoundingClientRect();

    // Convert to SVG coordinate space (relative to midEl)
    const x1 = 0;
    const y1  = (wRect.top + wRect.height/2 + scrollY) - (midRect.top + scrollY);
    const x2  = 48;
    const y2  = (cRect.top + cRect.height/2 + scrollY) - (midRect.top + scrollY);

    const strokeW = Math.max(1.5, (p.amount / total) * 16);
    const opacity = 0.25 + (p.amount / total) * 0.6;

    const path = document.createElementNS('http://www.w3.org/2000/svg','path');
    path.setAttribute('d', `M 0 ${y1} C 24 ${y1}, 24 ${y2}, 48 ${y2}`);
    path.setAttribute('stroke', '#4361EE');
    path.setAttribute('stroke-width', strokeW);
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke-opacity', Math.min(0.85, opacity));
    path.setAttribute('stroke-linecap', 'round');
    svg.appendChild(path);
  });
}

// ─── Category detail ───
function showCatDetail(catName, idx) {
  if (activeCat === catName) { closeCatDetail(); return; }
  activeCat = catName;

  document.querySelectorAll('.flow-cat-node').forEach(n => n.classList.remove('active'));
  const el = document.getElementById('fc' + idx);
  if (el) el.classList.add('active');

  const cat = Object.values({}).constructor.name; // dummy
  const txs = allData.transactions.filter(t =>
    t.type === 'EXPENSE' && (t.categoryName || 'Lainnya') === catName
  );
  const catTotal = txs.reduce((s,t) => s+t.amount, 0);

  const wrap = document.getElementById('catDetailWrap');
  wrap.innerHTML = `
    <div class="cat-detail-panel">
      <div class="cat-detail-head">
        <div>
          <div class="cat-detail-title">${getCatEmoji(catName)} ${esc(catName)}</div>
          <div class="cat-detail-sub">${txs.length} transaksi · Total ${fmtR(catTotal)}</div>
        </div>
        <button class="btn btn-ghost btn-xs" onclick="closeCatDetail()">✕</button>
      </div>
      ${txs.map(t => `
        <div class="tx-item">
          <div class="tx-icon expense">💸</div>
          <div class="tx-body">
            <div class="tx-desc">${esc(t.description)}</div>
            <div class="tx-meta">${fmtD(t.date)} · ${esc(t.walletName)}</div>
          </div>
          <div class="tx-right">
            <span class="tx-amt expense">-${fmtS(t.amount)}</span>
          </div>
        </div>
      `).join('')}
    </div>`;

  wrap.scrollIntoView({ behavior:'smooth', block:'nearest' });
}

function closeCatDetail() {
  activeCat = null;
  document.querySelectorAll('.flow-cat-node').forEach(n => n.classList.remove('active'));
  document.getElementById('catDetailWrap').innerHTML = '';
}

function toast(msg, type='') {
  const c = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = 'toast'+(type?' '+type:'');
  t.textContent = msg; c.appendChild(t);
  setTimeout(() => t.remove(), 2600);
}

// Redraw lines on resize
window.addEventListener('resize', () => {
  if (allData) renderFlow();
});

loadFlow();
</script>
</body>
</html>
