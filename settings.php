<?php
require_once 'db.php';
requireLogin();

$userId = $_SESSION['user_id'];
$sw = $pdo->prepare("SELECT * FROM Wallet WHERE userId = ? ORDER BY FIELD(name,'Cash','Dana','Gopay','ShopeePay','Bank Jago','Savings')");
$sw->execute([$userId]);
$wallets = $sw->fetchAll();
foreach ($wallets as &$w) $w['balance'] = (float)$w['balance'];
unset($w);
$walletsJson = json_encode($wallets);

$pageTitle = 'Pengaturan &ndash; Neofinance';
include 'src/components/_head.php';
?>
<body>

<div class="page-wrap">

  <!-- Header -->
  <div class="page-header">
    <a href="/" class="btn btn-ghost btn-xs" style="padding:0.38rem 0.55rem;">&#x2190;</a>
    <div class="page-title">Pengaturan</div>
  </div>

  <!-- Profil -->
  <div class="settings-section">
    <div class="settings-section-title">Profil</div>
    <div class="settings-card">

      <!-- Username -->
      <div class="settings-item" id="uRow">
        <div class="settings-item-left">
          <div class="settings-icon" style="background:#EEF1FF;">&#x1F464;</div>
          <div>
            <div class="settings-label">Username</div>
            <div class="settings-sub" id="curUsername"><?= htmlspecialchars($_SESSION['username']) ?></div>
          </div>
        </div>
        <button class="btn btn-ghost btn-xs" onclick="toggleEdit('uEdit')">Edit</button>
      </div>
      <div class="inline-edit" id="uEdit">
        <div id="uAlert" class="alert alert-err"></div>
        <div id="uOk" class="alert alert-ok"></div>
        <div class="inline-edit-row">
          <input type="text" id="newUsername" placeholder="Username baru" value="<?= htmlspecialchars($_SESSION['username']) ?>">
          <button class="btn btn-xs" onclick="changeUsername()" style="flex-shrink:0">Simpan</button>
        </div>
      </div>

      <!-- Password -->
      <div class="settings-item" id="pRow">
        <div class="settings-item-left">
          <div class="settings-icon" style="background:#EDFFF9;">&#x1F512;</div>
          <div>
            <div class="settings-label">Password</div>
            <div class="settings-sub">Ubah password akun</div>
          </div>
        </div>
        <button class="btn btn-ghost btn-xs" onclick="toggleEdit('pEdit')">Edit</button>
      </div>
      <div class="inline-edit" id="pEdit">
        <div id="pAlert" class="alert alert-err"></div>
        <div id="pOk" class="alert alert-ok"></div>
        <div class="fgroup">
          <label>Password Saat Ini</label>
          <input type="password" id="curPass" placeholder="&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;">
        </div>
        <div class="fgroup" style="margin-bottom:0.5rem">
          <label>Password Baru</label>
          <input type="password" id="newPass" placeholder="minimal 6 karakter">
        </div>
        <button class="btn btn-xs" onclick="changePassword()" style="width:100%">Simpan Password</button>
      </div>

    </div>
  </div>

  <!-- Preferensi -->
  <div class="settings-section">
    <div class="settings-section-title">Preferensi</div>
    <div class="settings-card">

      <!-- Balance visibility -->
      <div class="settings-item">
        <div class="settings-item-left">
          <div class="settings-icon" style="background:#FFF8E7;">&#x1F441;</div>
          <div>
            <div class="settings-label">Tampilkan Saldo</div>
            <div class="settings-sub">Sembunyikan nominal saldo</div>
          </div>
        </div>
        <label class="toggle">
          <input type="checkbox" id="toggleVis" onchange="savePref('nf_bal_vis', this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>

      <!-- Input mode -->
      <div class="settings-item">
        <div class="settings-item-left">
          <div class="settings-icon" style="background:#F5EEFF;">&#x1F522;</div>
          <div>
            <div class="settings-label">Mode Input Keypad</div>
            <div class="settings-sub">Gunakan keypad kustom saat input nominal</div>
          </div>
        </div>
        <label class="toggle">
          <input type="checkbox" id="toggleKeypad" onchange="savePref('nf_keypad', this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>

    </div>
  </div>

  <!-- Dompet -->
  <div class="settings-section">
    <div class="settings-section-title">Dompet</div>
    <div class="settings-card" id="walletSettingsList">
      <!-- injected -->
    </div>
  </div>

  <!-- Integrasi AI -->
  <div class="settings-section">
    <div class="settings-section-title">Integrasi AI &#x2728;</div>
    <div class="settings-card">

      <div class="settings-item" id="aiRow">
        <div class="settings-item-left">
          <div class="settings-icon" style="background:#E8F5E9;">&#x2728;</div>
          <div>
            <div class="settings-label">Gemini API Key</div>
            <div class="settings-sub" id="aiStatus">Mengecek&#x2026;</div>
          </div>
        </div>
        <button class="btn btn-ghost btn-xs" onclick="toggleEdit('aiEdit')">Edit</button>
      </div>
      <div class="inline-edit" id="aiEdit">
        <div id="aiAlert" class="alert alert-err"></div>
        <div id="aiOk" class="alert alert-ok"></div>
        <p style="font-size:0.7rem;color:var(--muted);margin-bottom:0.5rem;line-height:1.5;">
          Dapatkan API key gratis di <a href="https://aistudio.google.com/apikey" target="_blank" style="color:var(--blue);font-weight:700;">aistudio.google.com</a> &mdash; 1 juta token/hari, no CC required.<br>
          Kalau diisi, transaksi akan dikategorikan pakai Gemini AI (lebih akurat).
        </p>
        <div class="inline-edit-row">
          <input type="password" id="geminiApiKey" placeholder="AIzaSy...">
          <button class="btn btn-xs" onclick="saveGeminiKey()" style="flex-shrink:0">Simpan</button>
        </div>
      </div>

    </div>
  </div>

  <!-- Sesi -->
  <div class="settings-section">
    <div class="settings-section-title">Sesi</div>
    <div class="settings-card">
      <div class="settings-item">
        <div class="settings-item-left">
          <div class="settings-icon" style="background:#FFF0F0;">&#x1F6AA;</div>
          <div>
            <div class="settings-label">Keluar</div>
            <div class="settings-sub">Logout dari akun ini</div>
          </div>
        </div>
        <a href="logout.php" class="btn btn-danger btn-xs">Keluar</a>
      </div>
    </div>
  </div>

</div><!-- /page-wrap -->

<!-- Bottom Nav -->
<?php $navPage = 'settings'; include 'src/components/_navbar.php'; ?>

<?php include 'src/components/_add_modal.php'; ?>
<!-- Toast -->
<div class="toast-wrap" id="toastWrap"></div>

<!-- Modal: Set Balance Wallet -->
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
        <div class="amount-value" id="balDisplay">0</div>
      </div>
      <div class="quick-amounts mb-1">
        <button class="quick-btn" onclick="balQuick(0)">Reset</button>
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
        <button class="nk nk-del" onclick="balNumpadDel()">&#x232B;</button>
      </div>
      <input type="hidden" id="balWalletId">
      <button class="btn btn-full btn-lg" id="balSubmitBtn" onclick="submitBalance()" style="margin-top:0.5rem;">
        &#x1F4BE; Simpan Saldo
      </button>
    </div>
  </div>
</div>

<!-- Modal: Edit Wallet Name -->
<div class="overlay" id="walletNameOverlay" onclick="if(event.target===this)closeWalletNameModal()">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <div class="modal-title">Edit Nama Dompet</div>
      <span class="modal-close" onclick="closeWalletNameModal()">&#x2715;</span>
    </div>
    <div class="modal-body">
      <div id="wnAlert" class="alert alert-err"></div>
      <div class="fgroup">
        <label>Nama Dompet</label>
        <input type="text" id="walletNewName" placeholder="Nama baru">
      </div>
      <input type="hidden" id="walletEditId">
      <button class="btn btn-full btn-lg" onclick="submitWalletName()">Simpan Nama</button>
    </div>
  </div>
</div>

<script src="src/js/add_modal.js"></script>
<script>
const WALLETS  = <?= $walletsJson ?>;
const LS_VIS   = 'nf_bal_vis';
const LS_KP    = 'nf_keypad';
const fmt  = n => Number(n).toLocaleString('id-ID');
const fmtS = n => {
  if(n>=1e6) return 'Rp '+(n/1e6).toLocaleString('id-ID',{maximumFractionDigits:1})+'jt';
  if(n>=1e3) return 'Rp '+(n/1e3).toLocaleString('id-ID',{maximumFractionDigits:0})+'rb';
  return 'Rp '+n;
};
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
let balRaw = '';

// --- Init toggles ---
function initToggles() {
  const vis = localStorage.getItem(LS_VIS);
  document.getElementById('toggleVis').checked = vis===null ? true : vis==='1';
  const kp  = localStorage.getItem(LS_KP);
  document.getElementById('toggleKeypad').checked = kp===null ? true : kp==='1';
}

function savePref(key, val) {
  localStorage.setItem(key, val ? '1' : '0');
  toast(val ? 'Diaktifkan \u2713' : 'Dinonaktifkan', 'ok');
}

function toggleEdit(id) {
  document.getElementById(id).classList.toggle('open');
}

// --- Username ---
async function changeUsername() {
  const alertErr=document.getElementById('uAlert');
  const alertOk =document.getElementById('uOk');
  alertErr.classList.remove('show'); alertOk.classList.remove('show');
  const username=document.getElementById('newUsername').value.trim();
  const fd=new FormData(); fd.append('username',username);
  try {
    const res=await fetch('src/actions/settings.php?action=change_username',{method:'POST',body:fd});
    const d=await res.json();
    if(d.success){
      document.getElementById('curUsername').textContent=username;
      alertOk.textContent=d.message; alertOk.classList.add('show');
      toast('Username diubah \u2713','ok');
    } else { alertErr.textContent=d.message; alertErr.classList.add('show'); }
  } catch { alertErr.textContent='Koneksi gagal.'; alertErr.classList.add('show'); }
}

// --- Password ---
async function changePassword() {
  const alertErr=document.getElementById('pAlert');
  const alertOk =document.getElementById('pOk');
  alertErr.classList.remove('show'); alertOk.classList.remove('show');
  const cur=document.getElementById('curPass').value;
  const nw =document.getElementById('newPass').value;
  const fd=new FormData(); fd.append('current_password',cur); fd.append('new_password',nw);
  try {
    const res=await fetch('src/actions/settings.php?action=change_password',{method:'POST',body:fd});
    const d=await res.json();
    if(d.success){
      alertOk.textContent=d.message; alertOk.classList.add('show');
      document.getElementById('curPass').value='';
      document.getElementById('newPass').value='';
      toast('Password diubah \u2713','ok');
    } else { alertErr.textContent=d.message; alertErr.classList.add('show'); }
  } catch { alertErr.textContent='Koneksi gagal.'; alertErr.classList.add('show'); }
}

// --- Wallet List ---
const iMap = { dana:'public/dana.png', gopay:'public/gopay.png', shopeepay:'public/shopeepay.png', jago:'public/jago.png' };
function getIcon(name) {
  const n=name.toLowerCase();
  for(const k of Object.keys(iMap)){if(n.includes(k))return `<img src="${iMap[k]}" style="width:28px;height:28px;border-radius:7px;object-fit:cover;border:1.5px solid rgba(0,0,0,0.1);" alt="${name}">`;}
  const e=n.includes('saving')?'\uD83D\uDC37':'\uD83D\uDCB5';
  const bg=n.includes('saving')?'#FFF8E7':'#F4F0FF';
  return `<div style="width:28px;height:28px;border-radius:7px;border:1.5px solid rgba(0,0,0,0.1);background:${bg};display:flex;align-items:center;justify-content:center;font-size:15px;">${e}</div>`;
}

function renderWalletSettings() {
  const list=document.getElementById('walletSettingsList');
  if (!WALLETS.length) { list.innerHTML='<div class="empty" style="padding:2rem;text-align:center;color:var(--muted)">Belum ada dompet</div>'; return; }
  list.innerHTML = WALLETS.map(w=>`
    <div class="wallet-settings-item">
      ${getIcon(w.name)}
      <div class="wallet-settings-info">
        <div class="wallet-settings-name">${esc(w.name)}</div>
        <div class="wallet-settings-bal">${fmtS(w.balance)}</div>
      </div>
      <div class="wallet-settings-actions">
        <button class="btn btn-ghost btn-xs" onclick="openWalletNameModal('${w.id}','${w.name.replace(/'/g,"\\'")}')">&#x270F;&#xFE0F; Nama</button>
        <button class="btn btn-xs" onclick="openBalModal('${w.id}','${w.name.replace(/'/g,"\\'")}',${w.balance})">&#x1F4B0; Saldo</button>
      </div>
    </div>
  `).join('');
}

// --- Wallet Name Modal ---
function openWalletNameModal(id, name) {
  document.getElementById('walletEditId').value=id;
  document.getElementById('walletNewName').value=name;
  document.getElementById('wnAlert').classList.remove('show');
  document.getElementById('walletNameOverlay').classList.add('open');
}
function closeWalletNameModal() { document.getElementById('walletNameOverlay').classList.remove('open'); }

async function submitWalletName() {
  const alertEl=document.getElementById('wnAlert'); alertEl.classList.remove('show');
  const walletId=document.getElementById('walletEditId').value;
  const name=document.getElementById('walletNewName').value.trim();
  const fd=new FormData(); fd.append('walletId',walletId); fd.append('name',name);
  try {
    const res=await fetch('src/actions/settings.php?action=update_wallet',{method:'POST',body:fd});
    const d=await res.json();
    if(d.success){ closeWalletNameModal(); toast('Nama dompet diperbarui \u2713','ok'); setTimeout(()=>location.reload(),1000); }
    else { alertEl.textContent=d.message; alertEl.classList.add('show'); }
  } catch { alertEl.textContent='Koneksi gagal.'; alertEl.classList.add('show'); }
}

// --- Balance Modal ---
function updateBalDisplay() {
  const el=document.getElementById('balDisplay');
  const v=parseInt(balRaw||'0',10);
  el.textContent=v?fmt(v):'0';
  el.classList.toggle('placeholder',!v);
}
function balNumpad(n)  { if(balRaw.length>=13||(!balRaw&&n===0))return; balRaw+=String(n); updateBalDisplay(); }
function balNumpadDel(){ balRaw=balRaw.slice(0,-1); updateBalDisplay(); }
function balQuick(n)   { balRaw=String(n); updateBalDisplay(); }

function openBalModal(id, name, bal) {
  balRaw=bal>0?String(Math.round(bal)):'';
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
  btn.disabled=true; btn.textContent='Menyimpan\u2026';
  const fd=new FormData(); fd.append('walletId',walletId); fd.append('balance',balance);
  try {
    const res=await fetch('src/actions/transaction.php?action=set_balance',{method:'POST',body:fd});
    const d=await res.json();
    if(d.success){ closeBalModal(); toast('Saldo diperbarui \u2713','ok'); setTimeout(()=>location.reload(),1000); }
    else { alertEl.textContent=d.message; alertEl.classList.add('show'); }
  } catch { alertEl.textContent='Koneksi gagal.'; alertEl.classList.add('show'); }
  btn.disabled=false; btn.textContent='\uD83D\uDCBE Simpan Saldo';
}

// --- Gemini API Key ---
async function loadGeminiKeyStatus() {
  try {
    const res = await fetch('src/actions/settings.php?action=get_setting&key=gemini_api_key');
    const d   = await res.json();
    const el  = document.getElementById('aiStatus');
    if (d.value && d.value.trim()) {
      el.textContent = '\u2705 Terhubung \u2014 AI categorization aktif';
      el.style.color = 'var(--mint)';
      document.getElementById('geminiApiKey').placeholder = d.value.slice(0,8) + '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022';
    } else {
      el.textContent = 'Belum dikonfigurasi \u2014 pakai keyword matching';
      el.style.color = '';
    }
  } catch { document.getElementById('aiStatus').textContent = 'Gagal mengecek status'; }
}

async function saveGeminiKey() {
  const alertErr = document.getElementById('aiAlert');
  const alertOk  = document.getElementById('aiOk');
  alertErr.classList.remove('show'); alertOk.classList.remove('show');
  const key = document.getElementById('geminiApiKey').value.trim();
  const fd  = new FormData();
  fd.append('key', 'gemini_api_key');
  fd.append('value', key);
  try {
    const res = await fetch('src/actions/settings.php?action=save_setting', { method:'POST', body:fd });
    const d   = await res.json();
    if (d.success) {
      alertOk.textContent = key ? '\u2705 API key disimpan!' : 'API key dihapus.';
      alertOk.classList.add('show');
      toast(key ? 'Gemini AI aktif \u2713' : 'API key dihapus', 'ok');
      loadGeminiKeyStatus();
    } else {
      alertErr.textContent = d.message;
      alertErr.classList.add('show');
    }
  } catch {
    alertErr.textContent = 'Koneksi gagal.';
    alertErr.classList.add('show');
  }
}

// --- Toast ---
function toast(msg,type='') {
  const c=document.getElementById('toastWrap');
  const t=document.createElement('div');
  t.className='toast'+(type?' '+type:'');
  t.textContent=msg; c.appendChild(t);
  setTimeout(()=>t.remove(),2600);
}

// --- Init ---
initToggles(); renderWalletSettings(); loadGeminiKeyStatus();
</script>
</body>
</html>