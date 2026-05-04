<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
$uid = $_SESSION['user_id'];

// Mark all read
if (isset($_GET['mark_all'])) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    setFlash('success', 'All notifications marked as read.');
    header("Location:notifications.php"); exit();
}

// Mark single read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $conn->query("UPDATE notifications SET is_read=1 WHERE id=$nid AND user_id=$uid");
}

// Delete single
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $nid = (int)$_GET['del'];
    $conn->query("DELETE FROM notifications WHERE id=$nid AND user_id=$uid");
    header("Location:notifications.php"); exit();
}

// Fetch all
$res = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC");
$notifs = $res->fetch_all(MYSQLI_ASSOC);
$unread_count = count(array_filter($notifs, fn($n) => !$n['is_read']));

$page_title = "Notifications — Barangay AMS";
?>
<?php require_once 'includes/header.php'; ?>
<?php showFlash(); ?>

<div class="page-hdr">
  <div>
    <div class="page-title">🔔 Notifications</div>
    <div class="page-sub"><?= $unread_count ?> unread notification<?= $unread_count != 1 ? 's' : '' ?></div>
  </div>
  <?php if ($unread_count > 0): ?>
    <a href="notifications.php?mark_all=1" class="btn btn-secondary btn-sm">✔ Mark All Read</a>
  <?php endif; ?>
</div>

<div class="card">
  <?php if (empty($notifs)): ?>
    <div style="text-align:center;padding:50px 20px;color:#6c757d;">
      <div style="font-size:48px;margin-bottom:12px;">🔔</div>
      <div style="font-weight:600;font-size:16px;color:#343a40;margin-bottom:6px;">No Notifications</div>
      <p style="font-size:13px;">You have no notifications yet.</p>
    </div>
  <?php else: ?>
    <?php foreach ($notifs as $n): ?>
      <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
        <div style="display:flex;gap:12px;align-items:flex-start;flex:1;">
          <?php if (!$n['is_read']): ?>
            <div class="ndot" style="margin-top:8px;flex-shrink:0;"></div>
          <?php else: ?>
            <div style="width:8px;flex-shrink:0;"></div>
          <?php endif; ?>
          <div style="flex:1;">
            <div class="ntitle"><?= e($n['title']) ?></div>
            <div class="nmsg"><?= e($n['message']) ?></div>
            <div class="ntime">🕐 <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></div>
          </div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0;margin-top:4px;">
          <?php if (!$n['is_read']): ?>
            <a href="notifications.php?read=<?= $n['id'] ?>" class="btn btn-secondary btn-sm" title="Mark as read">✔</a>
          <?php endif; ?>
          <a href="notifications.php?del=<?= $n['id'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this notification?')" title="Delete">🗑</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
