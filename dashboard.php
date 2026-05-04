<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
if (isAdmin()) {
  header("Location:admin/index.php");
  exit();
}
$uid = $_SESSION['user_id'];

// Handle cancel (soft approach: mark deleted_at)
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
  $cid = (int) $_GET['cancel'];
  $upd = $conn->prepare("UPDATE appointments SET deleted_at=NOW() WHERE id=? AND user_id=? AND status='pending' AND deleted_at IS NULL");
  $upd->bind_param("ii", $cid, $uid);
  $upd->execute();
  if ($upd->affected_rows > 0) {
    notify($conn, $uid, 'Appointment Cancelled', 'You cancelled your appointment. It will appear as crossed out in your records.');
    setFlash('warning', 'Appointment cancelled (shown as struck through in your list).');
  }
  header("Location:dashboard.php");
  exit();
}

// Fetch ALL (including soft-deleted)
$res = $conn->prepare("SELECT * FROM appointments WHERE user_id=? ORDER BY created_at DESC");
$res->bind_param("i", $uid);
$res->execute();
$all = $res->get_result()->fetch_all(MYSQLI_ASSOC);

// Stats (exclude deleted)
$active = array_filter($all, fn($a) => !$a['deleted_at']);
$total = count($active);
$pending = count(array_filter($active, fn($a) => $a['status'] === 'pending'));
$approved = count(array_filter($active, fn($a) => $a['status'] === 'approved'));
$rejected = count(array_filter($active, fn($a) => $a['status'] === 'rejected'));
$upcoming = count(array_filter($active, fn($a) => $a['status'] === 'approved' && $a['appt_date'] >= date('Y-m-d')));

// Chart data by month
$monthly = $conn->prepare("SELECT DATE_FORMAT(appt_date,'%b') mo,COUNT(*) c FROM appointments WHERE user_id=? AND deleted_at IS NULL GROUP BY DATE_FORMAT(appt_date,'%Y-%m') ORDER BY appt_date DESC LIMIT 6");
$monthly->bind_param("i", $uid);
$monthly->execute();
$months_data = $monthly->get_result()->fetch_all(MYSQLI_ASSOC);

// By doc type
$bytype = $conn->prepare("SELECT document_type, COUNT(*) c FROM appointments WHERE user_id=? AND deleted_at IS NULL GROUP BY document_type");
$bytype->bind_param("i", $uid);
$bytype->execute();
$docdata = $bytype->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "My Appointments — Barangay AMS";

function badge($s, $deleted = false)
{
  if ($deleted)
    return "<span class='badge badge-deleted' style='text-decoration:none'>🗑️ Cancelled</span>";
  $m = ['pending' => ['badge-pending', '⏳ Pending'], 'approved' => ['badge-approved', '✅ Approved'], 'rejected' => ['badge-rejected', '❌ Rejected'], 'rescheduled' => ['badge-rescheduled', '📅 Rescheduled']];
  [$c, $l] = $m[$s] ?? ['', ''];
  return "<span class='badge $c'>$l</span>";
}
?>
<?php require_once 'includes/header.php'; ?>
<?php showFlash(); ?>

<div class="page-hdr">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-sub">Track and manage your barangay appointment requests.</div>
  </div>
  <a href="book.php" class="btn btn-primary">➕ Book Appointment</a>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card" style="border-top-color:#003087">
    <div class="s-icon">📁</div>
    <div class="s-val" style="color:#003087"><?= $total ?></div>
    <div class="s-lbl">Total</div>
  </div>
  <div class="stat-card" style="border-top-color:#198754">
    <div class="s-icon">✅</div>
    <div class="s-val" style="color:#198754"><?= $upcoming ?></div>
    <div class="s-lbl">Upcoming</div>
  </div>
  <div class="stat-card" style="border-top-color:#ffc107">
    <div class="s-icon">⏳</div>
    <div class="s-val" style="color:#856404"><?= $pending ?></div>
    <div class="s-lbl">Pending</div>
  </div>
  <div class="stat-card" style="border-top-color:#dc3545">
    <div class="s-icon">❌</div>
    <div class="s-val" style="color:#dc3545"><?= $rejected ?></div>
    <div class="s-lbl">Rejected</div>
  </div>
</div>

<!-- Charts row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
  <div class="card">
    <div class="card-hdr">📅 Appointments by Month</div>
    <div class="card-body" id="chart-month" style="height:240px;"></div>
  </div>
  <div class="card">
    <div class="card-hdr">📄 By Document Type</div>
    <div class="card-body" id="chart-doc" style="height:240px;"></div>
  </div>
</div>

<!-- Appointments DataTable -->
<div class="card">
  <div class="card-hdr">📋 Appointment Records</div>
  <div class="card-body">
    <div class="tbl-wrap">
      <table class="gtbl dtbl" style="width:100%">
        <thead>
          <tr>
            <th>#</th>
            <th>Document</th>
            <th>Purpose</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all as $a):
            $del = !empty($a['deleted_at']);
            ?>
            <tr class="<?= $del ? 'soft-del' : '' ?>">
              <td><?= $a['id'] ?></td>
              <td><strong><?= e($a['document_type']) ?></strong></td>
              <td><?= e($a['purpose']) ?></td>
              <td><?= $a['appt_date'] ?></td>
              <td><?= $a['appt_time'] ?></td>
              <td><?= badge($a['status'], $del) ?><?php if ($a['admin_note'] && !$del): ?><br><small
                    style="color:#6c757d"><?= e($a['admin_note']) ?></small><?php endif; ?></td>
              <td>
                <?php if (!$del): ?>

                  <?php if ($a['status'] === 'pending'): ?>
                    <a href="dashboard.php?cancel=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
                      onclick="return confirm('Cancel this appointment? It will be crossed out but remain visible.')">Cancel</a>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="font-size:11px;color:#6c757d;">Cancelled on
                    <?= date('M d', strtotime($a['deleted_at'])) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
// Prepare chart JSON
$months_labels = array_map(fn($r) => $r['mo'], array_reverse($months_data));
$months_vals = array_map(fn($r) => (int) $r['c'], array_reverse($months_data));
$doc_names = array_map(fn($r) => $r['document_type'], $docdata);
$doc_vals = array_map(fn($r) => (int) $r['c'], $docdata);
$extra_js = "<script>
// Monthly bar chart
Highcharts.chart('chart-month',{
  chart:{type:'column',backgroundColor:'transparent',height:220,margin:[20,10,40,30]},
  title:{text:''},
  xAxis:{categories:" . json_encode($months_labels) . ",labels:{style:{fontSize:'11px'}}},
  yAxis:{title:{text:''},allowDecimals:false,labels:{style:{fontSize:'11px'}}},
  series:[{name:'Appointments',data:" . json_encode($months_vals) . ",color:'#003087',borderRadius:4}],
  legend:{enabled:false},
  credits:{enabled:false},
  tooltip:{valueSuffix:' appointment(s)'}
});
// Donut chart
Highcharts.chart('chart-doc',{
  chart:{type:'pie',backgroundColor:'transparent',height:220,margin:[10,10,10,10]},
  title:{text:''},
  plotOptions:{pie:{innerSize:'55%',dataLabels:{enabled:true,format:'{point.name}<br>{point.percentage:.0f}%',style:{fontSize:'10px'}}}},
  series:[{name:'Requests',data:" . json_encode(array_map(fn($n, $v) => ['name' => $n, 'y' => $v], $doc_names, $doc_vals)) . " }],
  credits:{enabled:false}
});
</script>";
?>
<?php require_once 'includes/footer.php'; ?>