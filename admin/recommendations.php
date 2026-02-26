<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireAdmin();

$db  = getDB();
$uid = $_SESSION['user_id'];
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId  = (int)$_POST['user_id'];
    $bankId  = (int)$_POST['blood_bank_id'];
    $reason  = trim($_POST['reason']??'');
    if (!$userId || !$bankId) $error = 'Please select a user and a blood bank.';
    else {
        $db->prepare("INSERT INTO recommendations (admin_id,user_id,blood_bank_id,reason) VALUES (?,?,?,?)")->execute([$uid,$userId,$bankId,$reason]);
        // Also notify
        $bank = $db->prepare("SELECT name FROM blood_banks WHERE id=?"); $bank->execute([$bankId]); $bank=$bank->fetch();
        $db->prepare("INSERT INTO notifications (user_id,sender_id,title,message,type) VALUES (?,?,?,?,?)")
           ->execute([$userId,$uid,"Blood Bank Recommendation","Admin recommends: {$bank['name']}. ".($reason?:"Visit at your earliest convenience."),'info']);
        $success = 'Recommendation saved and user notified.';
    }
}

$users = $db->query("SELECT id,full_name,role,blood_group,city FROM users WHERE role IN ('donor','seeker') ORDER BY full_name")->fetchAll();
$banks = $db->query("SELECT bb.*,u.city FROM blood_banks bb JOIN users u ON bb.user_id=u.id WHERE bb.is_active=1 ORDER BY bb.name")->fetchAll();
$recs  = $db->query("SELECT r.*,u.full_name AS user_name,u.role AS user_role,bb.name AS bank_name,bb.city AS bank_city,a.full_name AS admin_name FROM recommendations r JOIN users u ON r.user_id=u.id JOIN blood_banks bb ON r.blood_bank_id=bb.id JOIN users a ON r.admin_id=a.id ORDER BY r.created_at DESC LIMIT 20")->fetchAll();

renderHead('Recommendations'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <h1 class="page-title">Blood Bank <span>Recommendations</span></h1>
  <p class="page-subtitle">Recommend suitable blood banks to donors or seekers based on their needs and location.</p>

  <?php if($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid-2" style="gap:1.75rem;align-items:start;">
    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">New Recommendation</h2>
      <div class="card">
        <form method="POST">
          <div style="display:flex;flex-direction:column;gap:.85rem;">
            <div class="form-group">
              <label>User (Donor or Seeker) *</label>
              <select name="user_id">
                <option value="">Select user</option>
                <?php foreach($users as $u): ?>
                  <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> — <?= ucfirst($u['role']) ?> <?= $u['blood_group']?"({$u['blood_group']})":'' ?> — <?= $u['city'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Recommended Blood Bank *</label>
              <select name="blood_bank_id">
                <option value="">Select blood bank</option>
                <?php foreach($banks as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?> — <?= $b['bank_city']??$b['city'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Reason / Notes</label>
              <textarea name="reason" placeholder="Why is this blood bank recommended for this user?"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Recommendation</button>
          </div>
        </form>
      </div>

      <!-- Blood Banks Summary -->
      <div style="margin-top:1.5rem;">
        <h2 class="section-title" style="margin-bottom:.85rem;">Blood Banks Overview</h2>
        <?php foreach($banks as $b): ?>
        <div class="bank-card" style="margin-bottom:.75rem;">
          <div class="bank-name"><?= htmlspecialchars($b['name']) ?></div>
          <div class="bank-addr">📍 <?= htmlspecialchars($b['address']) ?> · <?= $b['bank_city']??$b['city'] ?></div>
          <div style="font-size:.78rem;color:var(--text-muted);margin-top:.3rem;">📞 <?= $b['contact'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">Recent Recommendations</h2>
      <?php if(empty($recs)): ?>
        <div class="empty-state"><div class="icon">🏥</div><p>No recommendations made yet.</p></div>
      <?php else: foreach($recs as $i=>$r): ?>
      <div class="card card-sm" style="margin-bottom:.85rem;animation:fadeUp .35s <?= $i*.04 ?>s ease both;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">
          <div style="font-weight:600;"><?= htmlspecialchars($r['user_name']) ?></div>
          <span style="font-size:.68rem;font-family:var(--font-mono);color:var(--text-muted);"><?= ucfirst($r['user_role']) ?></span>
        </div>
        <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:.4rem;">
          → Recommended: <span style="color:var(--text);font-weight:600;"><?= htmlspecialchars($r['bank_name']) ?></span> in <?= htmlspecialchars($r['bank_city']) ?>
        </div>
        <?php if($r['reason']): ?>
          <div style="font-size:.78rem;color:var(--text-muted);padding:.6rem;background:var(--bg-panel);border-radius:6px;margin-bottom:.4rem;"><?= htmlspecialchars($r['reason']) ?></div>
        <?php endif; ?>
        <div style="font-size:.72rem;color:var(--text-muted);">by <?= htmlspecialchars($r['admin_name']) ?> · <?= date('d M Y H:i',strtotime($r['created_at'])) ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
</body></html>
