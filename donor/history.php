<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['donor','admin']);

$db  = getDB();
$uid = $_SESSION['user_id'];
$donations = $db->prepare("SELECT d.*,bb.name AS bank_name,bb.city AS bank_city FROM donations d LEFT JOIN blood_banks bb ON d.blood_bank_id=bb.id WHERE d.donor_id=? ORDER BY d.donation_date DESC");
$donations->execute([$uid]);
$donations = $donations->fetchAll();
$total = count($donations);
$completed = array_filter($donations, fn($d)=>$d['status']==='completed');
$totalUnits = array_sum(array_column(iterator_to_array((function($a){foreach($a as $i)yield $i;})($completed)), 'units'));

renderHead('Donation History'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <a href="/donor/dashboard.php" class="back-link">← Back to Dashboard</a>
  <h1 class="page-title">Donation <span>History</span></h1>
  <p class="page-subtitle">Complete record of all your blood donations.</p>

  <div class="grid-4 stagger" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-value"><?= $total ?></div><div class="stat-label">Total Donations</div></div>
    <div class="stat-card"><div class="stat-value"><?= count($completed) ?></div><div class="stat-label">Completed</div></div>
    <div class="stat-card"><div class="stat-value"><?= number_format($totalUnits, 1) ?></div><div class="stat-label">Units Donated</div></div>
    <div class="stat-card"><div class="stat-value" style="font-size:1rem;"><?= $donations ? date('d M Y', strtotime($donations[0]['donation_date'])) : '—' ?></div><div class="stat-label">Last Donation</div></div>
  </div>

  <div class="table-wrapper">
    <table>
      <thead><tr><th>#</th><th>Date</th><th>Blood Bank</th><th>City</th><th>Blood Group</th><th>Units</th><th>Status</th><th>Notes</th></tr></thead>
      <tbody>
        <?php if(empty($donations)): ?>
          <tr><td colspan="8" class="text-center" style="padding:3rem;color:var(--text-muted)">No donation records found.</td></tr>
        <?php else: foreach($donations as $i=>$d): ?>
        <tr style="animation:fadeUp .3s ease both;animation-delay:<?= $i*.03 ?>s">
          <td class="mono text-muted" style="font-size:.75rem;"><?= $i+1 ?></td>
          <td class="mono" style="font-size:.82rem;"><?= date('d M Y', strtotime($d['donation_date'])) ?></td>
          <td><?= htmlspecialchars($d['bank_name']??'—') ?></td>
          <td><?= htmlspecialchars($d['bank_city']??'—') ?></td>
          <td><?= bloodGroupBadge($d['blood_group']) ?></td>
          <td class="mono"><?= $d['units'] ?></td>
          <td><span class="status-badge status-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
          <td style="font-size:.8rem;color:var(--text-muted);"><?= htmlspecialchars($d['notes']??'—') ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body></html>
