<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/mailer.php';
if (isLoggedIn()) {
  header("Location:dashboard.php");
  exit();
}
$err = '';
$ok = '';
$old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old = $_POST;

  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $addr = trim($_POST['address'] ?? '');
  $pass = $_POST['password'] ?? '';
  $conf = $_POST['confirm'] ?? '';


  if (!$name || !$email || !$phone || !$addr || !$pass) {
    $err = "Please fill in all required fields.";
  } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
    $err = "Only Gmail accounts are allowed.";
  } elseif (!preg_match('/^09\d{9}$/', $phone)) {
    $err = "Contact number must start with 09 and be 11 digits.";
  } elseif (strlen($pass) < 6) {
    $err = "Password must be at least 6 characters.";
  } elseif ($pass !== $conf) {
    $err = "Passwords do not match.";
  } else {
    $s = $conn->prepare("SELECT id FROM users WHERE email=?");
    $s->bind_param("s", $email);
    $s->execute();
    $s->store_result();

    if ($s->num_rows > 0) {
      $err = "That email is already registered.";
    } else {
      $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
      $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      $ins = $conn->prepare("
                INSERT INTO users
                (name,email,password,phone,address,is_verified,verify_code,verify_expires)
                VALUES(?,?,?,?,?,0,?,?)
            ");

      $ins->bind_param(
        "sssssss",
        $name,
        $email,
        $hash,
        $phone,
        $addr,
        $code,
        $expires
      );

      if ($ins->execute()) {
        $sent = sendVerificationEmail($email, $name, $code);
        header("Location:verify.php?email=" . urlencode($email) . "&sent=" . ($sent ? '1' : '0'));
        exit();
      } else {
        $err = "Registration failed. Please try again.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register — Barangay AMS</title>
  <link rel="stylesheet" href="assets/css/style.css">

  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
  <div class="gov-bar"><span>🇵🇭</span><span>Republic of the Philippines &mdash; Official Barangay Services
      Portal</span></div>
  <div class="auth-page">
    <div class="auth-logo">
      <div class="lc">🏛️</div>
      <h1>Create an Account</h1>
      <p>Barangay Appointment Management System</p>
    </div>
    <div class="auth-card" style="max-width:480px;">
      <h2>Resident Registration</h2>
      <p class="sub">Fill in your information to register.</p>
      <?php if ($err): ?>
        <div class="flash flash-error"><span>❌</span><span><?= $err ?></span></div>
      <?php endif; ?>
      <form method="POST">
        <div class="fg"><label class="flabel">Full Name <span class="req">*</span></label>
          <input type="text" name="name" class="fc" placeholder="e.g. Juan dela Cruz" required
            value="<?= e($old['name'] ?? '') ?>">
        </div>
        <div class="fg"><label class="flabel">Email Address <span class="req">*</span></label>
          <input type="email" name="email" class="fc" placeholder="yourname@email.com" required
            value="<?= e($old['email'] ?? '') ?>">
          <div class="fhint">📧 A 6-digit verification code will be sent to this email.</div>
        </div>
        <div class="fg"><label class="flabel">Phone Number <span class="req">*</span></label>
          <input type="text" name="phone" class="fc" placeholder="09XXXXXXXXX" required
            value="<?= e($old['phone'] ?? '') ?>">
        </div>
        <div class="fg"><label class="flabel">Home Address <span class="req">*</span></label>
          <input type="text" name="address" class="fc" placeholder="House No., Street, Barangay" required
            value="<?= e($old['address'] ?? '') ?>">
        </div>
        <div class="frow">
          <div class="fg"><label class="flabel">Password <span class="req">*</span></label>
            <input type="password" name="password" class="fc" placeholder="Min. 6 characters" required>
          </div>
          <div class="fg"><label class="flabel">Confirm Password <span class="req">*</span></label>
            <input type="password" name="confirm" class="fc" placeholder="Re-enter" required>
          </div>
        </div>
        <!-- Terms -->
        <div class="fg" style="display:flex;gap:8px;align-items:flex-start;">
          <input type="checkbox" id="terms" required style="margin-top:3px;flex-shrink:0;">
          <label for="terms" style="font-size:12px;color:#6c757d;">I agree to the terms and conditions of the Barangay
            AMS. My information will be used solely for barangay service purposes.</label>
        </div>

        <!-- ✅ CAPTCHA BOX -->
        <div class="fg">
          <div class="g-recaptcha" data-sitekey="6LeHI80sAAAAAJxSEGDSzBlLKAZX-HZYgNvJliT_"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">📧 Register &amp; Send Verification</button>
      </form>
      <p style="text-align:center;margin-top:14px;font-size:13px;color:#6c757d;">
        Already have an account? <a href="index.php" style="color:#003087;font-weight:600;">Sign in</a>
      </p>
    </div>
    <div class="auth-footer">&copy; <?= date('Y') ?> Barangay AMS &mdash; Republic of the Philippines</div>
  </div>
</body>

</html>