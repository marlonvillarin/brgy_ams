<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];

// Fetch appointment — resident can only print their own; admin can print any
if (isAdmin()) {
  $stmt = $conn->prepare("
    SELECT a.*, 
           u.name AS u_name, 
           u.address AS u_address, 
           u.phone AS u_phone, 
           u.email AS u_email 
    FROM appointments a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.id=? 
      AND a.status='approved' 
      AND a.deleted_at IS NULL
  ");
  $stmt->bind_param("i", $id);
} else {
  $stmt = $conn->prepare("
    SELECT a.*, 
           u.name AS u_name, 
           u.address AS u_address, 
           u.phone AS u_phone, 
           u.email AS u_email 
    FROM appointments a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.id=? 
      AND a.user_id=? 
      AND a.status='approved' 
      AND a.deleted_at IS NULL
  ");
  $stmt->bind_param("ii", $id, $uid);
}

$stmt->execute();
$a = $stmt->get_result()->fetch_assoc();

if (!$a) {
  setFlash('error', 'Document not found or not approved yet.');
  header("Location:" . (isAdmin() ? "admin/index.php" : "dashboard.php"));
  exit();
}

// ✅ FIXED: WALK-IN SUPPORT (NO DESIGN CHANGE)
$name = htmlspecialchars($a['u_name'] ?? $a['walkin_name'] ?? '-');
$address = htmlspecialchars($a['u_address'] ?? $a['walkin_address'] ?? '-');
$phone = htmlspecialchars($a['u_phone'] ?? $a['walkin_phone'] ?? '-');
$email = htmlspecialchars($a['u_email'] ?? $a['walkin_email'] ?? '-');

// Control number
$ctrl = 'BRG-' . date('Y') . '-' . str_pad($a['id'], 5, '0', STR_PAD_LEFT);
$issued = date('F d, Y');
$purpose = htmlspecialchars($a['purpose']);
$doc = htmlspecialchars($a['document_type']);
// Document-specific body text
$body_texts = [
  'Barangay Clearance' =>
    "This is to certify that <strong>{$name}</strong>, of legal age, Filipino citizen, and a resident of <strong>{$address}</strong>, is known to be of good standing in this barangay and has no derogatory record on file as of this date.<br><br>
         This certification is issued upon the request of the above-named person for the purpose of <strong>{$purpose}</strong> and for whatever legal purpose it may serve.",

  'Barangay Certificate of Residency' =>
    "This is to certify that <strong>{$name}</strong> is a <em>bona fide</em> resident of <strong>{$address}</strong> and has been residing in this barangay for a considerable period of time.<br><br>
         This certification is issued upon the request of the above-named person for <strong>{$purpose}</strong> and for whatever legal purpose this may serve.",

  'Barangay Indigency Certificate' =>
    "This is to certify that <strong>{$name}</strong>, a resident of <strong>{$address}</strong>, belongs to an indigent family and is one of the less fortunate constituents of this barangay.<br><br>
         This certification is issued in connection with <strong>{$purpose}</strong> and for any legal purpose this document may serve.",

  'Barangay Business Clearance' =>
    "This is to certify that <strong>{$name}</strong>, a resident of <strong>{$address}</strong>, has been cleared by this barangay and has no pending obligation or complaint in connection with the operation of the stated business.<br><br>
         This clearance is issued for the purpose of <strong>{$purpose}</strong> and for whatever legal purpose it may serve.",

  'Certificate of Good Moral Character' =>
    "This is to certify that <strong>{$name}</strong>, a resident of <strong>{$address}</strong>, is personally known to the undersigned and is of good moral character, honest, law-abiding citizen, and has a good standing in the community.<br><br>
         This certification is issued upon the request of the above-named person for <strong>{$purpose}</strong> and to attest to the truth of the foregoing.",

  'Barangay Blotter Report' =>
    "This is to certify that the following matter has been duly recorded in the Barangay Blotter Book of this barangay.<br><br>
         <strong>Complainant/Requesting Party:</strong> {$name}<br>
         <strong>Address:</strong> {$address}<br>
         <strong>Nature of Request:</strong> {$purpose}<br><br>
         This blotter entry is issued upon the request of the above-named individual for record and reference purposes.",
];

$body_text = $body_texts[$a['document_type']] ?? "This is to certify that <strong>{$name}</strong>, a resident of <strong>{$address}</strong>, has been issued this document for the purpose of <strong>{$purpose}</strong>.";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Print: <?= e($a['document_type']) ?> — Barangay AMS</title>
  <link rel="stylesheet" href="<?= isAdmin() ? '../' : '' ?>assets/css/style.css">
  <style>
    @media screen {
      .print-page {
        max-width: 700px;
        margin: 30px auto;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 6px;
      }

      .print-actions {
        max-width: 700px;
        margin: 0 auto 16px;
        display: flex;
        gap: 10px;
        padding: 16px;
      }
    }

    @media print {
      .print-actions {
        display: none !important;
      }

      body {
        margin: 0;
        background: white;
      }

      .print-page {
        margin: 0;
        border: none;
        border-radius: 0;
        box-shadow: none;
      }
    }

    body {
      font-family: Arial, sans-serif;
      background: #f1f3f5;
    }

    .print-page {
      padding: 40px 50px;
    }

    .ph {
      text-align: center;
      margin-bottom: 6px;
    }

    .ph-title {
      font-size: 11px;
      color: #555;
      letter-spacing: .5px;
    }

    .ph-brgy {
      font-size: 18px;
      font-weight: 700;
      color: #003087;
      text-transform: uppercase;
      margin: 4px 0;
    }

    .ph-loc {
      font-size: 11px;
      color: #555;
    }

    .ph-line {
      border: none;
      border-top: 3px double #003087;
      margin: 14px 0 8px;
    }

    .doc-title {
      text-align: center;
      font-size: 16px;
      font-weight: 700;
      color: #003087;
      text-transform: uppercase;
      letter-spacing: 2px;
      text-decoration: underline;
      margin: 20px 0 18px;
    }

    .doc-ctrl {
      display: flex;
      justify-content: space-between;
      font-size: 11px;
      color: #666;
      margin-bottom: 20px;
    }

    .doc-body {
      font-size: 13px;
      line-height: 2;
      text-align: justify;
      margin-bottom: 24px;
      color: #222;
    }

    .doc-note {
      font-size: 11px;
      color: #555;
      margin-top: 10px;
      font-style: italic;
      border-top: 1px solid #e9ecef;
      padding-top: 10px;
    }

    .sig-row {
      display: flex;
      justify-content: flex-end;
      margin-top: 40px;
    }

    .sig-box {
      text-align: center;
      width: 200px;
    }

    .sig-name {
      font-weight: 700;
      font-size: 14px;
      color: #003087;
      border-top: 1px solid #000;
      padding-top: 6px;
      margin-top: 30px;
    }

    .sig-title {
      font-size: 11px;
      color: #555;
      margin-top: 2px;
    }

    .seal {
      width: 80px;
      height: 80px;
      border: 2px dashed #aaa;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      margin: 0 auto 8px;
    }

    .doc-footer {
      text-align: center;
      margin-top: 30px;
      font-size: 10px;
      color: #aaa;
      border-top: 1px solid #e9ecef;
      padding-top: 10px;
    }

    .ph-logos {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 20px;
      margin-bottom: 8px;
    }

    .logo-circle {
      width: 60px;
      height: 60px;
      background: #003087;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: #FFD700;
    }
  </style>
</head>

<body>

  <!-- Action bar (screen only) -->
  <div class="print-actions no-print">
    <button onclick="window.print()" class="btn btn-primary">🖨️ Print Document</button>
    <a href="<?= isAdmin() ? 'admin/index.php' : 'dashboard.php' ?>" class="btn btn-secondary">← Back</a>
    <span style="margin-left:auto;font-size:12px;color:#6c757d;align-self:center;">Control No:
      <strong><?= $ctrl ?></strong></span>
  </div>

  <!-- Printable document -->
  <div class="print-page">

    <!-- Header -->
    <div class="ph">
      <div class="ph-logos">
        <!-- LEFT: Barangay Maguikay Logo (replaces the PH flag) -->
        <div class="logo-circle">
          <?php
          $logo_path = 'assets/images/brgy_maguikay_logo.png';
          if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/brgy_ams/assets/images/brgy_maguikay_logo.png')): ?>
            <img src="<?= $logo_path ?>" alt="Barangay Maguikay Logo"
              style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
          <?php else: ?>
            🏛️
          <?php endif; ?>
        </div>

        <div>
          <div class="ph-title">Republic of the Philippines</div>
          <div class="ph-brgy">Barangay Maguikay</div>
          <div class="ph-loc">Mandaue City, Cebu</div>
        </div>
        <div class="logo-circle">
          <?php
          $bp_logo_path = 'assets/images/bagong_pilipinas_logo.png';
          if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/brgy_ams/assets/images/bagong_pilipinas_logo.png')): ?>
            <img src="<?= $bp_logo_path ?>" alt="Bagong Pilipinas Logo"
              style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
          <?php else: ?>
            🏛️
          <?php endif; ?>
        </div>
      </div>
    </div>
    <hr class="ph-line">

    <!-- Doc title -->
    <div class="doc-title"><?= $doc ?></div>

    <!-- Control + Date -->
    <div class="doc-ctrl">
      <span>Control No.: <strong><?= $ctrl ?></strong></span>
      <span>Date Issued: <strong><?= $issued ?></strong></span>
    </div>

    <!-- Salutation -->
    <div class="doc-body">
      <strong>TO WHOM IT MAY CONCERN:</strong><br><br>
      <?= $body_text ?>
      <br><br>
      Given this <strong><?= date('jS') ?> day of <?= date('F, Y') ?></strong> at Barangay Maguikay Mandaue.
      <div class="doc-note">
        This document is valid for <strong>30 days</strong> from the date of issuance. Not valid without the official
        dry seal and signature of the Barangay Captain or authorized official.
      </div>
    </div>

    <!-- Signature: SEAL on LEFT, SIGNATURE on RIGHT -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px;">

      <!-- LEFT SIDE: SEAL ONLY (with actual image) -->
      <div style="text-align: center;">
        <div class="seal">
          <?php
          $seal_path = 'assets/images/barangay_seal_black.png';
          if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/brgy_ams/assets/images/barangay_seal_black.png')): ?>
            <img src="<?= $seal_path ?>" alt="Barangay Seal"
              style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
          <?php else: ?>
            🏛️
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT SIDE: SIGNATURE + NAME (stays in original position) -->
      <div style="text-align: center;">
        <div style="margin: 15px 0 5px 0;">
          <?php
          $signature_path = 'assets/images/signature1.png';
          if (file_exists($signature_path)): ?>
            <img src="<?= $signature_path ?>" alt="Barangay Captain Signature"
              style="width: 180px; height: auto; max-height: 60px;">
          <?php else: ?>
            <div style="height: 40px; border-bottom: 1px solid #000; width: 180px; margin: 0 auto;"></div>
            <div style="font-size: 10px; color: #999; margin-top: 5px;">(Signature)</div>
          <?php endif; ?>
        </div>
        <div class="sig-name">HON. MARLON P. VILLARIN</div>
        <div class="sig-title">Punong Barangay</div>
      </div>
    </div>

    <!-- Footer -->
    <div class="doc-footer">
      Issued by Barangay AMS &bull; Control No. <?= $ctrl ?> &bull; Issued <?= $issued ?>
    </div>

  </div>

</body>

</html>