<?php
require_once 'db.php';
requireLogin();
$pageTitle = 'Riwayat Transaksi &ndash; Neofinance';
include 'src/components/_head.php';
?>
<body>

<div class="page-wrap">

  <div class="page-header">
    <a href="/" class="btn btn-ghost btn-xs">&#x2190;</a>
    <div class="page-title">&#x1F4CB; Riwayat</div>
  </div>

  <!-- Filters: Type -->
  <div class="hist-filters mb-2" id="typeFilters">
    <button class="hist-filter-btn active" data-val="ALL" onclick="setFilter('type','ALL',this)">Semua</button>
    <button class="hist-filter-btn" data-val="EXPENSE" onclick="setFilter('type','EXPENSE',this)">&#x1F4B8; Keluar</button>
    <button class="hist-filter-btn" data-val="INCOME" onclick="setFilter('type','INCOME',this)">&#x1F4B0; Masuk</button>
    <button class="hist-filter-btn" data-val="TRANSFER" onclick="setFilter('type','TRANSFER',this)">&#x2194;&#xFE0F; Transfer</button>
  </div>

  <!-- Filters: Period -->
  <div class="hist-filters mb-2" id="periodFilters">
    <button class="hist-filter-btn" data-val="7" onclick="setFilter('period','7',this)">7 Hari</button>
    <button class="hist-filter-btn" data-val="30" onclick="setFilter('period','30',this)">30 Hari</button>
    <button class="hist-filter-btn" data-val="90" onclick="setFilter('period','90',this)">90 Hari</button>
    <button class="hist-filter-btn active" data-val="ALL" onclick="setFilter('period','ALL',this)">Semua</button>
  </div>

  <!-- Search -->
  <div class="hist-search mb-2">
    <span class="hist-search-icon">&#x1F50D;</span>
    <input type="search" id="searchInput" placeholder="Cari transaksi&#x2026;" oninput="onSearch(this.value)">
  </div>

  <!-- Summary Bar -->
  <div class="hist-summary mb-2" id="histSummary">
    <span class="hist-summary-total" id="summaryTotal">Memuat&#x2026;</span>
    <span class="hist-summary-income" id="summaryIncome"></span>
    <span class="hist-summary-expense" id="summaryExpense"></span>
  </div>

  <!-- List -->
  <div class="tx-wrap" id="histWrap">
    <div class="spinner show" id="histSpinner">Memuat transaksi&#x2026;</div>
  </div>

  <!-- Pagination -->
  <div class="hist-pagination" id="histPagination"></div>

</div>

<!-- Bottom Nav -->
<?php $navPage = 'history'; include 'src/components/_navbar.php'; ?>

<div class="toast-wrap" id="toastWrap"></div>

<?php include 'src/components/_add_modal.php'; ?>
    <div class="modal-head">
      <span class="modal-title">&#x270F;&#xFE0F; Catat Transaksi</span>
      <span class="modal-close" onclick="closeAddModal()">&#x2715;</span>
    </div>
    <div class="modal-body">
      <div id="addAlert" class="alert alert-err"></div>
      <div class="type-tabs mb-1">
        <button class="type-tab active-expense" data-type="EXPENSE" onclick="setType(this)"><span class="tab-icon">&#x1F4B8;</span>Keluar</button>
        <button class="type-tab" data-type="INCOME" onclick="setType(this)"><span class="tab-icon">&#x1F4B0;</span>Masuk</button>
        <button class="type-tab" data-type="TRANSFER" onclick="setType(this)"><span class="tab-icon">&#x2194;&#xFE0F;</span>Transfer</button>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.4rem">
        <div class="wallet-selector-label" style="margin-bottom:0">Dari Dompet</div>
        <div class="mini-eye-btn" id="modalEyeBtn" onclick="toggleModalBal()">&#x1F441;</div>
      </div>
      <div class="wallet-selector" id="fromWalletSel"></div>
      <input type="hidden" id="selectedFromWallet">
      <div id="toWalletBlock" style="display:none">
        <div class="wallet-selector-label">Ke Dompet</div>
        <div class="wallet-selector" id="toWalletSel"></div>
        <input type="hidden" id="selectedToWallet">
      </div>
      <div class="amount-display mb-1">
        <div class="amount-prefix">Rp</div>
        <div class="amount-value placeholder" id="amountDisplay">0</div>
      </div>
      <div class="quick-amounts mb-1">
        <button class="quick-btn" onclick="quickAmt(10000)">+10rb</button>
        <button class="quick-btn" onclick="quickAmt(20000)">+20rb</button>
        <button class="quick-btn" onclick="quickAmt(50000)">+50rb</button>
        <button class="quick-btn" onclick="quickAmt(100000)">+100rb</button>
        <button class="quick-btn" onclick="quickAmt(200000)">+200rb</button>
        <button class="quick-btn" onclick="quickAmt(500000)">+500rb</button>
      </div>
      <div id="numpadWrap">
        <div class="numpad">
          <button class="nk" onclick="numpad(1)">1</button><button class="nk" onclick="numpad(2)">2</button><button class="nk" onclick="numpad(3)">3</button>
          <button class="nk" onclick="numpad(4)">4</button><button class="nk" onclick="numpad(5)">5</button><button class="nk" onclick="numpad(6)">6</button>
          <button class="nk" onclick="numpad(7)">7</button><button class="nk" onclick="numpad(8)">8</button><button class="nk" onclick="numpad(9)">9</button>
          <button class="nk nk-0" onclick="numpad(0)">0</button>
          <button class="nk nk-del" onclick="numpadDel()">&#x232B;</button>
        </div>
      </div>
      <div id="nativeAmtWrap" style="display:none">
        <div class="fgroup mb-1">
          <label>Jumlah (Rp)</label>
          <input type="number" id="nativeAmt" min="0" step="1000" placeholder="0" oninput="syncNativeAmt(this.value)">
        </div>
      </div>
      <div class="fgroup mb-1">
        <label>Keterangan <span style="font-weight:500;text-transform:none;letter-spacing:0;color:var(--muted)">(auto-kategori &#x1FAA4;)</span></label>
        <input type="text" id="descInput" placeholder="contoh: makan siang, bensin&#x2026;">
      </div>
      <button class="btn btn-full btn-lg" id="addSubmitBtn" onclick="submitRecord()">&#x1F4BE; Simpan Transaksi</button>
    </div>
  </div>
</div>

<script src="src/js/add_modal.js"></script>
<script>
const LS_VIS    = 'nf_bal_vis';
const LS_KEYPAD = 'nf_keypad';
const PAGE_SIZE = 20;

let allTx     = [];
let wallets   = [];
let filtered  = [];
let page      = 1;
let filters   = { type:'ALL', period:'ALL', q:'' };
let currentType = 'EXPENSE';
let amountRaw   = '';
let useKeypad   = true;
let modalBalVisible = true;
let visState    = true;

function initPrefs() {
  const vis = localStorage.getItem(LS_VIS);
  visState = vis === null ? true : vis === '1';
  modalBalVisible = visState;
  const kp = localStorage.getItem(LS_KEYPAD);
  useKeypad = kp === null ? true : kp === '1';
  applyKeypadMode();
}
function applyKeypadMode() {
  document.getElementById('numpadWrap').style.display    = useKeypad ? 'block' : 'none';
  document.getElementById('nativeAmtWrap').style.display = useKeypad ? 'none'  : 'block';
}
function toggleModalBal() {
  modalBalVisible = !modalBalVisible;
  document.getElementById('modalEyeBtn').textContent = modalBalVisible ? '\uD83D\uDC41' : '\uD83D\uDE48';
  renderWalletSelectors();
}

const fmt  = n => Number(n).toLocaleString('id-ID');
const fmtR = n => 'Rp ' + fmt(n);
const fmtS = n => {
  if(n>=1e9) return 'Rp '+(n/1e9).toLocaleString('id-ID',{maximumFractionDigits:1})+'M';
  if(n>=1e6) return 'Rp '+(n/1e6).toLocaleString('id-ID',{maximumFractionDigits:1})+'jt';
  if(n>=1e3) return 'Rp '+(n/1e3).toLocaleString('id-ID',{maximumFractionDigits:0})+'rb';
  return 'Rp '+n;
};
const fmtD = d => new Date(d).toLocaleDateString('id-ID',{weekday:'short',day:'numeric',month:'short',year:'numeric'});
const fmtDShort = d => new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'});
const esc  = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

async function loadData() {
  try {
    const res = await fetch('src/api/data.php');
    const d   = await res.json();
    if (!d.success) { toast('Gagal memuat','err'); return; }
    allTx   = d.transactions;
    wallets = d.wallets;
    document.getElementById('histSpinner').classList.remove('show');
    applyFilters();
  } catch { toast('Koneksi gagal','err'); }
}

// --- Filters ---
function setFilter(key, val, btn) {
  filters[key] = val;
  page = 1;
  const group = key === 'type' ? 'typeFilters' : 'periodFilters';
  document.querySelectorAll(`#${group} .hist-filter-btn`).forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
function onSearch(q) {
  filters.q = q.trim().toLowerCase();
  page = 1;
  applyFilters();
}

function applyFilters() {
  const now = new Date();
  filtered = allTx.filter(t => {
    if (filters.type !== 'ALL' && t.type !== filters.type) return false;
    if (filters.period !== 'ALL') {
      const days = parseInt(filters.period, 10);
      const cutoff = new Date(now); cutoff.setDate(cutoff.getDate() - days);
      if (new Date(t.date) < cutoff) return false;
    }
    if (filters.q) {
      const hay = (t.description+' '+(t.categoryName||'')+' '+t.walletName).toLowerCase();
      if (!hay.includes(filters.q)) return false;
    }
    return true;
  });
  updateSummary();
  renderList();
}

function updateSummary() {
  const inc = filtered.filter(t=>t.type==='INCOME').reduce((s,t)=>s+t.amount,0);
  const exp = filtered.filter(t=>t.type==='EXPENSE').reduce((s,t)=>s+t.amount,0);
  document.getElementById('summaryTotal').textContent = filtered.length + ' transaksi';
  document.getElementById('summaryIncome').textContent = inc ? '+'+fmtS(inc) : '';
  document.getElementById('summaryExpense').textContent = exp ? '-'+fmtS(exp) : '';
}

function renderList() {
  const wrap = document.getElementById('histWrap');
  if (!filtered.length) {
    wrap.innerHTML = `<div class="empty"><div class="empty-icon">\uD83D\uDCED</div><div class="empty-txt">Tidak ada transaksi</div><div class="empty-sub">Coba ubah filter atau kata kunci</div></div>`;
    renderPagination();
    return;
  }
  const total  = filtered.length;
  const pages  = Math.ceil(total / PAGE_SIZE);
  page = Math.min(page, pages);
  const start  = (page-1)*PAGE_SIZE;
  const slice  = filtered.slice(start, start+PAGE_SIZE);

  const icons = {INCOME:'\uD83D\uDCB0',EXPENSE:'\uD83D\uDCB8',TRANSFER:'\u2194\uFE0F'};
  const cls   = {INCOME:'income',EXPENSE:'expense',TRANSFER:'transfer'};

  // Group by date
  const groups = {};
  slice.forEach(t => {
    const dk = t.date.slice(0,10);
    if (!groups[dk]) groups[dk] = [];
    groups[dk].push(t);
  });

  let html = '';
  for (const dk of Object.keys(groups)) {
    const label = fmtDShort(dk);
    html += `<div class="hist-date-group">${label}</div>`;
    groups[dk].forEach(t => {
      const sign   = t.type==='INCOME'?'+':t.type==='EXPENSE'?'-':'';
      const amtCls = t.type==='INCOME'?'income':t.type==='EXPENSE'?'expense':'';
      const meta   = t.walletName+(t.type==='TRANSFER'?' \u2192 '+t.relatedWalletName:'');
      const badge  = (t.categoryName&&t.type!=='TRANSFER')?`<div class="tx-badge">${esc(t.categoryName)}</div>`:'';
      html += `<div class="tx-item">
        <div class="tx-icon ${cls[t.type]}">${icons[t.type]}</div>
        <div class="tx-body">
          <div class="tx-desc">${esc(t.description)}</div>
          <div class="tx-meta">${esc(meta)}</div>${badge}
        </div>
        <div class="tx-right">
          <span class="tx-amt ${amtCls}">${sign}${fmtS(t.amount)}</span>
          <button class="btn btn-ghost btn-icon btn-xs" onclick="deleteTx('${t.id}')" title="Hapus">\uD83D\uDDD1</button>
        </div>
      </div>`;
    });
  }
  wrap.innerHTML = html;
  renderPagination();
}

function renderPagination() {
  const total  = filtered.length;
  const pages  = Math.ceil(total / PAGE_SIZE);
  const pag    = document.getElementById('histPagination');
  if (pages <= 1) { pag.innerHTML=''; return; }

  let html = `<button class="hist-page-btn" onclick="goPage(${page-1})" ${page===1?'disabled':''}>&#x2190;</button>`;
  const start = Math.max(1, page-2);
  const end   = Math.min(pages, page+2);
  if (start > 1) html += `<button class="hist-page-btn" onclick="goPage(1)">1</button>${start>2?'<span style="padding:0 0.2rem;color:var(--muted)">&#x2026;</span>':''}`;
  for (let i=start;i<=end;i++) {
    html += `<button class="hist-page-btn${i===page?' active':''}" onclick="goPage(${i})">${i}</button>`;
  }
  if (end < pages) html += `${end<pages-1?'<span style="padding:0 0.2rem;color:var(--muted)">&#x2026;</span>':''}<button class="hist-page-btn" onclick="goPage(${pages})">${pages}</button>`;
  html += `<button class="hist-page-btn" onclick="goPage(${page+1})" ${page===pages?'disabled':''}>&#x2192;</button>`;
  pag.innerHTML = html;
}

function goPage(p) {
  page = p;
  renderList();
  document.getElementById('histWrap').scrollIntoView({behavior:'smooth',block:'start'});
}

async function deleteTx(id) {
  if(!confirm('Hapus transaksi ini? Saldo akan dikembalikan.'))return;
  try {
    const res=await fetch('src/actions/transaction.php?action=delete_transaction',{method:'DELETE',body:new URLSearchParams({id}),headers:{'Content-Type':'application/x-www-form-urlencoded'}});
    const d=await res.json();
    if(d.success){toast('Dihapus \u2713','ok');await loadData();}
    else toast(d.message,'err');
  }catch{toast('Gagal menghapus','err');}
}

/* Add Modal */
const iMap = { dana:'public/dana.png', gopay:'public/gopay.png', shopeepay:'public/shopeepay.png', jago:'public/jago.png' };
function renderWalletSelectors() {
  const make = (cid, hid, def=0) => {
    const c=document.getElementById(cid); if(!c)return;
    c.innerHTML = wallets.map((w,i)=>{
      const n=w.name.toLowerCase(); let icon='';
      for(const k of Object.keys(iMap)){if(n.includes(k)){icon=`<img src="${iMap[k]}" class="wsel-emoji" alt="${w.name}">`;break;}}
      if(!icon){const e=n.includes('saving')?'\uD83D\uDC37':'\uD83D\uDCB5';icon=`<div class="wsel-emoji">${e}</div>`;}
      const balStr=(w.balance!==undefined&&modalBalVisible)?`<span class="wsel-bal">${fmtS(w.balance)}</span>`:(w.balance!==undefined?`<span class="wsel-bal">\u2022\u2022\u2022\u2022</span>`:'');
      return `<div class="wsel-item${i===def?' selected':''}" onclick="selectWallet('${cid}','${hid}','${w.id}',this)">${icon}<span class="wsel-name">${w.name}</span>${balStr}</div>`;
    }).join('');
    if(wallets[def])document.getElementById(hid).value=wallets[def].id;
  };
  make('fromWalletSel','selectedFromWallet',0);
  make('toWalletSel','selectedToWallet',wallets.length>1?1:0);
}
function selectWallet(cid,hid,wId,el) {
  document.querySelectorAll(`#${cid} .wsel-item`).forEach(i=>i.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById(hid).value=wId;
}
function setType(btn) {
  currentType=btn.dataset.type;
  document.querySelectorAll('.type-tab').forEach(t=>t.classList.remove('active-expense','active-income','active-transfer'));
  btn.classList.add('active-'+currentType.toLowerCase());
  document.getElementById('toWalletBlock').style.display=currentType==='TRANSFER'?'block':'none';
}
function updateAmtDisplay(){const el=document.getElementById('amountDisplay');const v=parseInt(amountRaw||'0',10);el.textContent=v?fmt(v):'0';el.classList.toggle('placeholder',!v);const na=document.getElementById('nativeAmt');if(na)na.value=amountRaw||'';}
function numpad(n){if(amountRaw.length>=13||(!amountRaw&&n===0))return;amountRaw+=String(n);updateAmtDisplay();}
function numpadDel(){amountRaw=amountRaw.slice(0,-1);updateAmtDisplay();}
function quickAmt(n){amountRaw=String(parseInt(amountRaw||'0',10)+n);updateAmtDisplay();}
function syncNativeAmt(v){amountRaw=v?String(parseInt(v,10)||0):'';const el=document.getElementById('amountDisplay');const num=parseInt(amountRaw||'0',10);el.textContent=num?fmt(num):'0';el.classList.toggle('placeholder',!num);}
function openAddModal(){
  amountRaw='';updateAmtDisplay();
  document.getElementById('descInput').value='';
  document.getElementById('addAlert').classList.remove('show');
  document.querySelectorAll('.type-tab').forEach((t,i)=>{t.classList.remove('active-expense','active-income','active-transfer');if(i===0)t.classList.add('active-expense');});
  currentType='EXPENSE';
  document.getElementById('toWalletBlock').style.display='none';
  const na=document.getElementById('nativeAmt');if(na)na.value='';
  applyKeypadMode();
  modalBalVisible=visState;
  document.getElementById('modalEyeBtn').textContent=modalBalVisible?'\uD83D\uDC41':'\uD83D\uDE48';
  renderWalletSelectors();
  document.getElementById('addOverlay').classList.add('open');
}
function closeAddModal(){document.getElementById('addOverlay').classList.remove('open');}
async function submitRecord(){
  const alertEl=document.getElementById('addAlert');alertEl.classList.remove('show');
  const btn=document.getElementById('addSubmitBtn');
  const amount=parseInt(amountRaw||'0',10);
  const desc=document.getElementById('descInput').value.trim();
  const fromW=document.getElementById('selectedFromWallet').value;
  const toW=document.getElementById('selectedToWallet')?.value;
  if(!amount){alertEl.textContent='Jumlah harus > 0.';alertEl.classList.add('show');return;}
  if(!desc){alertEl.textContent='Keterangan wajib diisi.';alertEl.classList.add('show');return;}
  if(currentType==='TRANSFER'&&fromW===toW){alertEl.textContent='Pilih dompet tujuan berbeda.';alertEl.classList.add('show');return;}
  btn.disabled=true;btn.textContent='Menyimpan\u2026';
  const fd=new FormData();
  fd.append('type',currentType);fd.append('amount',amount);fd.append('description',desc);fd.append('walletId',fromW);
  if(currentType==='TRANSFER')fd.append('relatedWalletId',toW);
  try{
    const res=await fetch('src/actions/transaction.php?action=add_transaction',{method:'POST',body:fd});
    const d=await res.json();
    if(d.success){closeAddModal();toast('Transaksi disimpan \u2713','ok');await loadData();}
    else{alertEl.textContent=d.message;alertEl.classList.add('show');}
  }catch{alertEl.textContent='Koneksi gagal.';alertEl.classList.add('show');}
  btn.disabled=false;btn.textContent='\uD83D\uDCBE Simpan Transaksi';
}
function toast(msg,type=''){const c=document.getElementById('toastWrap');const t=document.createElement('div');t.className='toast'+(type?' '+type:'');t.textContent=msg;c.appendChild(t);setTimeout(()=>t.remove(),2600);}

initPrefs();
loadData();
</script>
</body>
</html>