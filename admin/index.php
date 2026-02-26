<?php
session_start();
require_once '../config/db.php';
require_once '../config/layout.php';
require_once '../config/auth.php';
requireAdmin();

$db = getDB();

// Handle delete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    if ($id !== $_SESSION['user_id']) $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    $_SESSION['flash_success'] = 'User deleted.';
    header('Location: /boc/admin/index.php'); exit;
}

$role = $_GET['role'] ?? '';
$q    = trim($_GET['q'] ?? '');

$sql    = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($role) { $sql.=" AND role=?"; $params[]=$role; }
if ($q)    { $sql.=" AND (full_name LIKE ? OR email LIKE ? OR city LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; $params[]="%$q%"; }
$sql.=" ORDER BY role,created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
$users = $stmt->fetchAll();

// Stats
$counts = $db->query("SELECT role,COUNT(*) as c FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalReqs = $db->query("SELECT COUNT(*) FROM blood_requests WHERE status='open'")->fetchColumn();
$totalDons = $db->query("SELECT COUNT(*) FROM donations")->fetchColumn();

$flash = $_SESSION['flash_success']??''; unset($_SESSION['flash_success']);
renderHead('Admin — Users'); ?>
<?php renderNav(); ?>
<div class="page animate-in">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <h1 class="page-title">System <span>Overview</span></h1>
      <p class="page-subtitle">Admin control panel — manage all users and system activity.</p>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
      <a href="/admin/add_user.php" class="btn btn-primary">+ Add User</a>
      <a href="/admin/notifications.php" class="btn btn-warning">🔔 Broadcast</a>
      <a href="/admin/assessments.php" class="btn btn-secondary">📋 Assessments</a>
    </div>
  </div>

  <?php if($flash): ?><div class="alert alert-success">✓ <?= htmlspecialchars($flash) ?></div><?php endif; ?>

  <!-- Stats -->
  <div class="grid-4 stagger" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-value"><?= $counts['donor']??0 ?></div><div class="stat-label">Donors</div></div>
    <div class="stat-card"><div class="stat-value"><?= $counts['blood_bank']??0 ?></div><div class="stat-label">Blood Banks</div></div>
    <div class="stat-card"><div class="stat-value"><?= $counts['seeker']??0 ?></div><div class="stat-label">Seekers</div></div>
    <div class="stat-card"><div class="stat-value"><?= $totalReqs ?></div><div class="stat-label">Open Requests</div></div>
  </div>

  <!-- Filter Bar -->
  <form method="GET" style="margin-bottom:1.25rem;">
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
      <div class="form-group" style="flex:1;min-width:200px;">
        <input type="text" name="q" placeholder="Search name, email, city…" value="<?= htmlspecialchars($q) ?>">
      </div>
      <div class="form-group">
        <select name="role">
          <option value="">All Roles</option>
          <option value="admin"      <?= $role==='admin'?'selected':'' ?>>Admin</option>
          <option value="donor"      <?= $role==='donor'?'selected':'' ?>>Donor</option>
          <option value="blood_bank" <?= $role==='blood_bank'?'selected':'' ?>>Blood Bank</option>
          <option value="seeker"     <?= $role==='seeker'?'selected':'' ?>>Seeker</option>
        </select>
      </div>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <?php if($q||$role): ?><a href="/boc/admin/index.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </div>
  </form>

  <!-- Users Table -->
  <div class="table-wrapper">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Blood Group</th><th>City</th><th>Email</th><th>Contact</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if(empty($users)): ?>
          <tr><td colspan="9" class="text-center text-muted" style="padding:2.5rem">No users found.</td></tr>
        <?php else: foreach($users as $i=>$u): ?>
        <tr style="animation:fadeUp .3s <?= $i*.025 ?>s ease both;">
          <td class="mono text-muted" style="font-size:.72rem;"><?= $i+1 ?></td>
          <td style="font-weight:600;"><?= htmlspecialchars($u['full_name']) ?></td>
          <td>
            <span style="font-size:.68rem;font-weight:600;padding:.15rem .5rem;border-radius:4px;text-transform:uppercase;font-family:var(--font-mono);
              background:<?= match($u['role']){'admin'=>'rgba(245,158,11,.15)','blood_bank'=>'rgba(59,130,246,.12)','donor'=>'rgba(16,185,129,.1)','seeker'=>'rgba(167,139,250,.1)',default=>'rgba(107,114,128,.1)'} ?>;
              color:<?= match($u['role']){'admin'=>'#fbbf24','blood_bank'=>'#60a5fa','donor'=>'#34d399','seeker'=>'#a78bfa',default=>'#9ca3af'} ?>;">
              <?= ucfirst(str_replace('_',' ',$u['role'])) ?>
            </span>
          </td>
          <td><?= $u['blood_group'] ? bloodGroupBadge($u['blood_group']) : '<span class="text-muted">—</span>' ?></td>
          <td><?= htmlspecialchars($u['city']??'—') ?></td>
          <td style="font-size:.8rem;"><?= htmlspecialchars($u['email']) ?></td>
          <td class="mono" style="font-size:.78rem;"><?= htmlspecialchars($u['contact']??'—') ?></td>
          <td class="mono" style="font-size:.75rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div class="td-actions">
              <a href="/boc/admin/edit_user.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
              <?php if($u['role']==='donor'): ?><a href="/boc/admin/assessments.php?donor=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Assess</a><?php endif; ?>
              <?php if($u['id'] !== (int)$_SESSION['user_id']): ?>
              <form method="POST" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
                <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <p style="margin-top:.75rem;font-size:.75rem;color:var(--text-muted);">Showing <?= count($users) ?> user<?= count($users)!==1?'s':'' ?></p>
</div>
</body></html>
