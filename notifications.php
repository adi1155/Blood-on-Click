<?php
session_start();
require_once 'config/db.php';
require_once 'config/layout.php';
require_once 'config/auth.php';
requireLogin();

$db  = getDB();
$uid = $_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark_all'])) {
    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? OR user_id IS NULL")->execute([$uid]);
    header('Location: /notifications.php'); exit;
}

// Mark single as read
if (isset($_GET['read'])) {
    $db->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([(int)$_GET['read']]);
}

$notifs = $db->prepare("SELECT * FROM notifications WHERE (user_id=? OR user_id IS NULL) ORDER BY created_at DESC");
$notifs->execute([$uid]); $notifs=$notifs->fetchAll();
$unread = count(array_filter($notifs, fn($n)=>!$n['is_read']));

renderHead('Notifications'); ?>
<?php renderNav(); ?>
<div class="page animate-in" style="max-width:780px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="page-title">🔔 <span>Notifications</span></h1>
      <p class="page-subtitle"><?= $unread ?> unread notification<?= $unread!==1?'s':'' ?></p>
    </div>
    <?php if($unread): ?><a href="?mark_all=1" class="btn btn-secondary">Mark All Read</a><?php endif; ?>
  </div>

  <div class="card card-sm" style="padding:0;">
    <div class="notif-list">
      <?php if(empty($notifs)): ?>
        <div class="empty-state"><div class="icon">🔔</div><p>No notifications yet.</p></div>
      <?php else:
        $icons=['urgent'=>'🚨','stock_alert'=>'📉','assessment'=>'📋','info'=>'ℹ️','system'=>'⚙️','donation_request'=>'🩸'];
        foreach($notifs as $n):
      ?>
      <a href="?read=<?= $n['id'] ?>" style="text-decoration:none;color:inherit;" class="notif-item <?= !$n['is_read']?'unread':'' ?>">
        <div class="notif-icon type-<?= $n['type'] ?>"><?= $icons[$n['type']]??'🔔' ?></div>
        <div style="flex:1;min-width:0;">
          <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
          <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
          <div class="notif-time"><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></div>
        </div>
        <?php if(!$n['is_read']): ?><div class="unread-dot"></div><?php endif; ?>
      </a>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
</body></html>
