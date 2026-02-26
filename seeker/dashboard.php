<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['seeker','admin']);

$db  = getDB();
$uid = $_SESSION['user_id'];
$me  = $db->prepare("SELECT * FROM users WHERE id=?"); $me->execute([$uid]); $me=$me->fetch();

$myRequests = $db->prepare("SELECT * FROM blood_requests WHERE requester_id=? ORDER BY created_at DESC LIMIT 5"); $myRequests->execute([$uid]); $myRequests=$myRequests->fetchAll();
$openCount  = $db->prepare("SELECT COUNT(*) FROM blood_requests WHERE requester_id=? AND status='open'"); $openCount->execute([$uid]); $openCount=$openCount->fetchColumn();

// Nearby available donors (same city)
$donors = $db->prepare("SELECT u.full_name,u.blood_group,u.city,u.contact,u.last_donation_date,u.is_available FROM users u WHERE u.role='donor' AND u.city LIKE ? AND u.is_available=1 ORDER BY u.last_donation_date ASC LIMIT 6");
$donors->execute(["%{$me['city']}%"]);
$donors = $donors->fetchAll();

// Blood banks nearby
$banks = $db->prepare("SELECT bb.*,u.city FROM blood_banks bb JOIN users u ON bb.user_id=u.id WHERE u.city LIKE ? AND bb.is_active=1");
$banks->execute(["%{$me['city']}%"]);
$banks = $banks->fetchAll();

renderHead('Seeker Dashboard'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <h1 class="page-title">Hello, <span><?= htmlspecialchars($me['full_name']) ?></span></h1>
      <p class="page-subtitle">Seeker Dashboard &nbsp;·&nbsp; <?= htmlspecialchars($me['city']) ?></p>
    </div>
    <div style="display:flex;gap:.75rem;">
      <a href="/boc/seeker/search.php" class="btn btn-primary">🔍 Find Donors</a>
      <a href="/boc/seeker/request.php" class="btn btn-secondary">➕ New Request</a>
    </div>
  </div>

  <div class="grid-4 stagger" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-value"><?= $openCount ?></div><div class="stat-label">Open Requests</div></div>
    <div class="stat-card"><div class="stat-value"><?= count($donors) ?></div><div class="stat-label">Donors Near You</div></div>
    <div class="stat-card"><div class="stat-value"><?= count($banks) ?></div><div class="stat-label">Banks in Your City</div></div>
    <div class="stat-card"><div class="stat-value"><?= count($myRequests) ?></div><div class="stat-label">Total Requests</div></div>
  </div>

  <div class="grid-2" style="gap:1.5rem;">
    <div>
      <!-- My Requests -->
      <div class="section-header"><h2 class="section-title">My Requests</h2><a href="/boc/seeker/request.php" class="btn btn-primary btn-sm">+ New</a></div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Blood Group</th><th>Units</th><th>Hospital</th><th>Urgency</th><th>Status</th></tr></thead>
          <tbody>
            <?php if(empty($myRequests)): ?>
              <tr><td colspan="5" class="text-center text-muted" style="padding:2rem">No requests yet.</td></tr>
            <?php else: foreach($myRequests as $r): ?>
            <tr>
              <td><?= bloodGroupBadge($r['blood_group']) ?></td>
              <td class="mono"><?= $r['units_needed'] ?></td>
              <td style="font-size:.82rem;"><?= htmlspecialchars($r['hospital_name']??'—') ?></td>
              <td><span class="urgency-badge urgency-<?= $r['urgency'] ?>"><?= ucfirst($r['urgency']) ?></span></td>
              <td><span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Nearby Blood Banks -->
      <div style="margin-top:1.5rem;">
        <div class="section-header"><h2 class="section-title">Blood Banks Near You</h2><a href="/boc/search.php" class="btn btn-secondary btn-sm">More</a></div>
        <?php foreach($banks as $b): ?>
        <div class="bank-card" style="margin-bottom:.75rem;">
          <div class="bank-name"><?= htmlspecialchars($b['name']) ?></div>
          <div class="bank-addr">📍 <?= htmlspecialchars($b['address']) ?></div>
          <a href="tel:<?= $b['contact'] ?>" class="btn btn-success btn-sm" style="margin-top:.5rem;">📞 <?= htmlspecialchars($b['contact']) ?></a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Available Donors (color-coded blood group icons) -->
    <div>
      <div class="section-header"><h2 class="section-title">Available Donors Near You</h2><a href="/boc/seeker/search.php" class="btn btn-secondary btn-sm">Search</a></div>
      <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem;">Color-coded by blood group. Click a donor for contact details.</p>
      <div class="grid-auto">
        <?php foreach($donors as $d):
          $color = bloodGroupColor($d['blood_group']);
          $canDonate = !$d['last_donation_date'] || date('Y-m-d', strtotime($d['last_donation_date'].' +90 days')) <= date('Y-m-d');
        ?>
        <div onclick="this.nextElementSibling.classList.toggle('hidden')" style="background:var(--bg-card);border:1px solid <?= $color ?>44;border-radius:var(--radius-lg);padding:1rem;cursor:pointer;transition:all var(--t);" onmouseover="this.style.borderColor='<?= $color ?>'" onmouseout="this.style.borderColor='<?= $color ?>44'">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">
            <div style="width:40px;height:40px;border-radius:50%;background:<?= $color ?>22;border:2px solid <?= $color ?>66;display:flex;align-items:center;justify-content:center;font-family:var(--font-mono);font-size:.78rem;font-weight:600;color:<?= $color ?>;">
              <?= $d['blood_group'] ?>
            </div>
            <span style="font-size:.65rem;background:<?= $canDonate?'rgba(16,185,129,.15)':'rgba(234,88,12,.12)' ?>;color:<?= $canDonate?'#10b981':'#fb923c' ?>;border-radius:4px;padding:.1rem .4rem;font-family:var(--font-mono);">
              <?= $canDonate?'Ready':'Cooldown' ?>
            </span>
          </div>
          <div style="font-weight:600;font-size:.875rem;"><?= htmlspecialchars($d['full_name']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted);">📍 <?= htmlspecialchars($d['city']) ?></div>
        </div>
        <div class="hidden" style="background:var(--bg-panel);border-radius:0 0 var(--radius) var(--radius);padding:.75rem 1rem;margin-top:-8px;border:1px solid <?= $color ?>33;border-top:none;font-size:.82rem;">
          📞 <a href="tel:<?= $d['contact'] ?>" style="color:<?= $color ?>"><?= htmlspecialchars($d['contact']) ?></a>
          <div style="font-size:.75rem;color:var(--text-muted);margin-top:.25rem;">Last donated: <?= $d['last_donation_date'] ? date('d M Y',strtotime($d['last_donation_date'])) : 'Never' ?></div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($donors)): ?>
          <div style="grid-column:1/-1;"><div class="empty-state"><div class="icon">🔍</div><p>No available donors found in <?= htmlspecialchars($me['city']) ?>.<br><a href="/boc/seeker/search.php">Search other cities →</a></p></div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<style>.hidden{display:none!important;}</style>
</body></html>
