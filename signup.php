<?php
session_start();
require_once 'config/db.php';
if (isset($_SESSION['user_id'])) { header('Location: /boc/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'full_name'  => trim($_POST['full_name'] ?? ''),
        'age'        => (int)($_POST['age'] ?? 0),
        'gender'     => $_POST['gender'] ?? '',
        'blood_group'=> $_POST['blood_group'] ?? '',
        'contact'    => trim($_POST['contact'] ?? ''),
        'email'      => strtolower(trim($_POST['email'] ?? '')),
        'password'   => $_POST['password'] ?? '',
        'confirm'    => $_POST['confirm_password'] ?? '',
        'city'       => trim($_POST['city'] ?? ''),
        'role'       => in_array($_POST['role']??'',['donor','seeker']) ? $_POST['role'] : 'donor',
        'last_donation_date' => $_POST['last_donation_date'] ?? null,
    ];
    if (!$d['full_name'] || !$d['age'] || !$d['gender'] || !$d['blood_group'] || !$d['contact'] || !$d['email'] || !$d['password'] || !$d['city'])
        $error = 'Please fill in all required fields.';
    elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL))
        $error = 'Invalid email address.';
    elseif (strlen($d['password']) < 8)
        $error = 'Password must be at least 8 characters.';
    elseif ($d['password'] !== $d['confirm'])
        $error = 'Passwords do not match.';
    else {
        $db = getDB();
        $chk = $db->prepare("SELECT id FROM users WHERE email=?"); $chk->execute([$d['email']]);
        if ($chk->fetch()) { $error = 'Email already registered.'; }
        else {
            $hash = password_hash($d['password'], PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (full_name,age,gender,blood_group,contact,email,password,city,last_donation_date,role) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$d['full_name'],$d['age'],$d['gender'],$d['blood_group'],$d['contact'],$d['email'],$hash,$d['city'],($d['last_donation_date']?:null),$d['role']]);
            $_SESSION['signup_success'] = true;
            header('Location: /boc/login.php'); exit;
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Register — Blood on Click</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,700;1,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/boc/assets/style.css">
</head><body>
<div class="auth-bg">
  <div style="width:100%;max-width:640px;padding:2rem 0;position:relative;z-index:1;">
    <div class="auth-card">
      <div class="auth-logo">
        <h1>Blood<em>on</em>Click</h1>
        <p>Create your account</p>
      </div>
      <?php if($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form method="POST" id="sf" novalidate>
        <!-- Role Selection -->
        <div style="display:flex;gap:.75rem;margin-bottom:1.5rem;" id="roleGroup">
          <label style="flex:1;cursor:pointer;">
            <input type="radio" name="role" value="donor" <?= (($_POST['role']??'donor')==='donor')?'checked':'' ?> style="display:none;" class="role-radio" id="r-donor">
            <div class="role-option" data-for="r-donor">
              <div style="font-size:1.4rem;margin-bottom:.3rem;">🩸</div>
              <strong>Donor</strong>
              <p>I want to donate blood</p>
            </div>
          </label>
          <label style="flex:1;cursor:pointer;">
            <input type="radio" name="role" value="seeker" <?= (($_POST['role']??'')==='seeker')?'checked':'' ?> style="display:none;" class="role-radio" id="r-seeker">
            <div class="role-option" data-for="r-seeker">
              <div style="font-size:1.4rem;margin-bottom:.3rem;">🔍</div>
              <strong>Seeker</strong>
              <p>I need blood / searching donors</p>
            </div>
          </label>
        </div>

        <div class="form-grid">
          <div class="form-group full"><label>Full Name *</label><input type="text" name="full_name" id="full_name" placeholder="Your full name" value="<?= htmlspecialchars($_POST['full_name']??'') ?>"><span class="field-error" id="err-full_name"></span></div>
          <div class="form-group"><label>Age *</label><input type="number" name="age" id="age" min="18" max="65" placeholder="25" value="<?= htmlspecialchars($_POST['age']??'') ?>"><span class="field-error" id="err-age"></span></div>
          <div class="form-group"><label>Gender *</label><select name="gender" id="gender"><option value="">Select</option><?php foreach(['Male','Female','Other'] as $g): ?><option value="<?= $g ?>" <?= (($_POST['gender']??'')===$g)?'selected':'' ?>><?= $g ?></option><?php endforeach; ?></select><span class="field-error" id="err-gender"></span></div>
          <div class="form-group"><label>Blood Group *</label><select name="blood_group" id="blood_group"><option value="">Select</option><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?><option value="<?= $bg ?>" <?= (($_POST['blood_group']??'')===$bg)?'selected':'' ?>><?= $bg ?></option><?php endforeach; ?></select><span class="field-error" id="err-blood_group"></span></div>
          <div class="form-group"><label>Contact *</label><input type="tel" name="contact" id="contact" placeholder="03001234567" value="<?= htmlspecialchars($_POST['contact']??'') ?>"><span class="field-error" id="err-contact"></span></div>
          <div class="form-group"><label>City *</label><input type="text" name="city" id="city" placeholder="Lahore" value="<?= htmlspecialchars($_POST['city']??'') ?>"><span class="field-error" id="err-city"></span></div>
          <div class="form-group full"><label>Email *</label><input type="email" name="email" id="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email']??'') ?>"><span class="field-error" id="err-email"></span></div>
          <div class="form-group"><label>Password *</label><input type="password" name="password" id="password" placeholder="Min. 8 characters"><span class="field-error" id="err-password"></span></div>
          <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password"><span class="field-error" id="err-confirm_password"></span></div>
          <div class="form-group full" id="donorDateWrap">
            <label>Last Donation Date <small style="font-weight:400;text-transform:none;">(donors only, optional)</small></label>
            <input type="date" name="last_donation_date" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['last_donation_date']??'') ?>">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full" style="margin-top:1.25rem;">Create Account</button>
        <p style="text-align:center;margin-top:.75rem;font-size:.82rem;color:var(--text-muted)">
          Already registered? <a href="/boc/login.php">Sign in</a>
        </p>
      </form>
    </div>
  </div>
</div>

<style>
.role-option {
  background:var(--bg-panel); border:1px solid var(--border); border-radius:var(--radius-lg);
  padding:1rem; text-align:center; transition:all var(--t);
}
.role-option strong { display:block; font-size:.9rem; margin-bottom:.15rem; }
.role-option p { font-size:.75rem; color:var(--text-muted); }
.role-option.selected { border-color:var(--red); background:var(--red-pale); }
</style>
<script>
// Role selector
document.querySelectorAll('.role-radio').forEach(r => {
  r.addEventListener('change', () => {
    document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
    document.querySelector(`[data-for="${r.id}"]`).classList.add('selected');
    document.getElementById('donorDateWrap').style.display = r.value === 'donor' ? '' : 'none';
  });
});
// Init
const activeR = document.querySelector('.role-radio:checked');
if (activeR) { document.querySelector(`[data-for="${activeR.id}"]`).classList.add('selected'); }
if (activeR?.value === 'seeker') document.getElementById('donorDateWrap').style.display = 'none';

// Validation
const rules = {
  full_name: v => v.trim().length>=2?'':'Name required.',
  age:       v => (parseInt(v)>=18&&parseInt(v)<=65)?'':'Age must be 18-65.',
  gender:    v => v?'':'Select gender.',
  blood_group:v => v?'':'Select blood group.',
  contact:   v => /^[0-9+\-\s]{7,15}$/.test(v.trim())?'':'Valid contact required.',
  city:      v => v.trim().length>=2?'':'City required.',
  email:     v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim())?'':'Valid email required.',
  password:  v => v.length>=8?'':'Min 8 characters.',
  confirm_password: v => (v===document.getElementById('password').value)?'':'Passwords do not match.',
};
function vf(n,v){const fn=rules[n];if(!fn)return'';const m=fn(v);const el=document.getElementById(n);const er=document.getElementById('err-'+n);if(m){el?.classList.add('invalid');if(er)er.textContent=m;}else{el?.classList.remove('invalid');if(er)er.textContent='';}return m;}
Object.keys(rules).forEach(n=>{const el=document.getElementById(n);if(!el)return;el.addEventListener('blur',()=>vf(n,el.value));});
document.getElementById('sf').addEventListener('submit',e=>{let bad=false;Object.keys(rules).forEach(n=>{const el=document.getElementById(n);if(el&&vf(n,el.value))bad=true;});if(bad){e.preventDefault();document.querySelector('.invalid')?.scrollIntoView({behavior:'smooth',block:'center'});}});
</script>
</body></html>
