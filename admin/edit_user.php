<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin/index.php'); exit; }

$user = $db->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$id]); $user = $user->fetch();
if (!$user) { header('Location: /admin/index.php'); exit; }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'full_name'   => trim($_POST['full_name'] ?? ''),
        'age'         => (int)($_POST['age'] ?? 0),
        'gender'      => $_POST['gender'] ?? '',
        'blood_group' => $_POST['blood_group'] ?? '',
        'contact'     => trim($_POST['contact'] ?? ''),
        'email'       => strtolower(trim($_POST['email'] ?? '')),
        'city'        => trim($_POST['city'] ?? ''),
        'role'        => in_array($_POST['role']??'',['admin','donor','blood_bank','seeker']) ? $_POST['role'] : $user['role'],
        'last_donation_date' => $_POST['last_donation_date'] ?: null,
        'is_available' => isset($_POST['is_available']) ? 1 : 0,
    ];
    if (!$d['full_name'] || !$d['email'] || !$d['city']) {
        $error = 'Name, email, and city are required.';
    } else {
        $chk = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?"); $chk->execute([$d['email'],$id]);
        if ($chk->fetch()) {
            $error = 'Email already used by another account.';
        } else {
            $db->prepare("UPDATE users SET full_name=?,age=?,gender=?,blood_group=?,contact=?,email=?,city=?,last_donation_date=?,role=?,is_available=?,updated_at=NOW() WHERE id=?")
               ->execute([$d['full_name'],$d['age'],$d['gender'],$d['blood_group'],$d['contact'],$d['email'],$d['city'],$d['last_donation_date'],$d['role'],$d['is_available'],$id]);

            if ($_POST['new_password'] ?? '') {
                $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$id]);
            }
            $success = 'User updated successfully.';
            $user = $db->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$id]); $user=$user->fetch();
        }
    }
}

renderHead('Edit User'); ?>
<?php renderNav(); ?>
<div class="page animate-in" style="max-width:680px;">
  <a href="/admin/index.php" class="back-link">← Back to Users</a>
  <h1 class="page-title">Edit <span><?= htmlspecialchars($user['full_name']) ?></span></h1>
  <p class="page-subtitle">Update user profile and account details.</p>

  <?php if($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group full"><label>Full Name *</label><input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>"></div>
        <div class="form-group"><label>Role</label><select name="role"><?php foreach(['donor','seeker','blood_bank','admin'] as $r): ?><option value="<?= $r ?>" <?= $user['role']===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Blood Group</label><select name="blood_group"><option value="">None</option><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?><option value="<?= $bg ?>" <?= $user['blood_group']===$bg?'selected':'' ?>><?= $bg ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Age</label><input type="number" name="age" min="1" max="120" value="<?= $user['age'] ?>"></div>
        <div class="form-group"><label>Gender</label><select name="gender"><option value="">Select</option><?php foreach(['Male','Female','Other'] as $g): ?><option value="<?= $g ?>" <?= $user['gender']===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Contact</label><input type="tel" name="contact" value="<?= htmlspecialchars($user['contact']??'') ?>"></div>
        <div class="form-group"><label>City *</label><input type="text" name="city" value="<?= htmlspecialchars($user['city']??'') ?>"></div>
        <div class="form-group full"><label>Email *</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></div>
        <div class="form-group"><label>Last Donation Date</label><input type="date" name="last_donation_date" value="<?= $user['last_donation_date'] ?>" max="<?= date('Y-m-d') ?>"></div>
        <div class="form-group"><label>New Password <small style="font-weight:400;text-transform:none;">(leave blank to keep)</small></label><input type="password" name="new_password" placeholder="Enter new password"></div>
        <div class="form-group full"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;text-transform:none;font-size:.85rem;"><input type="checkbox" name="is_available" <?= $user['is_available']?'checked':'' ?> style="width:auto;"> Available for donation</label></div>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:1rem;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="/admin/index.php" class="btn btn-secondary">Cancel</a>
        <?php if($user['role']==='donor'): ?><a href="/admin/assessments.php?donor=<?= $id ?>" class="btn btn-secondary">View Assessments</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
</body></html>
