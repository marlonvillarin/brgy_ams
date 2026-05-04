<?php
// includes/header.php
$cur = basename($_SERVER['PHP_SELF']);
$isAd = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base = $isAd ? '../' : '';
$uid = $_SESSION['user_id'] ?? 0;
$unread = ($uid && isset($conn)) ? getUnread($conn, $uid) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= isset($page_title) ? e($page_title) : 'Barangay AMS' ?></title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <style>
    #DataTables_Table_0_wrapper .dataTables_filter input,
    #DataTables_Table_0_wrapper .dataTables_length select {
      border: 1px solid #ced4da;
      border-radius: 6px;
      padding: 5px 9px;
      font-size: 13px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: #003087 !important;
      color: #fff !important;
      border-radius: 4px;
      border: none !important;
    }
  </style>
</head>

<body>

  <!-- Header -->
  <header class="header no-print">
    <div class="brand">
      <div class="brand-logo">🏛️</div>
      <div>
        <h1>Barangay AMS</h1>
        <small>Appointment Management System</small>
      </div>
    </div>
    <div class="hdr-right">
      <?php if (isLoggedIn()): ?>
        <!-- Notification Bell -->
        <a href="<?= $base ?>notifications.php" class="notif-btn" title="Notifications">
          🔔
          <?php if ($unread > 0): ?>
            <span class="notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= $base ?>profile.php" class="user-chip">
          👤 <?= e($_SESSION['name']) ?>
          <?php if (isAdmin()): ?><span class="badge badge-admin"
              style="font-size:10px;padding:2px 7px;">Admin</span><?php endif; ?>
        </a>
        <a href="<?= $base ?>logout.php" class="btn-logout">Logout</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="layout">
    <!-- Sidebar -->
    <nav class="sidebar no-print">
      <?php if (isAdmin()): ?>
        <div class="sidebar-section">Admin Panel</div>
        <a href="/brgy_ams/admin/index.php" class="<?= $cur === 'index.php' && $isAd ? 'active' : '' ?>"><span
            class="icon">🏠</span> Dashboard</a>
        <a href="/brgy_ams/admin/all_appointments.php" class="<?= $cur === 'all_appointments.php' ? 'active' : '' ?>">
          <span class="icon">📋</span> Appointments
        </a>
        <a href="/brgy_ams/admin/residents.php" class="<?= $cur === 'residents.php' ? 'active' : '' ?>"><span
            class="icon">👥</span> Residents</a>
        <a href="/brgy_ams/admin/walkin.php" class="<?= $cur === 'walkin.php' ? 'active' : '' ?>">
          <span class="icon">🚶</span> Walk-in
        </a>
        <a href="/brgy_ams/admin/reports.php" class="<?= $cur === 'reports.php' ? 'active' : '' ?>"><span
            class="icon">📊</span>
          Reports</a>

        <a href="/brgy_ams/notifications.php" class="<?= $cur === 'notifications.php' && !$isAd ? 'active' : '' ?>">
          <span class="icon">🔔</span> Notifications
          <?php if ($unread > 0): ?><span class="notif-badge"
              style="position:static;margin-left:auto;"><?= $unread ?></span><?php endif; ?>
        </a>
      <?php else: ?>
        <div class="sidebar-section">Resident Portal</div>
        <a href="/brgy_ams/dashboard.php" class="<?= $cur === 'dashboard.php' ? 'active' : '' ?>"><span
            class="icon">🏠</span> Dashboard</a>
        <a href="/brgy_ams/records.php" class="<?= $cur === 'records.php' ? 'active' : '' ?>">
          <span class="icon">📋</span> View Records
        </a>
        <a href="/brgy_ams/book.php" class="<?= $cur === 'book.php' ? 'active' : '' ?>"><span class="icon">➕</span> Book
          Appointment</a>
        <a href="/brgy_ams/notifications.php" class="<?= $cur === 'notifications.php' ? 'active' : '' ?>">
          <span class="icon">🔔</span> Notifications
          <?php if ($unread > 0): ?><span class="notif-badge"
              style="position:static;margin-left:auto;"><?= $unread ?></span><?php endif; ?>
        </a>
      <?php endif; ?>
      <div class="sidebar-section">Account</div>
      <a href="<?= $base ?>profile.php" class="<?= $cur === 'profile.php' ? 'active' : '' ?>"><span
          class="icon">👤</span>
        Profile</a>
      <a href="<?= $base ?>logout_confirm.php"><span class="icon">🚪</span> Logout</a>
    </nav>



    <main class="main">