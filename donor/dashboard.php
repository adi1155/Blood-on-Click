<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['donor','admin']);

$db  = getDB();
$uid = $_SESSION['user_id'];

$me = $db->prepare("SELECT * FROM users WHERE id=?"); $me->execute([$uid]); $me = $me->fetch();

$donations  = $db->prepare("SELECT d.*,bb.name AS bank_name FROM donations d LEFT JOIN blood_banks bb ON d.blood_bank_id=bb.id WHERE d.donor_id=? ORDER BY d.donation_date DESC LIMIT 5"); $donations->execute([$uid]); $donations=$donations->fetchAll();
$donCount   = $db->prepare("SELECT COUNT(*) FROM donations WHERE donor_id=? AND status='completed'"); $donCount->execute([$uid]); $donCount=$donCount->fetchColumn();
$lastAssess = $db->prepare("SELECT * FROM medical_assessments WHERE donor_id=? ORDER BY assessed_at DESC LIMIT 1"); $lastAssess->execute([$uid]); $lastAssess=$lastAssess->fetch();
$nearbyBanks= $db->query("SELECT bb.*,u.city FROM blood_banks bb JOIN users u ON bb.user_id=u.id WHERE bb.is_active=1 LIMIT 3")->fetchAll();
$notifs     = $db->prepare("SELECT * FROM notifications WHERE (user_id=? OR user_id IS NULL) ORDER BY created_at DESC LIMIT 5"); $notifs->execute([$uid]); $notifs=$notifs->fetchAll();

renderHead('Donor Dashboard');
?>
<?php renderNav(); ?>
<div class="page animate-in">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <h1 class="page-title">Welcome, <span><?= htmlspecialchars($me['full_name']) ?></span></h1>
      <p class="page-subtitle">Donor Dashboard &nbsp;·&nbsp; <?= bloodGroupBadge($me['blood_group'], true) ?></p>
    </div>
    <div style="display:flex;gap:.75rem;">
      <a href="/donor/history.php" class="btn btn-secondary">📋 My History</a>
      <a href="/search.php" class="btn btn-primary">🏥 Find Banks</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid-4 stagger" style="margin-bottom:2rem;">
    <div class="stat-card">
      <div class="stat-value"><?= $donCount ?></div>
      <div class="stat-label">Total Donations</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $me['last_donation_date'] ? date('d M', strtotime($me['last_donation_date'])) : '—' ?></div>
      <div class="stat-label">Last Donation</div>
    </div>
    <div class="stat-card">
      <?php
      $nextDate = $me['last_donation_date'] ? date('Y-m-d', strtotime($me['last_donation_date'].' +90 days')) : null;
      $ready = !$nextDate || $nextDate <= date('Y-m-d');
      ?>
      <div class="stat-value" style="font-size:1.3rem;color:<?= $ready ? '#10b981' : '#f59e0b' ?>">
        <?= $ready ? '✓ Ready' : date('d M', strtotime($nextDate)) ?>
      </div>
      <div class="stat-label">Next Eligibility</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:<?= $me['is_available'] ? '#10b981' : '#f87171' ?>">
        <?= $me['is_available'] ? 'Active' : 'Inactive' ?>
      </div>
      <div class="stat-label">Availability</div>
    </div>
  </div>

  <div class="grid-2" style="gap:1.5rem;">
    <!-- Left column -->
    <div>
      <div class="section-header"><h2 class="section-title">Recent Donations</h2><a href="/donor/history.php" class="btn btn-secondary btn-sm">View All</a></div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Date</th><th>Blood Bank</th><th>Group</th><th>Status</th></tr></thead>
          <tbody>
            <?php if(empty($donations)): ?>
              <tr><td colspan="4" class="text-center text-muted" style="padding:2rem">No donations yet.</td></tr>
            <?php else: foreach($donations as $d): ?>
            <tr>
              <td class="mono" style="font-size:.8rem;"><?= date('d M Y', strtotime($d['donation_date'])) ?></td>
              <td><?= htmlspecialchars($d['bank_name'] ?? '—') ?></td>
              <td><?= bloodGroupBadge($d['blood_group']) ?></td>
              <td><span class="status-badge status-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Latest Assessment -->
      <?php if($lastAssess): ?>
      <div style="margin-top:1.5rem;">
        <div class="section-header"><h2 class="section-title">Latest Health Report</h2><a href="/donor/assessments.php" class="btn btn-secondary btn-sm">All Reports</a></div>
        <div class="assessment-card <?= $lastAssess['passed']?'passed':'failed' ?>">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
            <span style="font-size:.8rem;color:var(--text-muted);"><?= date('d M Y', strtotime($lastAssess['assessed_at'])) ?></span>
            <?php if($lastAssess['passed']): ?>
              <span class="status-badge status-completed">✓ Passed</span>
            <?php else: ?>
              <span class="status-badge status-rejected">✗ Not Passed</span>
            <?php endif; ?>
          </div>
          <div class="vitals-grid">
            <div class="vital"><div class="vital-val"><?= $lastAssess['hemoglobin'] ?></div><div class="vital-name">Hemoglobin (g/dL)</div></div>
            <div class="vital"><div class="vital-val"><?= $lastAssess['blood_pressure'] ?></div><div class="vital-name">Blood Pressure</div></div>
            <div class="vital"><div class="vital-val"><?= $lastAssess['pulse_rate'] ?> bpm</div><div class="vital-name">Pulse Rate</div></div>
          </div>
          <div class="disease-row">
            <?php foreach(['HIV'=>$lastAssess['hiv_status'],'Hep-B'=>$lastAssess['hepatitis_b'],'Hep-C'=>$lastAssess['hepatitis_c'],'Syphilis'=>$lastAssess['syphilis'],'Malaria'=>$lastAssess['malaria']] as $k=>$v): ?>
              <span class="disease-chip <?= $v==='Negative'?'chip-neg':'chip-pos' ?>"><?= $k ?>: <?= $v ?></span>
            <?php endforeach; ?>
          </div>
          <?php if($lastAssess['notes']): ?><p style="margin-top:.75rem;font-size:.8rem;color:var(--text-muted);"><?= htmlspecialchars($lastAssess['notes']) ?></p><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right column -->
    <div>
      <!-- Notifications -->
      <div class="section-header"><h2 class="section-title">Notifications</h2><a href="/notifications.php" class="btn btn-secondary btn-sm">All</a></div>
      <div class="card card-sm" style="padding:0;">
        <div class="notif-list">
          <?php if(empty($notifs)): ?>
            <div class="empty-state"><div class="icon">🔔</div><p>No notifications.</p></div>
          <?php else: foreach($notifs as $n):
            $icons=['urgent'=>'🚨','stock_alert'=>'📉','assessment'=>'📋','info'=>'ℹ️','system'=>'⚙️','donation_request'=>'🩸'];
          ?>
            <div class="notif-item <?= !$n['is_read']?'unread':'' ?>">
              <div class="notif-icon type-<?= $n['type'] ?>"><?= $icons[$n['type']]??'🔔' ?></div>
              <div style="flex:1;min-width:0;">
                <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                <div class="notif-time"><?= date('d M H:i', strtotime($n['created_at'])) ?></div>
              </div>
              <?php if(!$n['is_read']): ?><div class="unread-dot"></div><?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Nearby Blood Banks -->
      <div style="margin-top:1.5rem;">
        <div class="section-header"><h2 class="section-title">Nearby Blood Banks</h2><a href="/search.php" class="btn btn-secondary btn-sm">Find More</a></div>
        <?php foreach($nearbyBanks as $bb): ?>
        <div class="bank-card" style="margin-bottom:.85rem;">
          <div class="bank-name"><?= htmlspecialchars($bb['name']) ?></div>
          <div class="bank-addr">📍 <?= htmlspecialchars($bb['address']) ?></div>
          <div style="display:flex;gap:.5rem;margin-top:.5rem;">
            <a href="tel:<?= $bb['contact'] ?>" class="btn btn-secondary btn-sm">📞 Call</a>
            <a href="/search.php?bank=<?= $bb['id'] ?>" class="btn btn-secondary btn-sm">View Stock</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</body></html>
