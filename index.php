<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (isLoggedIn()) {
  header("Location:" . (isAdmin() ? 'admin/index.php' : 'dashboard.php'));
  exit();
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  if (!$email || !$pass) {
    $err = "Please fill in all fields.";
  } else {
    $s = $conn->prepare("SELECT * FROM users WHERE email=?");
    $s->bind_param("s", $email);
    $s->execute();
    $u = $s->get_result()->fetch_assoc();
    if ($u && password_verify($pass, $u['password'])) {
      if (!$u['is_verified']) {
        $err = "Please verify your email first. <a href='verify.php?email=" . urlencode($email) . "'>Verify now</a>";
      } else {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['name'] = $u['name'];
        $_SESSION['email'] = $u['email'];
        $_SESSION['role'] = $u['role'];
        header("Location:" . ($u['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php'));
        exit();
      }
    } else {
      $err = "Invalid email or password.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — Barangay AMS</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <!-- <div class="gov-bar"><span>🇵🇭</span><span>Republic of the Philippines &mdash; Official Barangay Services
      Portal</span></div> -->
  <div class="auth-page">
    <div class="auth-logo">
      <div class="lc">🏛️</div>
      <h1>Barangay AMS</h1>
      <p>Appointment Management System</p>
    </div>
    <div class="auth-card">
      <h2>Sign In to Your Account</h2>
      <p class="sub">Enter your credentials to access the portal.</p>
      <?php if ($err): ?>
        <div class="flash flash-error"><span>❌</span><span><?= $err ?></span></div>
      <?php endif; ?>
      <?php showFlash(); ?>
      <form method="POST">
        <div class="fg"><label class="flabel">Email Address <span class="req">*</span></label>
          <input type="email" name="email" class="fc" placeholder="yourname@email.com" required
            value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="fg"><label class="flabel">Password <span class="req">*</span></label>
          <input type="password" name="password" class="fc" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">🔐 Sign In</button>
      </form>
      <p style="text-align:center;margin-top:14px;font-size:13px;color:#6c757d;">
        No account yet? <a href="register.php" style="color:#003087;font-weight:600;">Register here</a>
      </p>
    </div>

    <div class="auth-footer">
      &copy; <?= date('Y') ?> Barangay AMS &mdash; Republic of the Philippines
    </div>
  </div>
</body>

</html>