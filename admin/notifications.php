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
    $title   = trim($_POST['title']??'');
    $message = trim($_POST['message']??'');
    $type    = in_array($_POST['type']??'',['info','urgent','stock_alert','assessment','system','donation_request'])?$_POST['type']:'info';
    $target  = $_POST['target'] ?? 'all';
    $bgFilter= $_POST['blood_group'] ?? '';
    $cityFilter = trim($_POST['city'] ?? '');

    if (!$title || !$message) { $error = 'Title and message are required.'; }
    else {
        if ($target === 'broadcast') {
            // System-wide broadcast (user_id = NULL)
            $db->prepare("INSERT INTO notifications (user_id,sender_id,title,message,type) VALUES (NULL,?,?,?,?)")->execute([$uid,$title,$message,$type]);
            $success = 'System-wide broadcast sent successfully.';
        } else {
            $sql = "SELECT id FROM users WHERE role IN ('donor','seeker','blood_bank')";
            $params = [];
            if ($target === 'donors')     { $sql.=" AND role='donor'"; }
            if ($target === 'seekers')    { $sql.=" AND role='seeker'"; }
            if ($target === 'blood_banks'){ $sql.=" AND role='blood_bank'"; }
            if ($bgFilter) { $sql.=" AND blood_group=?"; $params[]=$bgFilter; }
            if ($cityFilter) { $sql.=" AND city LIKE ?"; $params[]="%$cityFilter%"; }
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $users = $stmt->fetchAll();
            $ins = $db->prepare("INSERT INTO notifications (user_id,sender_id,title,message,type) VALUES (?,?,?,?,?)");
            foreach($users as $u) $ins->execute([$u['id'],$uid,$title,$message,$type]);
            $success = "Notification sent to " . count($users) . " user(s).";
        }
    }
}

// Recent notifications
$recent = $db->query("SELECT n.*,u.full_name AS sender_name FROM notifications n LEFT JOIN users u ON n.sender_id=u.id ORDER BY n.created_at DESC LIMIT 20")->fetchAll();

renderHead('Send Notifications'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <h1 class="page-title">System <span>Notifications</span></h1>
  <p class="page-subtitle">Send targeted or broadcast notifications to users.</p>

  <?php if($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid-2" style="gap:1.75rem;align-items:start;">
    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">Compose Notification</h2>
      <div class="card">
        <form method="POST">
          <div style="display:flex;flex-direction:column;gap:.85rem;">
            <div class="form-group">
              <label>Notification Type</label>
              <select name="type">
                <option value="urgent">🚨 Urgent</option>
                <option value="stock_alert">📉 Stock Alert</option>
                <option value="donation_request">🩸 Donation Request</option>
                <option value="info" selected>ℹ️ Info</option>
                <option value="system">⚙️ System</option>
              </select>
            </div>
            <div class="form-group">
              <label>Title *</label>
              <input type="text" name="title" placeholder="Notification title">
            </div>
            <div class="form-group">
              <label>Message *</label>
              <textarea name="message" placeholder="Full notification message..."></textarea>
            </div>
            <hr class="divider">
            <div class="form-group">
              <label>Send To</label>
              <select name="target" id="targetSel" onchange="toggleFilters(this.value)">
                <option value="all">All Users</option>
                <option value="donors">All Donors</option>
                <option value="seekers">All Seekers</option>
                <option value="blood_banks">All Blood Banks</option>
                <option value="broadcast">System Broadcast (everyone)</option>
              </select>
            </div>
            <div id="extraFilters">
              <div class="form-group">
                <label>Filter by Blood Group (optional)</label>
                <select name="blood_group">
                  <option value="">All blood groups</option>
                  <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?><option value="<?= $bg ?>"><?= $bg ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="form-group" style="margin-top:.85rem;">
                <label>Filter by City (optional)</label>
                <input type="text" name="city" placeholder="e.g. Lahore">
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Send Notification</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Recent Notifications Log -->
    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">Recent Notifications</h2>
      <div class="card card-sm" style="padding:0;">
        <div class="notif-list">
          <?php if(empty($recent)): ?>
            <div class="empty-state"><div class="icon">🔔</div><p>No notifications sent yet.</p></div>
          <?php else:
            $icons=['urgent'=>'🚨','stock_alert'=>'📉','assessment'=>'📋','info'=>'ℹ️','system'=>'⚙️','donation_request'=>'🩸'];
            foreach($recent as $n): ?>
          <div class="notif-item">
            <div class="notif-icon type-<?= $n['type'] ?>"><?= $icons[$n['type']]??'🔔' ?></div>
            <div style="flex:1;min-width:0;">
              <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
              <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
              <div class="notif-time">
                <?= $n['user_id']?'To user #'.$n['user_id']:'📢 Broadcast' ?> ·
                by <?= htmlspecialchars($n['sender_name']??'System') ?> ·
                <?= date('d M H:i',strtotime($n['created_at'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function toggleFilters(val) {
  document.getElementById('extraFilters').style.opacity = val==='broadcast'?.4:1;
  document.getElementById('extraFilters').style.pointerEvents = val==='broadcast'?'none':'';
}
</script>
</body></html>
