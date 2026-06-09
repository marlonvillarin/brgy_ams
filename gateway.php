<?php
session_start();

// If already verified recently, skip gateway
if (isset($_SESSION['human_verified']) && $_SESSION['human_verified'] === true) {
    header("Location: index.php");
    exit();
}

$err = '';

// ✅ Your existing v2 secret key
$recaptcha_secret = "6LeHI80sAAAAAHLwBxn2IO6qpvMC3KFBci0HD8Kx";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptcha_response)) {
        $err = "Please complete the verification.";
    } else {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
        $response_data = json_decode($verify);

        if ($response_data->success) {
            // Mark as verified for this session
            $_SESSION['human_verified'] = true;
            header("Location: index.php");
            exit();
        } else {
            $err = "Verification failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Security Check — Barangay AMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            background: #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .gateway-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 450px;
            width: 90%;
            text-align: center;
        }

        .gateway-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .gateway-title {
            font-size: 24px;
            font-weight: 700;
            color: #003087;
            margin-bottom: 8px;
        }

        .gateway-desc {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 24px;
        }

        .g-recaptcha {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .btn-verify {
            background: #003087;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }

        .btn-verify:hover {
            background: #002266;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="gateway-card">
        <div class="gateway-icon">🛡️</div>
        <div class="gateway-title">Security Check</div>
        <div class="gateway-desc">
            Please verify you're human before continuing to the Barangay Appointment Management System.
        </div>

        <?php if ($err): ?>
            <div class="error-msg">❌
                <?= htmlspecialchars($err) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="g-recaptcha" data-sitekey="6LeHI80sAAAAAJxSEGDSzBlLKAZX-HZYgNvJliT_"></div>
            <button type="submit" class="btn-verify">✓ Verify & Continue</button>
        </form>

        <div style="margin-top: 20px; font-size: 11px; color: #adb5bd;">
            Protected by reCAPTCHA
        </div>
    </div>
</body>

</html>