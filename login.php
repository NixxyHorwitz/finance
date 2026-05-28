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
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="page-center">
    <div class="auth-box">
      <p class="auth-title">Neofinance</p>
      <p class="auth-sub">Masuk ke akun kamu</p>

      <div id="alert" class="alert alert-danger"></div>
      <div id="alertOk" class="alert alert-success"></div>

      <form id="loginForm">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" placeholder="masukkan username" autocomplete="username" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="masukkan password" autocomplete="current-password" required>
        </div>
        <button type="submit" id="submitBtn" class="btn btn-full" style="margin-top:0.5rem;">Masuk</button>
      </form>

      <p style="text-align:center;margin-top:1rem;font-size:0.82rem;color:var(--muted);">
        Belum punya akun? <a href="register.php" style="color:var(--text);font-weight:700;">Daftar</a>
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

    document.getElementById('loginForm').addEventListener('submit', async e => {
      e.preventDefault();
      const btn = document.getElementById('submitBtn');
      const alert = document.getElementById('alert');
      alert.classList.remove('show');
      btn.disabled = true;
      btn.textContent = 'Memuat...';
      const fd = new FormData(e.target);
      try {
        const res = await fetch('auth_actions.php?action=login', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          location.href = 'index.php';
        } else {
          alert.textContent = data.message;
          alert.classList.add('show');
          btn.disabled = false;
          btn.textContent = 'Masuk';
        }
      } catch {
        alert.textContent = 'Terjadi kesalahan koneksi.';
        alert.classList.add('show');
        btn.disabled = false;
        btn.textContent = 'Masuk';
      }
    });
  </script>
</body>
</html>
