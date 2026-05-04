<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
$uid = $_SESSION['user_id'];
$err = '';

$s = $conn->prepare("SELECT * FROM users WHERE id=?");
$s->bind_param("i", $uid); $s->execute();
$user = $s->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $np      = $_POST['new_password'] ?? '';
    $cp      = $_POST['confirm_password'] ?? '';

    if (!$name || !$phone || !$address) {
        $err = "Please fill in all required fields.";
    } elseif ($np && strlen($np) < 6) {
        $err = "New password must be at least 6 characters.";
    } elseif ($np && $np !== $cp) {
        $err = "Passwords do not match.";
    } else {
        if ($np) {
            $hash = password_hash($np, PASSWORD_DEFAULT);
            $u = $conn->prepare("UPDATE users SET name=?,phone=?,address=?,password=? WHERE id=?");
            $u->bind_param("ssssi", $name, $phone, $address, $hash, $uid);
        } else {
            $u = $conn->prepare("UPDATE users SET name=?,phone=?,address=? WHERE id=?");
            $u->bind_param("sssi", $name, $phone, $address, $uid);
        }
        $u->execute();
        $_SESSION['name'] = $name;
        setFlash('success', '✅ Profile updated successfully.');
        header("Location:profile.php"); exit();
    }
}
$back = isAdmin() ? "admin/index.php" : "dashboard.php";
$page_title = "My Profile — Barangay AMS";
?>
<?php require_once 'includes/header.php'; ?>
<?php showFlash(); ?>

<div class="page-hdr">
  <div><div class="page-title">👤 My Profile</div><div class="page-sub">Update your personal information.</div></div>
</div>

<?php if ($err): ?>
  <div class="flash flash-error"><span>❌</span><span><?= e($err) ?></span></div>
<?php endif; ?>

<div style="max-width:520px;">
  <div class="card">
    <div class="card-hdr">📋 Personal Information</div>
    <div class="card-body">
      <form method="POST">
        <div class="fg"><label class="flabel">Full Name <span class="req">*</span></label>
          <input type="text" name="name" class="fc" required value="<?= e($user['name']) ?>"></div>
        <div class="fg"><label class="flabel">Email Address</label>
          <input type="email" class="fc" value="<?= e($user['email']) ?>" disabled>
          <div class="fhint">Email address cannot be changed.</div></div>
        <div class="fg"><label class="flabel">Phone Number <span class="req">*</span></label>
          <input type="text" name="phone" class="fc" required value="<?= e($user['phone']) ?>"></div>
        <div class="fg"><label class="flabel">Home Address <span class="req">*</span></label>
          <input type="text" name="address" class="fc" required value="<?= e($user['address']) ?>"></div>

        <hr style="border:none;border-top:1px solid #e9ecef;margin:20px 0;">
        <div style="font-size:13px;color:#6c757d;margin-bottom:14px;font-weight:600;">🔒 Change Password <span style="font-weight:400;">(leave blank to keep current)</span></div>
        <div class="frow">
          <div class="fg"><label class="flabel">New Password</label>
            <input type="password" name="new_password" class="fc" placeholder="Min. 6 characters"></div>
          <div class="fg"><label class="flabel">Confirm New Password</label>
            <input type="password" name="confirm_password" class="fc" placeholder="Re-enter"></div>
        </div>
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn btn-primary" style="flex:1;">💾 Save Changes</button>
          <a href="<?= $back ?>" class="btn btn-secondary" style="flex:1;text-align:center;">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Info card -->
  <div class="card">
    <div class="card-body" style="display:flex;gap:14px;align-items:center;">
      <div style="width:52px;height:52px;background:#003087;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;color:#FFD700;flex-shrink:0;">👤</div>
      <div>
        <div style="font-weight:700;color:#003087;"><?= e($user['name']) ?></div>
        <div style="font-size:12px;color:#6c757d;"><?= e($user['email']) ?></div>
        <div style="margin-top:4px;">
          <span class="badge <?= $user['role'] === 'admin' ? 'badge-rescheduled' : 'badge-approved' ?>"><?= ucfirst($user['role']) ?></span>
          <span class="badge <?= $user['is_verified'] ? 'badge-approved' : 'badge-rejected' ?>" style="margin-left:4px;"><?= $user['is_verified'] ? '✅ Verified' : '❌ Unverified' ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
