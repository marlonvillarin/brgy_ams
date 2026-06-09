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

// ✅ YOUR V3 SECRET KEY
$recaptcha_secret = "6LcqqOYsAAAAAHZRlrtg1Eq3tkKmF8UkBeCKaASX";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old = $_POST;

  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $addr = trim($_POST['address'] ?? '');
  $pass = $_POST['password'] ?? '';
  $conf = $_POST['confirm'] ?? '';

  // Get reCAPTCHA token from form
  $recaptcha_token = $_POST['recaptcha_token'] ?? '';

  // Server-side v3 verification
  if (empty($recaptcha_token)) {
    $err = "Security verification failed. Please try again.";
  } else {
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_token}");
    $response_data = json_decode($verify);

    if (!$response_data->success) {
      $err = "reCAPTCHA verification failed. Please try again.";
    } elseif ($response_data->score < 0.3) {
      $err = "Security check failed. Please try again.";
    } elseif ($response_data->action !== 'register') {
      $err = "Invalid reCAPTCHA action.";
    }
  }

  // Only proceed if no reCAPTCHA error
  if (empty($err)) {
    if (!$name || !$email || !$phone || !$addr || !$pass) {
      $err = "Please fill in all required fields.";
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
      $err = "Only Gmail accounts are allowed.";
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
      $err = "Contact number must start with 09 and be exactly 11 digits.";
    } elseif (strlen($pass) < 6) {
      $err = "Password must be at least 6 characters.";
    } elseif (!preg_match('/[A-Za-z]/', $pass)) {
      $err = "Password must contain at least one letter.";
    } elseif (!preg_match('/[0-9]/', $pass)) {
      $err = "Password must contain at least one number.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $pass)) {
      $err = "Password must contain at least one symbol (!@#$%^&* etc.).";
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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register — Barangay AMS</title>
  <link rel="stylesheet" href="assets/css/style.css">

  <script src="https://www.google.com/recaptcha/api.js?render=6LcqqOYsAAAAAA_An4AwN1h8o1V7v2lxpwQkX9Yg"></script>
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

      <form method="POST" id="registerForm">
        <div class="fg"><label class="flabel">Full Name <span class="req">*</span></label>
          <input type="text" name="name" class="fc" placeholder="e.g. Juan dela Cruz" required
            value="<?= e($old['name'] ?? '') ?>">
        </div>
        <div class="fg"><label class="flabel">Email Address <span class="req">*</span></label>
          <input type="email" name="email" class="fc" placeholder="yourname@gmail.com" required
            value="<?= e($old['email'] ?? '') ?>">
          <div class="fhint">📧 A 6-digit verification code will be sent to this email.</div>
        </div>
        <div class="fg"><label class="flabel">Phone Number <span class="req">*</span></label>
          <input type="tel" name="phone" id="phone" class="fc" placeholder="09XXXXXXXXX" required
            value="<?= e($old['phone'] ?? '') ?>" maxlength="11" pattern="09[0-9]{9}">
          <div class="fhint" id="phoneHint" style="font-size:11px; color:#6c757d;">
            📱 Must be 11 digits and start with 09 (e.g., 09123456789)
          </div>
        </div>
        <div class="fg"><label class="flabel">Home Address <span class="req">*</span></label>
          <input type="text" name="address" class="fc" placeholder="House No., Street, Barangay" required
            value="<?= e($old['address'] ?? '') ?>">
        </div>
        <div class="frow">
          <div class="fg"><label class="flabel">Password <span class="req">*</span></label>
            <input type="password" name="password" id="password" class="fc" placeholder="Min. 6 characters" required>
            <div class="fhint" id="passwordHint" style="font-size:11px; color:#6c757d;">
              🔒 Password must contain: 6+ characters, at least 1 letter, 1 number, and 1 symbol
            </div>
          </div>
          <div class="fg"><label class="flabel">Confirm Password <span class="req">*</span></label>
            <input type="password" name="confirm" id="confirm" class="fc" placeholder="Re-enter" required>
            <div class="fhint" id="confirmHint" style="font-size:11px;"></div>
          </div>
        </div>

        <!-- Terms -->
        <div class="fg" style="display:flex;gap:8px;align-items:flex-start;">
          <input type="checkbox" id="terms" required style="margin-top:3px;flex-shrink:0;">
          <label for="terms" style="font-size:12px;color:#6c757d;">I agree to the terms and conditions of the Barangay
            AMS. My information will be used solely for barangay service purposes.</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">📧 Register &amp; Send
          Verification</button>
      </form>
      <p style="text-align:center;margin-top:14px;font-size:13px;color:#6c757d;">
        Already have an account? <a href="index.php" style="color:#003087;font-weight:600;">Sign in</a>
      </p>
    </div>
    <div class="auth-footer">&copy; <?= date('Y') ?> Barangay AMS &mdash; Republic of the Philippines</div>
  </div>

  <script>
    // Phone number validation (real-time)
    const phone = document.getElementById('phone');
    const phoneHint = document.getElementById('phoneHint');

    function validatePhone() {
      let value = phone.value;
      // Remove any non-digit characters
      value = value.replace(/\D/g, '');

      if (value.length === 0) {
        phoneHint.innerHTML = '📱 Must be 11 digits and start with 09 (e.g., 09123456789)';
        phoneHint.style.color = '#6c757d';
        return false;
      }

      if (value.length !== 11) {
        phoneHint.innerHTML = `❌ Need ${11 - value.length} more digit(s) - Must be exactly 11 digits`;
        phoneHint.style.color = '#dc3545';
        return false;
      }

      if (!value.startsWith('09')) {
        phoneHint.innerHTML = '❌ Phone number must start with 09';
        phoneHint.style.color = '#dc3545';
        return false;
      }

      phoneHint.innerHTML = '✅ Valid phone number!';
      phoneHint.style.color = '#198754';
      return true;
    }

    // Auto-format: remove non-digits, limit to 11 chars
    phone.addEventListener('input', function () {
      let value = this.value;
      value = value.replace(/\D/g, '');
      if (value.length > 11) {
        value = value.slice(0, 11);
      }
      this.value = value;
      validatePhone();
    });

    phone.addEventListener('blur', function () {
      validatePhone();
    });

    // Password validation
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm');
    const passwordHint = document.getElementById('passwordHint');
    const confirmHint = document.getElementById('confirmHint');

    function validatePassword() {
      const pass = password.value;
      let isValid = true;
      let message = '🔒 Password must contain: ';
      const checks = [];

      if (pass.length >= 6) {
        checks.push('✓ 6+ characters');
      } else {
        checks.push('✗ 6+ characters');
        isValid = false;
      }

      if (/[A-Za-z]/.test(pass)) {
        checks.push('✓ letter');
      } else {
        checks.push('✗ letter');
        isValid = false;
      }

      if (/[0-9]/.test(pass)) {
        checks.push('✓ number');
      } else {
        checks.push('✗ number');
        isValid = false;
      }

      if (/[^A-Za-z0-9]/.test(pass)) {
        checks.push('✓ symbol');
      } else {
        checks.push('✗ symbol');
        isValid = false;
      }

      if (pass.length === 0) {
        passwordHint.innerHTML = '🔒 Password must contain: 6+ characters, at least 1 letter, 1 number, and 1 symbol';
        passwordHint.style.color = '#6c757d';
      } else if (isValid) {
        passwordHint.innerHTML = '✅ Strong password!';
        passwordHint.style.color = '#198754';
      } else {
        passwordHint.innerHTML = checks.join(' · ');
        passwordHint.style.color = '#dc3545';
      }

      return isValid;
    }

    function validateConfirm() {
      if (confirm.value.length === 0) {
        confirmHint.innerHTML = '';
        return false;
      } else if (password.value === confirm.value) {
        confirmHint.innerHTML = '✅ Passwords match';
        confirmHint.style.color = '#198754';
        return true;
      } else {
        confirmHint.innerHTML = '❌ Passwords do not match';
        confirmHint.style.color = '#dc3545';
        return false;
      }
    }

    password.addEventListener('input', function () {
      validatePassword();
      validateConfirm();
    });

    confirm.addEventListener('input', validateConfirm);

    // Form validation before submit
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function (e) {
      // Check phone number
      if (!validatePhone()) {
        e.preventDefault();
        alert('Please enter a valid phone number: 11 digits starting with 09 (e.g., 09123456789)');
        submitBtn.disabled = false;
        submitBtn.textContent = '📧 Register & Send Verification';
        return false;
      }

      // Check password strength
      if (!validatePassword()) {
        e.preventDefault();
        alert('Please create a stronger password. It must have at least 6 characters, including letters, numbers, and symbols.');
        submitBtn.disabled = false;
        submitBtn.textContent = '📧 Register & Send Verification';
        return false;
      }

      if (password.value !== confirm.value) {
        e.preventDefault();
        alert('Passwords do not match. Please re-enter.');
        submitBtn.disabled = false;
        submitBtn.textContent = '📧 Register & Send Verification';
        return false;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = '⏳ Verifying...';

      grecaptcha.ready(function () {
        grecaptcha.execute('6LcqqOYsAAAAAA_An4AwN1h8o1V7v2lxpwQkX9Yg', { action: 'register' }).then(function (token) {
          const tokenInput = document.createElement('input');
          tokenInput.type = 'hidden';
          tokenInput.name = 'recaptcha_token';
          tokenInput.value = token;
          form.appendChild(tokenInput);
          form.submit();
        });
      });
    });
  </script>
</body>

</html>