<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['donor','admin']);

$db  = getDB();
$uid = $_SESSION['user_id'];
$assessments = $db->prepare("SELECT ma.*,u.full_name AS assessor_name FROM medical_assessments ma JOIN users u ON ma.assessed_by=u.id WHERE ma.donor_id=? ORDER BY ma.assessed_at DESC");
$assessments->execute([$uid]);
$assessments = $assessments->fetchAll();

renderHead('Health Reports'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <a href="/donor/dashboard.php" class="back-link">← Back to Dashboard</a>
  <h1 class="page-title">Health <span>Reports</span></h1>
  <p class="page-subtitle">Your complete medical assessment history from each donation.</p>

  <?php if(empty($assessments)): ?>
    <div class="empty-state"><div class="icon">🩺</div><p>No medical assessments on record yet.</p></div>
  <?php else: foreach($assessments as $i=>$a): ?>
  <div class="assessment-card <?= $a['passed']?'passed':'failed' ?>" style="margin-bottom:1.25rem;animation:fadeUp .4s ease <?= $i*.05 ?>s both;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
      <div>
        <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;">Assessment #<?= $i+1 ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);">
          <?= date('d M Y, H:i', strtotime($a['assessed_at'])) ?> &nbsp;·&nbsp; Assessed by <?= htmlspecialchars($a['assessor_name']) ?>
        </div>
      </div>
      <span class="status-badge <?= $a['passed']?'status-completed':'status-rejected' ?>"><?= $a['passed']?'✓ Passed':'✗ Not Passed' ?></span>
    </div>

    <div class="vitals-grid">
      <div class="vital"><div class="vital-val"><?= $a['hemoglobin'] ?> g/dL</div><div class="vital-name">Hemoglobin</div></div>
      <div class="vital"><div class="vital-val"><?= $a['blood_pressure'] ?></div><div class="vital-name">Blood Pressure</div></div>
      <div class="vital"><div class="vital-val"><?= $a['pulse_rate'] ?> bpm</div><div class="vital-name">Pulse Rate</div></div>
      <div class="vital"><div class="vital-val"><?= $a['weight'] ?> kg</div><div class="vital-name">Weight</div></div>
      <div class="vital"><div class="vital-val"><?= $a['temperature'] ?> °C</div><div class="vital-name">Temperature</div></div>
    </div>

    <div style="margin-top:.85rem;">
      <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:.5rem;">Screening Tests</div>
      <div class="disease-row">
        <span class="disease-chip <?= $a['hiv_status']==='Negative'?'chip-neg':'chip-pos' ?>">HIV: <?= $a['hiv_status'] ?></span>
        <span class="disease-chip <?= $a['hepatitis_b']==='Negative'?'chip-neg':'chip-pos' ?>">Hepatitis B: <?= $a['hepatitis_b'] ?></span>
        <span class="disease-chip <?= $a['hepatitis_c']==='Negative'?'chip-neg':'chip-pos' ?>">Hepatitis C: <?= $a['hepatitis_c'] ?></span>
        <span class="disease-chip <?= $a['syphilis']==='Negative'?'chip-neg':'chip-pos' ?>">Syphilis: <?= $a['syphilis'] ?></span>
        <span class="disease-chip <?= $a['malaria']==='Negative'?'chip-neg':'chip-pos' ?>">Malaria: <?= $a['malaria'] ?></span>
      </div>
    </div>

    <?php if($a['notes']): ?>
    <div style="margin-top:.85rem;padding:.85rem;background:var(--bg-panel);border-radius:var(--radius);font-size:.82rem;color:var(--text-muted);border-left:3px solid var(--border-red);">
      📝 <?= htmlspecialchars($a['notes']) ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; endif; ?>
</div>
</body></html>
