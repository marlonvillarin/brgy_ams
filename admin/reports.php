<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Totals
$t = $conn->query("SELECT COUNT(*) total, SUM(status='pending') pending, SUM(status='approved') approved, SUM(status='rejected') rejected, SUM(status='rescheduled') rescheduled FROM appointments WHERE deleted_at IS NULL")->fetch_assoc();
$residents = $conn->query("SELECT COUNT(*) c FROM users WHERE role='resident'")->fetch_assoc()['c'];
$deleted   = $conn->query("SELECT COUNT(*) c FROM appointments WHERE deleted_at IS NOT NULL")->fetch_assoc()['c'];

// By document
$by_doc = $conn->query("SELECT document_type, COUNT(*) c FROM appointments WHERE deleted_at IS NULL GROUP BY document_type ORDER BY c DESC")->fetch_all(MYSQLI_ASSOC);

// Monthly (last 6)
$monthly = $conn->query("SELECT DATE_FORMAT(appt_date,'%b %Y') mo, DATE_FORMAT(appt_date,'%Y-%m') ym, COUNT(*) c FROM appointments WHERE deleted_at IS NULL AND appt_date >= DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym ASC")->fetch_all(MYSQLI_ASSOC);

// Recent
$recent = $conn->query("SELECT a.*, u.name un FROM appointments a JOIN users u ON a.user_id=u.id WHERE a.deleted_at IS NULL ORDER BY a.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

function pct($v,$t){return $t>0?round($v/$t*100):0;}
function rbadge($s){$m=['pending'=>['badge-pending','⏳'],'approved'=>['badge-approved','✅'],'rejected'=>['badge-rejected','❌'],'rescheduled'=>['badge-rescheduled','📅']];[$c,$i]=$m[$s]??['',''];return "<span class='badge $c'>$i ".ucfirst($s)."</span>";}

$page_title = "Reports — Admin | Barangay AMS";
$doc_names  = json_encode(array_column($by_doc,'document_type'));
$doc_vals   = json_encode(array_map(fn($r)=>(int)$r['c'],$by_doc));
$mo_labels  = json_encode(array_column($monthly,'mo'));
$mo_vals    = json_encode(array_map(fn($r)=>(int)$r['c'],$monthly));

$extra_js = "<script>
// Status pie
Highcharts.chart('chart-status',{
  chart:{type:'pie',backgroundColor:'transparent',height:260},
  title:{text:''},
  plotOptions:{pie:{innerSize:'50%',dataLabels:{format:'{point.name}<br><b>{point.y}</b> ({point.percentage:.0f}%)',style:{fontSize:'11px'}}}},
  series:[{name:'Appointments',data:[
    {name:'Approved',y:{$t['approved']},color:'#198754'},
    {name:'Pending', y:{$t['pending']}, color:'#ffc107'},
    {name:'Rejected',y:{$t['rejected']},color:'#dc3545'},
    {name:'Rescheduled',y:{$t['rescheduled']},color:'#0d6efd'},
  ]}],credits:{enabled:false}
});
// Monthly column
Highcharts.chart('chart-monthly',{
  chart:{type:'column',backgroundColor:'transparent',height:260},
  title:{text:''},
  xAxis:{categories:{$mo_labels},labels:{style:{fontSize:'11px'}}},
  yAxis:{title:{text:''},allowDecimals:false},
  series:[{name:'Appointments',data:{$mo_vals},color:'#003087',borderRadius:4}],
  legend:{enabled:false},credits:{enabled:false},
  tooltip:{valueSuffix:' appointment(s)'}
});
// By document bar
Highcharts.chart('chart-docs',{
  chart:{type:'bar',backgroundColor:'transparent',height:280},
  title:{text:''},
  xAxis:{categories:{$doc_names},labels:{style:{fontSize:'11px'}}},
  yAxis:{title:{text:''},allowDecimals:false},
  series:[{name:'Requests',data:{$doc_vals},color:'#003087'}],
  legend:{enabled:false},credits:{enabled:false},
  tooltip:{valueSuffix:' request(s)'}
});
</script>";
?>
<?php require_once '../includes/header.php'; ?>

<div class="page-hdr">
  <div><div class="page-title">📊 Reports & Analytics</div><div class="page-sub">System-wide appointment statistics and insights.</div></div>
</div>

<!-- KPI cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));">
  <?php foreach([
    ['Total Appointments','📋',$t['total'],'#003087'],
    ['Approved','✅',$t['approved'],'#198754'],
    ['Pending','⏳',$t['pending'],'#856404'],
    ['Rejected','❌',$t['rejected'],'#dc3545'],
    ['Rescheduled','📅',$t['rescheduled'],'#0d6efd'],
    ['Residents','👥',$residents,'#6f42c1'],
    ['Deleted','🗑️',$deleted,'#6c757d'],
  ] as [$l,$i,$v,$c]): ?>
  <div class="stat-card" style="border-top-color:<?=$c?>;text-align:center;">
    <div class="s-icon"><?=$i?></div>
    <div class="s-val" style="color:<?=$c?>"><?=$v?></div>
    <div class="s-lbl"><?=$l?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts row 1 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
  <div class="card"><div class="card-hdr">📈 Status Distribution</div>
    <div class="card-body"><div id="chart-status"></div></div></div>
  <div class="card"><div class="card-hdr">📅 Monthly Appointments</div>
    <div class="card-body"><div id="chart-monthly"></div></div></div>
</div>

<!-- Charts row 2 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
  <div class="card"><div class="card-hdr">📄 Requests by Document Type</div>
    <div class="card-body"><div id="chart-docs"></div></div></div>

  <div class="card"><div class="card-hdr">📊 Status Breakdown (%)</div>
    <div class="card-body">
      <?php foreach([
        ['Approved',$t['approved'],'#198754'],
        ['Pending',$t['pending'],'#ffc107'],
        ['Rejected',$t['rejected'],'#dc3545'],
        ['Rescheduled',$t['rescheduled'],'#0d6efd'],
      ] as [$l,$v,$c]):
        $p=pct($v,$t['total']);
      ?>
      <div class="prog-wrap">
        <div class="prog-lbl"><span><?=$l?></span><strong style="color:<?=$c?>"><?=$v?> (<?=$p?>%)</strong></div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?=$p?>%;background:<?=$c?>;"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Recent table -->
<div class="card">
  <div class="card-hdr">🕒 Recent Appointments</div>
  <div class="tbl-wrap">
    <table class="gtbl">
      <thead><tr><th>Resident</th><th>Document</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td style="font-weight:600;color:#003087;"><?= e($r['un']) ?></td>
            <td><?= e($r['document_type']) ?></td>
            <td><?= $r['appt_date'] ?></td>
            <td><?= rbadge($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
