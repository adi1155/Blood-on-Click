<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['blood_bank','admin']);

$db  = getDB();
$uid = $_SESSION['user_id'];
$bank = $db->prepare("SELECT * FROM blood_banks WHERE user_id=?"); $bank->execute([$uid]); $bank=$bank->fetch();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title'] ?? '');
    $message    = trim($_POST['message'] ?? '');
    $type       = in_array($_POST['type']??'',['urgent','stock_alert','donation_request','info']) ? $_POST['type'] : 'info';
    $targetBg   = $_POST['target_blood_group'] ?? '';
    $targetCity = trim($_POST['target_city'] ?? '');
    $broadcast  = isset($_POST['broadcast']);

    if (!$title || !$message) {
        $error = 'Title and message are required.';
    } else {
        if ($broadcast) {
            // Notify all donors
            $donors = $db->query("SELECT id FROM users WHERE role='donor'")->fetchAll();
            $stmt   = $db->prepare("INSERT INTO notifications (user_id,sender_id,title,message,type) VALUES (?,?,?,?,?)");
            foreach($donors as $d) $stmt->execute([$d['id'], $uid, $title, $message, $type]);
            $success = "Notification sent to all " . count($donors) . " donors.";
        } else {
            // Target specific donors
            $sql = "SELECT id FROM users WHERE role='donor'";
            $params = [];
            if ($targetBg) { $sql .= " AND blood_group=?"; $params[] = $targetBg; }
            if ($targetCity) { $sql .= " AND city LIKE ?"; $params[] = "%$targetCity%"; }
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $donors = $stmt->fetchAll();
            $ins = $db->prepare("INSERT INTO notifications (user_id,sender_id,title,message,type) VALUES (?,?,?,?,?)");
            foreach($donors as $d) $ins->execute([$d['id'], $uid, $title, $message, $type]);
            $success = "Notification sent to " . count($donors) . " targeted donor(s).";
        }
    }
}

renderHead('Send Notification'); ?>
<?php renderNav(); ?>
<div class="page animate-in" style="max-width:700px;">
  <a href="/boc/blood_bank/dashboard.php" class="back-link">← Back to Dashboard</a>
  <h1 class="page-title">Send <span>Notification</span></h1>
  <p class="page-subtitle">Alert donors or other blood banks about urgent needs or stock status.</p>

  <?php if($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="POST">
      <div style="display:flex;flex-direction:column;gap:1rem;">

        <div class="form-group">
          <label>Notification Type</label>
          <select name="type">
            <option value="urgent">🚨 Urgent Emergency</option>
            <option value="stock_alert">📉 Stock Alert</option>
            <option value="donation_request">🩸 Donation Request</option>
            <option value="info">ℹ️ General Info</option>
          </select>
        </div>

        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" placeholder="e.g. URGENT: O- blood needed at Services Hospital">
        </div>

        <div class="form-group">
          <label>Message *</label>
          <textarea name="message" placeholder="Describe the need, location, or urgent request in detail..."></textarea>
        </div>

        <hr class="divider">
        <div style="font-size:.82rem;font-weight:600;color:var(--text-label);text-transform:uppercase;letter-spacing:.06em;">Target Audience</div>

        <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.88rem;">
          <input type="checkbox" name="broadcast" id="broadcast" style="width:auto;" onchange="toggleTarget(this)">
          Broadcast to ALL donors (ignore filters below)
        </label>

        <div id="targetFilters" style="display:flex;flex-direction:column;gap:.85rem;">
          <div class="form-group">
            <label>Target Blood Group <small style="font-weight:400;text-transform:none;">(leave empty for all)</small></label>
            <select name="target_blood_group">
              <option value="">All blood groups</option>
              <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                <option value="<?= $bg ?>"><?= $bg ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Target City <small style="font-weight:400;text-transform:none;">(leave empty for all cities)</small></label>
            <input type="text" name="target_city" placeholder="e.g. Lahore" value="<?= htmlspecialchars($bank['city']??'') ?>">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full">Send Notification</button>
      </div>
    </form>
  </div>
</div>
<script>
function toggleTarget(cb) {
  document.getElementById('targetFilters').style.opacity = cb.checked ? '.4' : '1';
  document.getElementById('targetFilters').style.pointerEvents = cb.checked ? 'none' : '';
}
</script>
</body></html>
