<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – Neofinance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="center-wrap">
    <div class="auth-box">
      <div class="auth-title">Neofinance</div>
      <div class="auth-sub">Masuk ke akun kamu</div>

      <div id="alertOk" class="alert alert-ok"></div>
      <div id="alertErr" class="alert alert-err"></div>

      <form id="loginForm" onsubmit="submitLogin(event)">
        <div class="fgroup">
          <label>Username</label>
          <input type="text" name="username" placeholder="masukkan username" autocomplete="username" required>
        </div>
        <div class="fgroup">
          <label>Password</label>
          <input type="password" name="password" placeholder="masukkan password" autocomplete="current-password" required>
        </div>
        <button type="submit" id="submitBtn" class="btn btn-full" style="margin-top:0.5rem">Masuk</button>
      </form>

      <p style="text-align:center;margin-top:1rem;font-size:0.78rem;color:var(--muted)">
        Belum punya akun?
        <a href="register.php" style="color:var(--text);font-weight:700">Daftar sekarang</a>
      </p>
    </div>
  </div>

  <script>
    const params = new URLSearchParams(location.search);
    if (params.has('registered')) {
      const el = document.getElementById('alertOk');
      el.textContent = 'Registrasi berhasil! Silakan login.';
      el.classList.add('show');
    }

    async function submitLogin(e) {
      e.preventDefault();
      const btn = document.getElementById('submitBtn');
      const err = document.getElementById('alertErr');
      err.classList.remove('show');
      btn.disabled = true; btn.textContent = 'Memuat…';
      try {
        const res  = await fetch('auth_actions.php?action=login', { method:'POST', body:new FormData(e.target) });
        const data = await res.json();
        if (data.success) {
          location.href = 'index.php';
        } else {
          err.textContent = data.message;
          err.classList.add('show');
          btn.disabled = false; btn.textContent = 'Masuk';
        }
      } catch {
        err.textContent = 'Terjadi kesalahan koneksi.';
        err.classList.add('show');
        btn.disabled = false; btn.textContent = 'Masuk';
      }
    }
  </script>
</body>
</html>
