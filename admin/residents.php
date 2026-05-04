<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$search = clean($conn, $_GET['q'] ?? '');
$sql = "SELECT u.*, (SELECT COUNT(*) FROM appointments a WHERE a.user_id=u.id AND a.deleted_at IS NULL) ac FROM users u WHERE u.role='resident'";
if ($search) $sql .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%')";
$sql .= " ORDER BY u.created_at DESC";
$residents = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$page_title = "Residents — Admin | Barangay AMS";
?>
<?php require_once '../includes/header.php'; ?>
<?php showFlash(); ?>

<div class="page-hdr">
  <div><div class="page-title">👥 Registered Residents</div><div class="page-sub">All residents registered in the system.</div></div>
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:16px;">
  <input type="text" name="q" class="fc" placeholder="🔍 Search by name, email, or phone..." value="<?= e($search) ?>" style="max-width:340px;">
  <button type="submit" class="btn btn-primary">Search</button>
  <a href="residents.php" class="btn btn-secondary">Reset</a>
</form>

<div class="card">
  <div class="tbl-wrap">
    <table class="gtbl dtbl" style="width:100%">
      <thead>
        <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Verified</th><th>Registered</th><th>Appointments</th></tr>
      </thead>
      <tbody>
      <?php foreach ($residents as $r): ?>
        <tr>
          <td style="color:#6c757d;font-size:12px;"><?= $r['id'] ?></td>
          <td style="font-weight:700;color:#003087;"><?= e($r['name']) ?></td>
          <td><?= e($r['email']) ?></td>
          <td><?= e($r['phone']) ?></td>
          <td><?= e($r['address']) ?></td>
          <td><?= $r['is_verified'] ? "<span class='badge badge-approved'>✅ Verified</span>" : "<span class='badge badge-rejected'>❌ Unverified</span>" ?></td>
          <td style="font-size:12px;color:#6c757d;"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
          <td style="font-weight:700;color:#003087;"><?= $r['ac'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
