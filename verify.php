<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/mailer.php';

if (isLoggedIn()) {
  header("Location: dashboard.php");
  exit();
}

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$sent = ($_GET['sent'] ?? '') === '1';

$err = '';
$ok = '';


// ==========================
// RESEND CODE (FIXED)
// ==========================
if (isset($_GET['resend']) && $email) {

  $s = $conn->prepare("SELECT * FROM users WHERE email=? AND is_verified=0");
  $s->bind_param("s", $email);
  $s->execute();
  $u = $s->get_result()->fetch_assoc();

  if ($u) {

    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $exp = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $upd = $conn->prepare("UPDATE users SET verify_code=?, verify_expires=? WHERE email=?");
    $upd->bind_param("sss", $code, $exp, $email);
    $upd->execute();

    sendVerificationEmail($email, $u['name'], $code);

    $ok = "Verification code resent to <strong>" . e($email) . "</strong>";
    $sent = true;
  }
}


// ==========================
// VERIFY CODE SUBMISSION
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {

  $code = trim(implode('', array_map(fn($k) => $_POST["d$k"] ?? '', [1, 2, 3, 4, 5, 6])));

  if (strlen($code) < 6) {
    $err = "Please enter the complete 6-digit code.";
  } else {

    $s = $conn->prepare("SELECT * FROM users WHERE email=? AND verify_code=? AND is_verified=0");
    $s->bind_param("ss", $email, $code);
    $s->execute();
    $u = $s->get_result()->fetch_assoc();

    if (!$u) {
      $err = "Invalid verification code.";
    } elseif (strtotime($u['verify_expires']) < time()) {
      $err = "Code has expired. <a href='verify.php?email=" . urlencode($email) . "&resend=1'>Resend code</a>";
    } else {

      // MARK VERIFIED (FIXED)
      $upd = $conn->prepare("UPDATE users SET is_verified=1, verify_code=NULL, verify_expires=NULL WHERE id=?");
      $upd->bind_param("i", $u['id']);
      $upd->execute();

      // AUTO LOGIN
      $_SESSION['user_id'] = $u['id'];
      $_SESSION['name'] = $u['name'];
      $_SESSION['email'] = $u['email'];
      $_SESSION['role'] = $u['role'];

      notify($conn, $u['id'], 'Welcome to Barangay AMS!', 'Your account has been verified.');

      setFlash('success', '✅ Email verified! Welcome, ' . $u['name'] . '!');
      header("Location: dashboard.php");
      exit();
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verify Email — Barangay AMS</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

  <div class="gov-bar">
    <span>🇵🇭</span>
    <span>Republic of the Philippines — Official Barangay Services Portal</span>
  </div>

  <div class="auth-page">

    <div class="auth-logo">
      <div class="lc">📧</div>
      <h1>Verify Your Email</h1>
      <p>Barangay Appointment Management System</p>
    </div>

    <div class="auth-card" style="max-width:420px;text-align:center;">

      <div style="font-size:48px;margin-bottom:12px;">📨</div>

      <h2>Check Your Email</h2>

      <p class="sub" style="text-align:center;">
        <?php if ($email): ?>
          We sent a 6-digit code to<br>
          <strong style="color:#003087;"><?= e($email) ?></strong>
        <?php else: ?>
          Enter your email's 6-digit code below.
        <?php endif; ?>
      </p>

      <!-- ERROR -->
      <?php if ($err): ?>
        <div class="flash flash-error" style="text-align:left;">
          <span>❌</span><span><?= $err ?></span>
        </div>
      <?php endif; ?>

      <!-- SUCCESS -->
      <?php if ($ok): ?>
        <div class="flash flash-success" style="text-align:left;">
          <span>✅</span><span><?= $ok ?></span>
        </div>
      <?php endif; ?>


      <!-- OTP FORM -->
      <form method="POST">

        <input type="hidden" name="email" value="<?= e($email) ?>">
        <input type="hidden" name="code" id="codeHidden">

        <div class="otp-wrap">
          <?php for ($i = 1; $i <= 6; $i++): ?>
            <input type="text" name="d<?= $i ?>" class="otp-input" maxlength="1" id="d<?= $i ?>" inputmode="numeric"
              autocomplete="off">
          <?php endfor; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block" onclick="buildCode()">
          ✅ Verify Email
        </button>

      </form>

      <p style="margin-top:16px;font-size:12px;color:#6c757d;">
        Didn't receive it?
        <a href="verify.php?email=<?= urlencode($email) ?>&resend=1" style="color:#003087;font-weight:600;">
          Resend Code
        </a>
      </p>

      <p style="margin-top:8px;font-size:12px;color:#6c757d;">
        <a href="index.php">← Back to Login</a>
      </p>

    </div>

    <div class="auth-footer">&copy; <?= date('Y') ?> Barangay AMS</div>

  </div>


  <script>
    // auto move
    document.querySelectorAll('.otp-input').forEach((el, i, arr) => {
      el.addEventListener('input', e => {
        if (e.target.value.length === 1 && arr[i + 1]) arr[i + 1].focus();
      });
      el.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !e.target.value && arr[i - 1]) arr[i - 1].focus();
      });
    });

    // paste
    document.querySelector('.otp-input').addEventListener('paste', e => {
      e.preventDefault();
      const p = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      document.querySelectorAll('.otp-input').forEach((el, i) => el.value = p[i] || '');
    });

    // build code
    function buildCode() {
      let c = '';
      for (let i = 1; i <= 6; i++) {
        c += document.getElementById('d' + i).value;
      }
      document.getElementById('codeHidden').value = c;
    }
  </script>

</body>

</html>