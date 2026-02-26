<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireRole(['blood_bank','admin']);

$db  = getDB();
$uid = $_SESSION['user_id'];
$bank = $db->prepare("SELECT * FROM blood_banks WHERE user_id=?"); $bank->execute([$uid]); $bank=$bank->fetch();
if (!$bank) { header('Location: /dashboard.php'); exit; }
$bid = $bank['id'];

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bg    = $_POST['blood_group'] ?? '';
    $units = max(0, (int)($_POST['units_available'] ?? 0));
    $thresh= max(1, (int)($_POST['threshold_alert'] ?? 5));
    $validGroups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
    if (!in_array($bg, $validGroups)) {
        $error = 'Invalid blood group.';
    } else {
        $db->prepare("INSERT INTO blood_stock (blood_bank_id,blood_group,units_available,threshold_alert) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE units_available=?,threshold_alert=?")
           ->execute([$bid,$bg,$units,$thresh,$units,$thresh]);
        $success = "Stock for {$bg} updated to {$units} units.";

        // Auto-notify if below threshold
        if ($units <= $thresh) {
            $db->prepare("INSERT INTO notifications (user_id,sender_id,title,message,type) VALUES (NULL,?,?,?,?)")
               ->execute([$uid, "⚠ Low Stock Alert: {$bg}", "{$bank['name']} has only {$units} units of {$bg} blood (below threshold of {$thresh}). Urgent donors needed.", 'stock_alert']);
        }
    }
}

$stock = $db->prepare("SELECT * FROM blood_stock WHERE blood_bank_id=? ORDER BY blood_group"); $stock->execute([$bid]); $stock=$stock->fetchAll();
$stockMap = []; foreach($stock as $s) $stockMap[$s['blood_group']] = $s;

renderHead('Manage Stock'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <a href="/boc/blood_bank/dashboard.php" class="back-link">← Back to Dashboard</a>
  <h1 class="page-title">Blood <span>Stock Management</span></h1>
  <p class="page-subtitle"><?= htmlspecialchars($bank['name']) ?> — Update and monitor blood unit levels.</p>

  <?php if($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid-2" style="gap:1.75rem;align-items:start;">
    <!-- Update Form -->
    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">Update Stock Units</h2>
      <div class="card">
        <form method="POST">
          <div style="display:flex;flex-direction:column;gap:.85rem;">
            <div class="form-group">
              <label>Blood Group *</label>
              <select name="blood_group">
                <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                  <option value="<?= $bg ?>"><?= $bg ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Units Available *</label>
              <input type="number" name="units_available" min="0" max="9999" placeholder="e.g. 25">
            </div>
            <div class="form-group">
              <label>Alert Threshold <small style="font-weight:400;text-transform:none">(notify when below this)</small></label>
              <input type="number" name="threshold_alert" min="1" max="100" value="5">
            </div>
            <button type="submit" class="btn btn-primary">Update Stock</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Current Stock Table -->
    <div>
      <h2 class="section-title" style="margin-bottom:1rem;">Current Inventory</h2>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Blood Group</th><th>Units Available</th><th>Reserved</th><th>Alert Level</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg):
              $s = $stockMap[$bg] ?? null;
              $units = $s ? (int)$s['units_available'] : 0;
              $thresh = $s ? (int)$s['threshold_alert'] : 5;
              $status = !$s ? 'Not Set' : ($units <= $thresh ? '⚠ Low' : ($units <= $thresh*2 ? 'Moderate' : '✓ OK'));
              $color  = bloodGroupColor($bg);
            ?>
            <tr>
              <td><span style="font-family:var(--font-mono);font-size:.82rem;font-weight:600;color:<?= $color ?>"><?= $bg ?></span></td>
              <td class="mono"><?= $units ?></td>
              <td class="mono"><?= $s ? $s['units_reserved'] : 0 ?></td>
              <td class="mono"><?= $thresh ?></td>
              <td>
                <span style="font-size:.75rem;font-weight:600;color:<?= $units<=$thresh?'#f87171':($units<=$thresh*2?'#fbbf24':'#10b981') ?>">
                  <?= $status ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Stock bars visual -->
      <div class="card" style="margin-top:1.25rem;padding:1rem;">
        <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:.85rem;">Visual Overview</div>
        <?php foreach($stock as $s):
          $pct = min(100, round($s['units_available']/50*100));
          $cls = $s['units_available'] <= $s['threshold_alert'] ? 'danger' : ($s['units_available'] <= $s['threshold_alert']*2 ? 'warn' : 'ok');
          $color = bloodGroupColor($s['blood_group']);
        ?>
        <div class="stock-bar-wrap" style="margin-bottom:.7rem;">
          <div class="stock-bar-header">
            <span style="font-family:var(--font-mono);font-size:.78rem;font-weight:600;color:<?= $color ?>"><?= $s['blood_group'] ?></span>
            <span style="font-size:.72rem;color:var(--text-muted);"><?= $s['units_available'] ?> units</span>
          </div>
          <div class="stock-bar-bg"><div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</body></html>
