<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$pageTitle = 'Daftar – Neofinance';
include '_head.php';
?>
<body>
  <div class="center-wrap">
    <div class="auth-box">
      <div class="auth-title">Neofinance</div>
      <div class="auth-sub">Buat akun baru gratis</div>

      <div id="alertErr" class="alert alert-err"></div>

      <form id="registerForm" onsubmit="submitRegister(event)">
        <div class="fgroup">
          <label>Username</label>
          <input type="text" name="username" placeholder="pilih username" autocomplete="username" required>
        </div>
        <div class="fgroup">
          <label>Password</label>
          <input type="password" name="password" placeholder="buat password" autocomplete="new-password" required>
        </div>
        <button type="submit" id="submitBtn" class="btn btn-full" style="margin-top:0.5rem">Daftar</button>
      </form>

      <p style="text-align:center;margin-top:1rem;font-size:0.78rem;color:var(--muted)">
        Sudah punya akun?
        <a href="login.php" style="color:var(--text);font-weight:700">Masuk</a>
      </p>
    </div>
  </div>

  <script>
    async function submitRegister(e) {
      e.preventDefault();
      const btn = document.getElementById('submitBtn');
      const err = document.getElementById('alertErr');
      err.classList.remove('show');
      btn.disabled = true; btn.textContent = 'Mendaftar…';
      try {
        const res  = await fetch('auth_actions.php?action=register',{method:'POST',body:new FormData(e.target)});
        const data = await res.json();
        if (data.success) { location.href='login.php?registered=1'; }
        else { err.textContent=data.message; err.classList.add('show'); btn.disabled=false; btn.textContent='Daftar'; }
      } catch { err.textContent='Terjadi kesalahan koneksi.'; err.classList.add('show'); btn.disabled=false; btn.textContent='Daftar'; }
    }
  </script>
</body>
</html>
