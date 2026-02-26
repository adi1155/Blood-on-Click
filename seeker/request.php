<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['seeker','admin']);

$db  = getDB();
$uid = $_SESSION['user_id'];
$me  = $db->prepare("SELECT * FROM users WHERE id=?"); $me->execute([$uid]); $me=$me->fetch();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'blood_group'  => $_POST['blood_group'] ?? '',
        'units_needed' => max(1,(int)($_POST['units_needed']??1)),
        'city'         => trim($_POST['city']??''),
        'hospital_name'=> trim($_POST['hospital_name']??''),
        'patient_name' => trim($_POST['patient_name']??''),
        'urgency'      => in_array($_POST['urgency']??'',['normal','urgent','critical'])?$_POST['urgency']:'normal',
        'notes'        => trim($_POST['notes']??''),
    ];
    if (!$d['blood_group'] || !$d['city'])
        $error = 'Blood group and city are required.';
    else {
        $db->prepare("INSERT INTO blood_requests (requester_id,requester_type,blood_group,units_needed,city,hospital_name,patient_name,urgency,notes) VALUES (?,?,?,?,?,?,?,?,?)")
           ->execute([$uid,'seeker',$d['blood_group'],$d['units_needed'],$d['city'],$d['hospital_name'],$d['patient_name'],$d['urgency'],$d['notes']]);

        // Notify matching donors in same city
        $donors = $db->prepare("SELECT id FROM users WHERE role='donor' AND blood_group=? AND city LIKE ? AND is_available=1");
        $donors->execute([$d['blood_group'],"%{$d['city']}%"]);
        $donors = $donors->fetchAll();
        $ins = $db->prepare("INSERT INTO notifications (user_id,sender_id,title,message,type) VALUES (?,?,?,?,?)");
        $urgLabel = strtoupper($d['urgency']);
        foreach($donors as $dn) {
            $ins->execute([$dn['id'],$uid,"[{$urgLabel}] {$d['blood_group']} Blood Needed","Patient: {$d['patient_name']} at {$d['hospital_name']}, {$d['city']} needs {$d['units_needed']} unit(s) of {$d['blood_group']} blood. Please respond if available.",'donation_request']);
        }
        $success = "Your blood request has been posted and " . count($donors) . " eligible donor(s) notified.";
    }
}

// Load my existing requests
$requests = $db->prepare("SELECT * FROM blood_requests WHERE requester_id=? ORDER BY created_at DESC");
$requests->execute([$uid]); $requests=$requests->fetchAll();

renderHead('My Blood Requests'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <a href="/seeker/dashboard.php" class="back-link">← Back to Dashboard</a>
      <h1 class="page-title">Blood <span>Requests</span></h1>
      <p class="page-subtitle">Post a new request or manage your existing ones.</p>
    </div>
  </div>

  <?php if($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid-2" style="gap:1.75rem;align-items:start;">
    <!-- New Request Form -->
    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">Post New Request</h2>
      <div class="card">
        <form method="POST">
          <div style="display:flex;flex-direction:column;gap:.85rem;">
            <div class="form-group">
              <label>Blood Group Required *</label>
              <select name="blood_group">
                <option value="">Select blood group</option>
                <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                  <option value="<?= $bg ?>"><?= $bg ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Units Needed</label>
              <input type="number" name="units_needed" min="1" max="50" value="1">
            </div>
            <div class="form-group">
              <label>City *</label>
              <input type="text" name="city" value="<?= htmlspecialchars($me['city']) ?>" placeholder="City where blood is needed">
            </div>
            <div class="form-group">
              <label>Hospital Name</label>
              <input type="text" name="hospital_name" placeholder="e.g. Services Hospital Lahore">
            </div>
            <div class="form-group">
              <label>Patient Name</label>
              <input type="text" name="patient_name" placeholder="Patient's name">
            </div>
            <div class="form-group">
              <label>Urgency Level</label>
              <select name="urgency">
                <option value="normal">Normal</option>
                <option value="urgent">Urgent</option>
                <option value="critical">Critical Emergency</option>
              </select>
            </div>
            <div class="form-group">
              <label>Additional Notes</label>
              <textarea name="notes" placeholder="Any additional details about the need..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Request & Notify Donors</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Existing Requests -->
    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">My Request History</h2>
      <?php if(empty($requests)): ?>
        <div class="empty-state"><div class="icon">📋</div><p>No blood requests yet.</p></div>
      <?php else: foreach($requests as $i=>$r): ?>
      <div class="card card-sm" style="margin-bottom:.85rem;border-left:3px solid <?= $r['urgency']==='critical'?'#dc2626':($r['urgency']==='urgent'?'#ea580c':'#16a34a') ?>;animation:fadeUp .35s <?= $i*.04 ?>s ease both;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">
          <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <?= bloodGroupBadge($r['blood_group'], true) ?>
            <span class="urgency-badge urgency-<?= $r['urgency'] ?>"><?= ucfirst($r['urgency']) ?></span>
          </div>
          <span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
        </div>
        <?php if($r['patient_name']): ?>
          <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($r['patient_name']) ?></div>
        <?php endif; ?>
        <?php if($r['hospital_name']): ?>
          <div style="font-size:.78rem;color:var(--text-muted);">🏥 <?= htmlspecialchars($r['hospital_name']) ?>, <?= htmlspecialchars($r['city']) ?></div>
        <?php endif; ?>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem;">
          <?= $r['units_needed'] ?> unit(s) &nbsp;·&nbsp; <?= date('d M Y H:i',strtotime($r['created_at'])) ?>
        </div>
        <?php if($r['status']==='open'): ?>
        <form method="POST" action="/boc/api/update_request.php" style="display:inline;margin-top:.5rem;">
          <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
          <input type="hidden" name="status" value="cancelled">
          <button type="submit" class="btn btn-danger btn-sm">Cancel Request</button>
        </form>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
</body></html>
