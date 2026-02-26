<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['blood_bank','admin']);

$db = getDB();
$uid = $_SESSION['user_id'];
$bank = $db->prepare("SELECT * FROM blood_banks WHERE user_id=?"); $bank->execute([$uid]); $bank=$bank->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $rid    = (int)$_POST['request_id'];
    $status = in_array($_POST['status'],['open','fulfilled','cancelled']) ? $_POST['status'] : 'open';
    $db->prepare("UPDATE blood_requests SET status=? WHERE id=?")->execute([$status,$rid]);
    header('Location: /boc/blood_bank/requests.php'); exit;
}

$filter   = $_GET['status'] ?? 'open';
$requests = $db->prepare("SELECT br.*,u.full_name,u.contact,u.email,u.city AS seeker_city FROM blood_requests br JOIN users u ON br.requester_id=u.id WHERE br.city=? AND br.status=? ORDER BY FIELD(br.urgency,'critical','urgent','normal'),br.created_at DESC");
$requests->execute([$bank['city'], $filter]);
$requests = $requests->fetchAll();

renderHead('Requests'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <a href="/boc/blood_bank/dashboard.php" class="back-link">← Back to Dashboard</a>
      <h1 class="page-title">Blood <span>Requests</span></h1>
      <p class="page-subtitle">Requests in <?= htmlspecialchars($bank['city']) ?></p>
    </div>
  </div>

  <div class="tab-bar">
    <a class="tab-btn <?= $filter==='open'?'active':'' ?>" href="?status=open">Open</a>
    <a class="tab-btn <?= $filter==='fulfilled'?'active':'' ?>" href="?status=fulfilled">Fulfilled</a>
    <a class="tab-btn <?= $filter==='cancelled'?'active':'' ?>" href="?status=cancelled">Cancelled</a>
  </div>

  <?php if(empty($requests)): ?>
    <div class="empty-state"><div class="icon">📋</div><p>No <?= $filter ?> requests in <?= htmlspecialchars($bank['city']) ?>.</p></div>
  <?php else: foreach($requests as $i=>$r): ?>
  <div class="card card-sm" style="margin-bottom:1rem;border-left:4px solid <?= $r['urgency']==='critical'?'#dc2626':($r['urgency']==='urgent'?'#ea580c':'#16a34a') ?>;animation:fadeUp .35s <?= $i*.04 ?>s ease both;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.75rem;">
      <div>
        <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;">
          <?= htmlspecialchars($r['patient_name']??'Patient') ?>
        </div>
        <div style="font-size:.78rem;color:var(--text-muted);">
          🏥 <?= htmlspecialchars($r['hospital_name']??'—') ?>
          &nbsp;·&nbsp; Requested by <?= htmlspecialchars($r['full_name']) ?>
          &nbsp;·&nbsp; <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
        </div>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <?= bloodGroupBadge($r['blood_group'], true) ?>
        <span class="urgency-badge urgency-<?= $r['urgency'] ?>"><?= ucfirst($r['urgency']) ?></span>
        <span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
      </div>
    </div>

    <div style="display:flex;gap:2rem;margin:.85rem 0;flex-wrap:wrap;">
      <div><div class="profile-label">Units Needed</div><div style="font-family:var(--font-mono);font-weight:600;"><?= $r['units_needed'] ?></div></div>
      <div><div class="profile-label">Contact</div><div class="mono" style="font-size:.85rem;"><?= htmlspecialchars($r['contact']) ?></div></div>
      <div><div class="profile-label">Email</div><div style="font-size:.82rem;"><?= htmlspecialchars($r['email']) ?></div></div>
    </div>

    <?php if($r['notes']): ?>
    <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:.75rem;padding:.7rem;background:var(--bg-panel);border-radius:var(--radius);">
      <?= htmlspecialchars($r['notes']) ?>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <a href="tel:<?= $r['contact'] ?>" class="btn btn-success btn-sm">📞 Call</a>
      <a href="mailto:<?= $r['email'] ?>" class="btn btn-secondary btn-sm">✉ Email</a>
      <?php if($r['status']==='open'): ?>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="status" value="fulfilled">
        <button type="submit" class="btn btn-success btn-sm">✓ Mark Fulfilled</button>
      </form>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="status" value="cancelled">
        <button type="submit" class="btn btn-danger btn-sm">✕ Cancel</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>
</body></html>
