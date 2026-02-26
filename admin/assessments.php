<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireAdmin();

$db  = getDB();
$uid = $_SESSION['user_id'];

$error = ''; $success = '';

// Pre-selected donor
$selectedDonor = isset($_GET['donor']) ? (int)$_GET['donor'] : 0;

// Handle new assessment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'donor_id'     => (int)($_POST['donor_id']??0),
        'donation_id'  => $_POST['donation_id'] ? (int)$_POST['donation_id'] : null,
        'hemoglobin'   => (float)($_POST['hemoglobin']??0),
        'blood_pressure'=> trim($_POST['blood_pressure']??''),
        'pulse_rate'   => (int)($_POST['pulse_rate']??0),
        'weight'       => (float)($_POST['weight']??0),
        'temperature'  => (float)($_POST['temperature']??0),
        'hiv_status'   => $_POST['hiv_status']??'Unknown',
        'hepatitis_b'  => $_POST['hepatitis_b']??'Unknown',
        'hepatitis_c'  => $_POST['hepatitis_c']??'Unknown',
        'syphilis'     => $_POST['syphilis']??'Unknown',
        'malaria'      => $_POST['malaria']??'Unknown',
        'passed'       => isset($_POST['passed']) ? 1 : 0,
        'notes'        => trim($_POST['notes']??''),
    ];
    if (!$d['donor_id']) {
        $error = 'Please select a donor.';
    } else {
        $db->prepare("INSERT INTO medical_assessments (donor_id,assessed_by,donation_id,hemoglobin,blood_pressure,pulse_rate,weight,temperature,hiv_status,hepatitis_b,hepatitis_c,syphilis,malaria,passed,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([$d['donor_id'],$uid,$d['donation_id'],$d['hemoglobin'],$d['blood_pressure'],$d['pulse_rate'],$d['weight'],$d['temperature'],$d['hiv_status'],$d['hepatitis_b'],$d['hepatitis_c'],$d['syphilis'],$d['malaria'],$d['passed'],$d['notes']]);

        // Notify donor
        $donor = $db->prepare("SELECT full_name FROM users WHERE id=?"); $donor->execute([$d['donor_id']]); $donor=$donor->fetch();
        $db->prepare("INSERT INTO notifications (user_id,sender_id,title,message,type) VALUES (?,?,?,?,?)")
           ->execute([$d['donor_id'],$uid,'Medical Assessment Ready',"Your medical assessment report has been completed. Result: ".($d['passed']?'PASSED ✓':'NOT PASSED ✗').". Log in to view details.",'assessment']);
        $success = "Assessment recorded for {$donor['full_name']} and donor notified.";
        $selectedDonor = $d['donor_id'];
    }
}

// Get all donors
$donors = $db->query("SELECT id,full_name,blood_group,city FROM users WHERE role='donor' ORDER BY full_name")->fetchAll();

// Get all assessments
$filterDonor = $selectedDonor ?: 0;
$asSql = "SELECT ma.*,u.full_name AS donor_name,u.blood_group,a.full_name AS assessor_name FROM medical_assessments ma JOIN users u ON ma.donor_id=u.id JOIN users a ON ma.assessed_by=a.id";
$asParams = [];
if ($filterDonor) { $asSql.=" WHERE ma.donor_id=?"; $asParams[]=$filterDonor; }
$asSql.=" ORDER BY ma.assessed_at DESC LIMIT 50";
$stmt = $db->prepare($asSql); $stmt->execute($asParams);
$assessments = $stmt->fetchAll();

// Get donations for selected donor (for linking)
$donorDonations = [];
if ($selectedDonor) {
    $dd = $db->prepare("SELECT d.id,d.donation_date,bb.name FROM donations d LEFT JOIN blood_banks bb ON d.blood_bank_id=bb.id WHERE d.donor_id=? ORDER BY d.donation_date DESC");
    $dd->execute([$selectedDonor]); $donorDonations=$dd->fetchAll();
}

renderHead('Assessments'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <h1 class="page-title">Medical <span>Assessments</span></h1>
      <p class="page-subtitle">Conduct and manage donor health evaluations.</p>
    </div>
  </div>

  <?php if($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid-2" style="gap:1.75rem;align-items:start;">
    <!-- Assessment Form -->
    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">New Assessment</h2>
      <div class="card">
        <form method="POST">
          <div style="display:flex;flex-direction:column;gap:.85rem;">
            <div class="form-group">
              <label>Donor *</label>
              <select name="donor_id" onchange="this.form.submit()" id="donorSel">
                <option value="">Select donor</option>
                <?php foreach($donors as $d): ?>
                  <option value="<?= $d['id'] ?>" <?= $d['id']===$selectedDonor?'selected':'' ?>>
                    <?= htmlspecialchars($d['full_name']) ?> — <?= $d['blood_group'] ?> (<?= $d['city'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if(!empty($donorDonations)): ?>
            <div class="form-group">
              <label>Link to Donation (optional)</label>
              <select name="donation_id">
                <option value="">Not linked</option>
                <?php foreach($donorDonations as $dd): ?>
                  <option value="<?= $dd['id'] ?>"><?= date('d M Y',strtotime($dd['donation_date'])) ?> — <?= htmlspecialchars($dd['name']??'No Bank') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
              <div class="form-group"><label>Hemoglobin (g/dL)</label><input type="number" name="hemoglobin" step="0.1" min="0" max="25" placeholder="13.5"></div>
              <div class="form-group"><label>Blood Pressure</label><input type="text" name="blood_pressure" placeholder="120/80"></div>
              <div class="form-group"><label>Pulse Rate (bpm)</label><input type="number" name="pulse_rate" min="0" max="250" placeholder="72"></div>
              <div class="form-group"><label>Weight (kg)</label><input type="number" name="weight" step="0.1" min="0" max="300" placeholder="65.0"></div>
              <div class="form-group"><label>Temperature (°C)</label><input type="number" name="temperature" step="0.1" min="30" max="45" placeholder="36.8"></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;">
              <?php foreach(['HIV'=>'hiv_status','Hepatitis B'=>'hepatitis_b','Hepatitis C'=>'hepatitis_c','Syphilis'=>'syphilis','Malaria'=>'malaria'] as $label=>$field): ?>
              <div class="form-group">
                <label><?= $label ?></label>
                <select name="<?= $field ?>">
                  <option value="Negative">Negative</option>
                  <option value="Positive">Positive</option>
                  <option value="Unknown">Unknown</option>
                </select>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="form-group">
              <label>Notes / Observations</label>
              <textarea name="notes" placeholder="Clinical notes, recommendations…"></textarea>
            </div>

            <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.875rem;">
              <input type="checkbox" name="passed" checked style="width:auto;">
              Donor passed assessment and is cleared for donation
            </label>

            <button type="submit" class="btn btn-primary">Save Assessment & Notify Donor</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Recent Assessments -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
        <h2 class="section-title"><?= $filterDonor?'Donor Assessments':'All Assessments' ?></h2>
        <?php if($filterDonor): ?><a href="/admin/assessments.php" class="btn btn-secondary btn-sm">Show All</a><?php endif; ?>
      </div>

      <?php if(empty($assessments)): ?>
        <div class="empty-state"><div class="icon">🩺</div><p>No assessments recorded yet.</p></div>
      <?php else: foreach($assessments as $i=>$a): ?>
      <div class="assessment-card <?= $a['passed']?'passed':'failed' ?>" style="margin-bottom:1rem;animation:fadeUp .35s <?= $i*.04 ?>s ease both;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem;">
          <div>
            <div style="font-weight:600;"><?= htmlspecialchars($a['donor_name']) ?> <?= bloodGroupBadge($a['blood_group']) ?></div>
            <div style="font-size:.74rem;color:var(--text-muted);">By <?= htmlspecialchars($a['assessor_name']) ?> · <?= date('d M Y', strtotime($a['assessed_at'])) ?></div>
          </div>
          <span class="status-badge <?= $a['passed']?'status-completed':'status-rejected' ?>"><?= $a['passed']?'Passed':'Failed' ?></span>
        </div>
        <div style="display:flex;gap:1.5rem;font-size:.78rem;flex-wrap:wrap;">
          <span>Hb: <strong><?= $a['hemoglobin'] ?></strong></span>
          <span>BP: <strong><?= $a['blood_pressure'] ?></strong></span>
          <span>Pulse: <strong><?= $a['pulse_rate'] ?></strong></span>
          <span>Wt: <strong><?= $a['weight'] ?>kg</strong></span>
        </div>
        <div class="disease-row" style="margin-top:.5rem;">
          <span class="disease-chip <?= $a['hiv_status']==='Negative'?'chip-neg':'chip-pos' ?>">HIV</span>
          <span class="disease-chip <?= $a['hepatitis_b']==='Negative'?'chip-neg':'chip-pos' ?>">HepB</span>
          <span class="disease-chip <?= $a['hepatitis_c']==='Negative'?'chip-neg':'chip-pos' ?>">HepC</span>
          <span class="disease-chip <?= $a['syphilis']==='Negative'?'chip-neg':'chip-pos' ?>">Syph</span>
          <span class="disease-chip <?= $a['malaria']==='Negative'?'chip-neg':'chip-pos' ?>">Mal</span>
        </div>
        <?php if($a['notes']): ?>
          <p style="margin-top:.5rem;font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($a['notes']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
</body></html>
