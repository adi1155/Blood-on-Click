<?php
session_start();
require_once 'config/db.php';
if (isset($_SESSION['user_id'])) { header('Location: /boc/dashboard.php'); exit; }

$error = ''; $success = '';
if (isset($_SESSION['signup_success'])) { $success = 'Account created! Please sign in.'; unset($_SESSION['signup_success']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = getDB()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['blood_group'] = $user['blood_group'];
            header('Location: /boc/dashboard.php'); exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sign In — Blood on Click</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,700;1,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/boc/assets/style.css">
</head><body>
<div class="auth-bg" style="align-items:center;">
  <div style="width:100%;max-width:400px;position:relative;z-index:1;">
    <div class="auth-card">
      <div class="auth-logo">
        <div style="font-size:2rem;margin-bottom:.4rem;">⬟</div>
        <h1>Blood<em>on</em>Click</h1>
        <p>Sign in to your account</p>
      </div>
      <?php if($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="POST" id="lf" novalidate>
        <div style="display:flex;flex-direction:column;gap:.75rem;margin-bottom:1rem;">
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" id="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
            <span class="field-error" id="err-email"></span>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" id="password" placeholder="Your password" autocomplete="current-password">
            <span class="field-error" id="err-password"></span>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        <p style="text-align:center;margin-top:.85rem;font-size:.82rem;color:var(--text-muted)">
          No account? <a href="/boc/signup.php">Register here</a>
        </p>
      </form>
      <hr class="divider">
      <p style="font-size:.72rem;color:var(--text-muted);text-align:center;">
        Admin: <span class="mono" style="color:var(--text-label)">admin@bloodonclick.com</span> / <span class="mono" style="color:var(--text-label)">Admin@123</span>
      </p>
    </div>
  </div>
</div>
<script>
document.getElementById('lf').addEventListener('submit', e => {
  let ok = true;
  const em = document.getElementById('email'), pw = document.getElementById('password');
  const se = (id,msg) => { document.getElementById('err-'+id).textContent=msg; document.getElementById(id).classList.toggle('invalid',!!msg); };
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim())) { se('email','Valid email required.'); ok=false; } else se('email','');
  if(!pw.value) { se('password','Password required.'); ok=false; } else se('password','');
  if(!ok) e.preventDefault();
});
</script>
</body></html>
