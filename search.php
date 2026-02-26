<?php
session_start();
require_once 'config/db.php';
require_once 'config/layout.php';
require_once 'config/auth.php';
requireLogin();

$db   = getDB();
$type = $_GET['type'] ?? 'donors';
$bg   = $_GET['blood_group'] ?? '';
$city = trim($_GET['city'] ?? '');
$searched = $bg || $city;
$results  = [];

if ($searched) {
    if ($type === 'donors') {
        $sql = "SELECT u.id,u.full_name,u.blood_group,u.age,u.gender,u.city,u.contact,u.email,u.last_donation_date,u.is_available FROM users u WHERE u.role='donor'";
        $params = [];
        if ($bg)   { $sql.=" AND u.blood_group=?"; $params[]=$bg; }
        if ($city) { $sql.=" AND u.city LIKE ?"; $params[]="%$city%"; }
        $sql.=" ORDER BY u.is_available DESC, u.full_name";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $results = $stmt->fetchAll();
    } else {
        $sql = "SELECT bb.*,u.city AS bank_city FROM blood_banks bb JOIN users u ON bb.user_id=u.id WHERE bb.is_active=1";
        $params = [];
        if ($city) { $sql.=" AND u.city LIKE ?"; $params[]="%$city%"; }
        $sql.=" ORDER BY bb.name";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $banks = $stmt->fetchAll();
        foreach($banks as &$b) {
            $s = $db->prepare("SELECT blood_group,units_available FROM blood_stock WHERE blood_bank_id=? ".($bg?"AND blood_group=?":"")." ORDER BY blood_group");
            $params2 = [$b['id']]; if($bg) $params2[]=$bg;
            $s->execute($params2); $b['stock'] = $s->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        $results = $bg ? array_filter($banks, fn($b)=>!empty($b['stock'])) : $banks;
    }
}

renderHead('Search'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <h1 class="page-title">Find <span>Donors & Banks</span></h1>
  <p class="page-subtitle">Search the entire network for matching donors and blood banks.</p>

  <form method="GET">
    <div class="search-bar">
      <div class="form-group" style="min-width:140px;">
        <label>Type</label>
        <select name="type" onchange="this.form.submit()">
          <option value="donors" <?= $type==='donors'?'selected':'' ?>>Donors</option>
          <option value="banks"  <?= $type==='banks'?'selected':'' ?>>Blood Banks</option>
        </select>
      </div>
      <div class="form-group">
        <label>Blood Group</label>
        <select name="blood_group">
          <option value="">Any</option>
          <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg2): ?><option value="<?= $bg2 ?>" <?= $bg===$bg2?'selected':'' ?>><?= $bg2 ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>City</label>
        <input type="text" name="city" placeholder="e.g. Lahore" value="<?= htmlspecialchars($city) ?>">
      </div>
      <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Search</button>
      <?php if($searched): ?><a href="/search.php?type=<?= $type ?>" class="btn btn-secondary" style="align-self:flex-end;">Clear</a><?php endif; ?>
    </div>
  </form>

  <!-- Blood group quick-filter -->
  <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $b): $c=bloodGroupColor($b); ?>
    <a href="?type=<?= $type ?>&blood_group=<?= $b ?>&city=<?= urlencode($city) ?>"
       style="font-family:var(--font-mono);font-size:.72rem;font-weight:600;padding:.22rem .65rem;border-radius:6px;text-decoration:none;transition:all .2s;background:<?= $bg===$b?$c.'33':'#ffffff08' ?>;border:1.5px solid <?= $bg===$b?$c:$c.'44' ?>;color:<?= $bg===$b?$c:$c ?>;">
      <?= $b ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if(!$searched): ?>
    <div class="empty-state"><div class="icon">🔍</div><p>Select filters above to search.</p></div>
  <?php elseif(empty($results)): ?>
    <div class="empty-state"><div class="icon">😔</div><p>No <?= $type ?> found matching your criteria.<br>Try a broader search.</p></div>
  <?php elseif($type==='donors'): ?>
    <div class="section-header">
      <h2 class="section-title"><?= count($results) ?> Donor<?= count($results)!==1?'s':'' ?> <?= $bg?bloodGroupBadge($bg):'' ?> <?= $city?"in <em>".htmlspecialchars($city)."</em>":'' ?></h2>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>#</th><th>Name</th><th>Age/Gender</th><th>Blood Group</th><th>City</th><th>Contact</th><th>Last Donation</th><th>Available</th></tr></thead>
        <tbody>
          <?php foreach($results as $i=>$d): ?>
          <tr style="animation:fadeUp .3s <?= $i*.03 ?>s ease both;">
            <td class="mono text-muted" style="font-size:.72rem;"><?= $i+1 ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($d['full_name']) ?></td>
            <td><?= $d['age'] ?> · <?= $d['gender'] ?></td>
            <td><?= bloodGroupBadge($d['blood_group']) ?></td>
            <td><?= htmlspecialchars($d['city']) ?></td>
            <td class="mono" style="font-size:.8rem;"><?= htmlspecialchars($d['contact']??'—') ?></td>
            <td class="mono" style="font-size:.78rem;"><?= $d['last_donation_date']?date('d M Y',strtotime($d['last_donation_date'])):'—' ?></td>
            <td><span style="font-size:.72rem;color:<?= $d['is_available']?'#10b981':'#f87171' ?>"><?= $d['is_available']?'✓ Yes':'✗ No' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="section-header"><h2 class="section-title"><?= count($results) ?> Blood Bank<?= count($results)!==1?'s':'' ?> Found</h2></div>
    <div class="grid-2" style="gap:1.25rem;">
      <?php foreach($results as $b): ?>
      <div class="bank-card">
        <div class="bank-name"><?= htmlspecialchars($b['name']) ?></div>
        <div class="bank-addr">📍 <?= htmlspecialchars($b['address']) ?></div>
        <div style="font-size:.78rem;margin:.5rem 0;display:flex;gap:.75rem;">
          <span>📞 <?= $b['contact'] ?></span>
          <span>✉ <?= $b['email'] ?></span>
        </div>
        <?php if(!empty($b['stock'])): ?>
        <div class="bank-stock-grid" style="margin-top:.75rem;">
          <?php foreach($b['stock'] as $grp=>$units): $c=bloodGroupColor($grp); ?>
          <div style="text-align:center;padding:.4rem .5rem;border-radius:7px;background:<?= $units>0?$c.'18':'rgba(107,114,128,.08)' ?>;border:1px solid <?= $units>0?$c.'44':'var(--border)' ?>;">
            <div style="font-family:var(--font-mono);font-size:.68rem;font-weight:700;color:<?= $units>0?$c:'var(--text-muted)' ?>"><?= $grp ?></div>
            <div style="font-size:.66rem;color:var(--text-muted);"><?= $units ?>u</div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body></html>
