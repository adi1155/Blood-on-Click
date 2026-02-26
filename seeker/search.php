<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireLogin();

$db = getDB();
$searched = false;
$donors = $banks = [];

$bg   = $_GET['blood_group'] ?? '';
$city = trim($_GET['city'] ?? '');
$type = $_GET['type'] ?? 'donors'; // donors | banks

if ($bg || $city) {
    $searched = true;
    if ($type === 'donors') {
        $sql = "SELECT u.id,u.full_name,u.blood_group,u.city,u.contact,u.last_donation_date,u.is_available FROM users u WHERE u.role='donor'";
        $params = [];
        if ($bg)   { $sql.=" AND u.blood_group=?"; $params[]=$bg; }
        if ($city) { $sql.=" AND u.city LIKE ?"; $params[]="%$city%"; }
        $sql .= " ORDER BY u.is_available DESC, u.last_donation_date ASC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $donors = $stmt->fetchAll();
    } else {
        // Search blood banks with stock
        $sql = "SELECT bb.*,u.city AS bank_city,u.id AS staff_id FROM blood_banks bb JOIN users u ON bb.user_id=u.id WHERE 1=1";
        $params = [];
        if ($city) { $sql.=" AND u.city LIKE ?"; $params[]="%$city%"; }
        $sql.=" AND bb.is_active=1 ORDER BY bb.name";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $banks = $stmt->fetchAll();
        // For each bank get stock
        foreach ($banks as &$b) {
            $s = $db->prepare("SELECT blood_group,units_available FROM blood_stock WHERE blood_bank_id=?");
            $s->execute([$b['id']]);
            $b['stock'] = $s->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        if ($bg) $banks = array_filter($banks, fn($b)=>isset($b['stock'][$bg]) && $b['stock'][$bg]>0);
    }
}

renderHead('Find Donors & Banks'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <h1 class="page-title">Find <span>Donors & Banks</span></h1>
  <p class="page-subtitle">Search for matching blood donors or blood banks near you.</p>

  <!-- Search Form -->
  <form method="GET">
    <div class="search-bar">
      <div class="form-group" style="min-width:130px;">
        <label>Search Type</label>
        <select name="type" onchange="this.form.submit()">
          <option value="donors" <?= $type==='donors'?'selected':'' ?>>Find Donors</option>
          <option value="banks"  <?= $type==='banks'?'selected':'' ?>>Find Blood Banks</option>
        </select>
      </div>
      <div class="form-group">
        <label>Blood Group</label>
        <select name="blood_group">
          <option value="">Any blood group</option>
          <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $b): ?>
            <option value="<?= $b ?>" <?= $bg===$b?'selected':'' ?>><?= $b ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>City</label>
        <input type="text" name="city" placeholder="e.g. Lahore" value="<?= htmlspecialchars($city) ?>">
      </div>
      <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Search</button>
      <?php if($searched): ?><a href="/seeker/search.php" class="btn btn-secondary" style="align-self:flex-end;">Clear</a><?php endif; ?>
    </div>
  </form>

  <!-- Blood Group Legend -->
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:center;">
    <span style="font-size:.72rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Blood Groups:</span>
    <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $b):
      $c = bloodGroupColor($b); ?>
    <span onclick="document.querySelector('[name=blood_group]').value='<?= $b ?>';document.querySelector('form').submit();"
          style="font-family:var(--font-mono);font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:6px;background:<?= $c ?>22;border:1.5px solid <?= $c ?>66;color:<?= $c ?>;cursor:pointer;transition:all .2s;"
          onmouseover="this.style.background='<?= $c ?>44'" onmouseout="this.style.background='<?= $c ?>22'">
      <?= $b ?>
    </span>
    <?php endforeach; ?>
    <span style="font-size:.72rem;color:var(--text-muted);margin-left:.5rem;">← Click any group to filter</span>
  </div>

  <!-- RESULTS: DONORS -->
  <?php if($searched && $type==='donors'): ?>
    <div class="section-header">
      <h2 class="section-title"><?= count($donors) ?> Donor<?= count($donors)!==1?'s':'' ?> Found <?= $bg?bloodGroupBadge($bg):'' ?> <?= $city?"in <em>".htmlspecialchars($city)."</em>":'' ?></h2>
    </div>
    <?php if(empty($donors)): ?>
      <div class="empty-state"><div class="icon">🔍</div><p>No donors found. Try a broader search.</p></div>
    <?php else: ?>
    <div class="grid-auto">
      <?php foreach($donors as $i=>$d):
        $color = bloodGroupColor($d['blood_group']);
        $canDonate = !$d['last_donation_date'] || date('Y-m-d',strtotime($d['last_donation_date'].' +90 days')) <= date('Y-m-d');
        $ready = $d['is_available'] && $canDonate;
      ?>
      <div style="background:var(--bg-card);border:2px solid <?= $color ?>33;border-radius:var(--radius-lg);overflow:hidden;transition:all .25s;animation:fadeUp .35s <?= $i*.05 ?>s ease both;"
           onmouseover="this.style.borderColor='<?= $color ?>'" onmouseout="this.style.borderColor='<?= $color ?>33'">
        <!-- Color header stripe -->
        <div style="height:4px;background:linear-gradient(90deg,<?= $color ?>,<?= $color ?>44);"></div>
        <div style="padding:1.25rem;">
          <div style="display:flex;justify-content:space-between;margin-bottom:.75rem;">
            <div style="width:46px;height:46px;border-radius:50%;background:<?= $color ?>18;border:2px solid <?= $color ?>55;display:flex;align-items:center;justify-content:center;font-family:var(--font-mono);font-size:.85rem;font-weight:700;color:<?= $color ?>;">
              <?= $d['blood_group'] ?>
            </div>
            <span style="font-size:.65rem;padding:.18rem .55rem;border-radius:4px;font-family:var(--font-mono);background:<?= $ready?'rgba(16,185,129,.15)':'rgba(107,114,128,.12)' ?>;color:<?= $ready?'#10b981':'#9ca3af' ?>;align-self:flex-start;">
              <?= $ready?'✓ Available':'Unavailable' ?>
            </span>
          </div>
          <div style="font-weight:600;font-size:.9rem;margin-bottom:.2rem;"><?= htmlspecialchars($d['full_name']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted);">📍 <?= htmlspecialchars($d['city']) ?></div>
          <div style="font-size:.72rem;color:var(--text-muted);margin-top:.2rem;">
            Last donated: <?= $d['last_donation_date'] ? date('d M Y',strtotime($d['last_donation_date'])) : 'Never' ?>
          </div>
          <?php if($ready): ?>
          <a href="tel:<?= $d['contact'] ?>" class="btn btn-success btn-sm" style="margin-top:.85rem;width:100%;background:<?= $color ?>18;color:<?= $color ?>;border-color:<?= $color ?>44;">
            📞 <?= htmlspecialchars($d['contact']) ?>
          </a>
          <?php else: ?>
          <div class="btn btn-sm" style="margin-top:.85rem;width:100%;background:rgba(107,114,128,.1);color:var(--text-muted);border:1px solid var(--border);cursor:default;">
            Not available
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  <!-- RESULTS: BANKS -->
  <?php elseif($searched && $type==='banks'): ?>
    <div class="section-header">
      <h2 class="section-title"><?= count($banks) ?> Blood Bank<?= count($banks)!==1?'s':'' ?> Found <?= $bg?"with ".bloodGroupBadge($bg):'' ?> <?= $city?"in <em>".htmlspecialchars($city)."</em>":'' ?></h2>
    </div>
    <?php if(empty($banks)): ?>
      <div class="empty-state"><div class="icon">🏥</div><p>No blood banks found with the selected criteria.</p></div>
    <?php else: foreach($banks as $i=>$b): ?>
    <div class="card card-sm" style="margin-bottom:1rem;animation:fadeUp .35s <?= $i*.04 ?>s ease both;">
      <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:.85rem;">
        <div>
          <div class="bank-name"><?= htmlspecialchars($b['name']) ?></div>
          <div class="bank-addr">📍 <?= htmlspecialchars($b['address']) ?></div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:flex-start;">
          <a href="tel:<?= $b['contact'] ?>" class="btn btn-success btn-sm">📞 Call</a>
          <a href="mailto:<?= $b['email'] ?>" class="btn btn-secondary btn-sm">✉ Email</a>
        </div>
      </div>
      <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:.5rem;">Available Stock</div>
      <div class="bank-stock-grid">
        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $grp):
          $units = $b['stock'][$grp] ?? 0;
          $color = bloodGroupColor($grp);
        ?>
        <div style="text-align:center;padding:.5rem .6rem;border-radius:8px;background:<?= $units>0?$color.'18':'rgba(107,114,128,.08)' ?>;border:1px solid <?= $units>0?$color.'44':'var(--border)' ?>;">
          <div style="font-family:var(--font-mono);font-size:.72rem;font-weight:700;color:<?= $units>0?$color:'var(--text-muted)' ?>"><?= $grp ?></div>
          <div style="font-size:.7rem;color:<?= $units>0?'var(--text)':'var(--text-muted)' ?>;margin-top:.1rem;"><?= $units ?>u</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; endif; ?>

  <?php elseif(!$searched): ?>
    <div class="empty-state">
      <div class="icon">🩸</div>
      <p>Select a blood group and/or city above to search for donors or blood banks.</p>
    </div>
  <?php endif; ?>
</div>
</body></html>
