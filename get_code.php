<!-- <?php
// get_code.php — DEV HELPER ONLY (delete in production!)
// Shows the verification code when email is not configured.
session_start();
require_once 'includes/db.php';
$email = trim($_GET['email'] ?? '');
if (!$email) {
  die("No email provided.");
}
$s = $conn->prepare("SELECT name,verify_code,verify_expires FROM users WHERE email=? AND is_verified=0");
$s->bind_param("s", $email);
$s->execute();
$u = $s->get_result()->fetch_assoc();
if (!$u) {
  die("No pending verification for that email (already verified or not found).");
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>Dev: View Code</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <div class="auth-page">
    <div class="auth-card" style="text-align:center;max-width:380px;">
      <div style="font-size:36px;margin-bottom:10px;">🛠️</div>
      <h2 style="color:#003087;">DEV: Verification Code</h2>
      <p style="font-size:12px;color:#6c757d;margin-bottom:20px;">
        This page is for testing only.<br>Delete <code>get_code.php</code> in production!
      </p>
      <p style="font-size:13px;">Account: <strong><?= htmlspecialchars($u['name']) ?></strong></p>
      <p style="font-size:13px;margin-top:6px;">Email: <strong><?= htmlspecialchars($email) ?></strong></p>
      <div style="margin:20px 0;background:#f0f4ff;border:2px solid #003087;border-radius:8px;padding:20px;">
        <div style="font-size:36px;font-weight:800;letter-spacing:10px;color:#003087;">
          <?= htmlspecialchars($u['verify_code'] ?? 'NONE') ?>
        </div>
      </div>
      <p style="font-size:12px;color:#6c757d;">Expires: <?= htmlspecialchars($u['verify_expires'] ?? 'N/A') ?></p>
      <a href="verify.php?email=<?= urlencode($email) ?>" class="btn btn-primary" style="margin-top:16px;">→ Go to
        Verify
        Page</a>
    </div>
  </div>
</body>

</html> -->