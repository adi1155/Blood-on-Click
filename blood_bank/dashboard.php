<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['blood_bank','admin']);

$db  = getDB();
$uid = $_SESSION['user_id'];

$bbStmt = $db->prepare("SELECT * FROM blood_banks WHERE user_id=?");
$bbStmt->execute([$uid]);
$bank = $bbStmt->fetch();

if (!$bank) {
    header('Location: /dashboard.php'); exit;
}
$bid = $bank['id'];

$stock   = $db->prepare("SELECT * FROM blood_stock WHERE blood_bank_id=? ORDER BY blood_group");
$stock->execute([$bid]); $stock = $stock->fetchAll();

$requests = $db->prepare("SELECT br.*,u.full_name,u.contact,u.email FROM blood_requests br JOIN users u ON br.requester_id=u.id WHERE br.city=? AND br.status='open' ORDER BY FIELD(br.urgency,'critical','urgent','normal'),br.created_at DESC LIMIT 10");
$requests->execute([$bank['city']]); $requests = $requests->fetchAll();

$criticalCount = 0;
foreach($stock as $s) if($s['units_available'] <= $s['threshold_alert']) $criticalCount++;

$totalUnits   = array_sum(array_column($stock, 'units_available'));
$openRequests = count($requests);

$recentDonors = $db->prepare("SELECT d.*,u.full_name,u.blood_group,u.contact FROM donations d JOIN users u ON d.donor_id=u.id WHERE d.blood_bank_id=? ORDER BY d.donation_date DESC LIMIT 8");
$recentDonors->execute([$bid]); $recentDonors=$recentDonors->fetchAll();

renderHead('Blood Bank Dashboard'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <h1 class="page-title"><span><?= htmlspecialchars($bank['name']) ?></span></h1>
      <p class="page-subtitle">📍 <?= htmlspecialchars($bank['address']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($bank['city']) ?></p>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
      <a href="/blood_bank/stock.php" class="btn btn-secondary">📊 Manage Stock</a>
      <a href="/blood_bank/send_notification.php" class="btn btn-warning">🔔 Send Alert</a>
      <a href="/blood_bank/requests.php" class="btn btn-primary">📋 All Requests</a>
    </div>
  </div>

  <?php if($criticalCount > 0): ?>
  <div class="alert alert-warning" style="margin-bottom:1.5rem;">
    ⚠ <strong><?= $criticalCount ?> blood group(s)</strong> are below the alert threshold. Consider sending a donor notification immediately.
    <a href="/blood_bank/send_notification.php" style="margin-left:.5rem;color:#fb923c;font-weight:600;">Send Alert →</a>
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="grid-4 stagger" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-value"><?= $totalUnits ?></div><div class="stat-label">Total Units in Stock</div></div>
    <div class="stat-card"><div class="stat-value" style="color:<?= $criticalCount?'#f87171':'#10b981' ?>"><?= $criticalCount ?></div><div class="stat-label">Low Stock Alerts</div></div>
    <div class="stat-card"><div class="stat-value"><?= $openRequests ?></div><div class="stat-label">Open Requests (City)</div></div>
    <div class="stat-card"><div class="stat-value"><?= count($recentDonors) ?></div><div class="stat-label">Recent Donations</div></div>
  </div>

  <div class="grid-2" style="gap:1.5rem;">
    <!-- Stock Overview -->
    <div>
      <div class="section-header"><h2 class="section-title">Blood Stock Levels</h2><a href="/blood_bank/stock.php" class="btn btn-secondary btn-sm">Edit Stock</a></div>
      <div class="card" style="padding:1rem;">
        <?php
        $maxCap = 50; // display max
        foreach($stock as $s):
          $units = (int)$s['units_available'];
          $pct   = min(100, round($units/$maxCap*100));
          $cls   = $units <= $s['threshold_alert'] ? 'danger' : ($units <= $s['threshold_alert']*2 ? 'warn' : 'ok');
          $color = bloodGroupColor($s['blood_group']);
        ?>
        <div class="stock-bar-wrap" style="margin-bottom:.85rem;">
          <div class="stock-bar-header">
            <span style="font-family:var(--font-mono);font-size:.8rem;font-weight:600;color:<?= $color ?>"><?= $s['blood_group'] ?></span>
            <span style="font-size:.75rem;color:var(--text-muted);font-family:var(--font-mono);">
              <?= $units ?> units
              <?php if($units <= $s['threshold_alert']): ?><span style="color:#f87171;"> ⚠ LOW</span><?php endif; ?>
            </span>
          </div>
          <div class="stock-bar-bg"><div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Incoming Requests -->
    <div>
      <div class="section-header"><h2 class="section-title">Incoming Requests</h2><a href="/blood_bank/requests.php" class="btn btn-secondary btn-sm">View All</a></div>
      <?php if(empty($requests)): ?>
        <div class="empty-state"><div class="icon">📋</div><p>No open requests in <?= htmlspecialchars($bank['city']) ?>.</p></div>
      <?php else: foreach($requests as $r): ?>
      <div class="card card-sm" style="margin-bottom:.75rem;border-left:3px solid <?= $r['urgency']==='critical'?'#dc2626':($r['urgency']==='urgent'?'#ea580c':'#16a34a') ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">
          <div>
            <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($r['patient_name']??$r['full_name']) ?></div>
            <div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($r['hospital_name']??'') ?></div>
          </div>
          <div style="display:flex;gap:.4rem;align-items:center;">
            <?= bloodGroupBadge($r['blood_group']) ?>
            <span class="urgency-badge urgency-<?= $r['urgency'] ?>"><?= ucfirst($r['urgency']) ?></span>
          </div>
        </div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:.6rem;">
          Units needed: <strong style="color:var(--text);"><?= $r['units_needed'] ?></strong> &nbsp;·&nbsp;
          <?= date('d M H:i', strtotime($r['created_at'])) ?>
        </div>
        <div style="display:flex;gap:.4rem;">
          <a href="tel:<?= htmlspecialchars($r['contact']) ?>" class="btn btn-success btn-sm">📞 <?= htmlspecialchars($r['contact']) ?></a>
          <a href="mailto:<?= htmlspecialchars($r['email']) ?>" class="btn btn-secondary btn-sm">✉ Email</a>
        </div>
      </div>
      <?php endforeach; endif; ?>

      <!-- Recent Donors to this bank -->
      <div style="margin-top:1.5rem;">
        <div class="section-header"><h2 class="section-title">Recent Donors</h2></div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Name</th><th>Group</th><th>Date</th><th>Contact</th></tr></thead>
            <tbody>
              <?php foreach($recentDonors as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['full_name']) ?></td>
                <td><?= bloodGroupBadge($d['blood_group']) ?></td>
                <td class="mono" style="font-size:.78rem;"><?= date('d M Y', strtotime($d['donation_date'])) ?></td>
                <td class="mono" style="font-size:.78rem;"><?= htmlspecialchars($d['contact']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body></html>
