<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireAdmin();

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'full_name'   => trim($_POST['full_name'] ?? ''),
        'age'         => (int)($_POST['age'] ?? 0),
        'gender'      => $_POST['gender'] ?? '',
        'blood_group' => $_POST['blood_group'] ?? '',
        'contact'     => trim($_POST['contact'] ?? ''),
        'email'       => strtolower(trim($_POST['email'] ?? '')),
        'password'    => $_POST['password'] ?? 'Admin@123',
        'city'        => trim($_POST['city'] ?? ''),
        'role'        => in_array($_POST['role']??'',['admin','donor','blood_bank','seeker']) ? $_POST['role'] : 'donor',
        'last_donation_date' => $_POST['last_donation_date'] ?: null,
        'is_available' => isset($_POST['is_available']) ? 1 : 0,
    ];
    if (!$d['full_name'] || !$d['email'] || !$d['city']) {
        $error = 'Name, email, and city are required.';
    } else {
        $chk = $db->prepare("SELECT id FROM users WHERE email=?"); $chk->execute([$d['email']]);
        if ($chk->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($d['password'], PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO users (full_name,age,gender,blood_group,contact,email,password,city,last_donation_date,role,is_available) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([$d['full_name'],$d['age'],$d['gender'],$d['blood_group'],$d['contact'],$d['email'],$hash,$d['city'],$d['last_donation_date'],$d['role'],$d['is_available']]);

            // If blood_bank role, prompt to also create bank record
            $_SESSION['flash_success'] = "User '{$d['full_name']}' added successfully.";
            header('Location: /admin/index.php'); exit;
        }
    }
}

renderHead('Add User'); ?>
<?php renderNav(); ?>
<div class="page animate-in" style="max-width:680px;">
  <a href="/admin/index.php" class="back-link">← Back to Users</a>
  <h1 class="page-title">Add <span>New User</span></h1>
  <p class="page-subtitle">Create a new account for any role in the system.</p>

  <?php if($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group full"><label>Full Name *</label><input type="text" name="full_name" placeholder="User's full name" value="<?= htmlspecialchars($_POST['full_name']??'') ?>"></div>
        <div class="form-group"><label>Role *</label><select name="role"><option value="donor">Donor</option><option value="seeker">Seeker</option><option value="blood_bank">Blood Bank Staff</option><option value="admin">Admin</option></select></div>
        <div class="form-group"><label>Blood Group</label><select name="blood_group"><option value="">Select</option><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?><option value="<?= $bg ?>" <?= (($_POST['blood_group']??'')===$bg)?'selected':'' ?>><?= $bg ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Age</label><input type="number" name="age" min="1" max="120" value="<?= htmlspecialchars($_POST['age']??'') ?>"></div>
        <div class="form-group"><label>Gender</label><select name="gender"><option value="">Select</option><?php foreach(['Male','Female','Other'] as $g): ?><option value="<?= $g ?>" <?= (($_POST['gender']??'')===$g)?'selected':'' ?>><?= $g ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Contact</label><input type="tel" name="contact" placeholder="03001234567" value="<?= htmlspecialchars($_POST['contact']??'') ?>"></div>
        <div class="form-group"><label>City *</label><input type="text" name="city" placeholder="e.g. Lahore" value="<?= htmlspecialchars($_POST['city']??'') ?>"></div>
        <div class="form-group full"><label>Email *</label><input type="email" name="email" placeholder="user@example.com" value="<?= htmlspecialchars($_POST['email']??'') ?>"></div>
        <div class="form-group"><label>Password <small style="font-weight:400;text-transform:none;">(default: Admin@123)</small></label><input type="text" name="password" value="Admin@123"></div>
        <div class="form-group"><label>Last Donation Date <small style="font-weight:400;text-transform:none;">(donors only)</small></label><input type="date" name="last_donation_date" max="<?= date('Y-m-d') ?>"></div>
        <div class="form-group full"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;text-transform:none;font-size:.85rem;"><input type="checkbox" name="is_available" checked style="width:auto;"> Mark as available for donation</label></div>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:1rem;">
        <button type="submit" class="btn btn-primary">Add User</button>
        <a href="/admin/index.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
</body></html>
