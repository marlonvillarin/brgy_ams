<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');

$show_deleted = !empty($_POST['show_deleted']);
$fstatus = clean($conn, $_POST['status'] ?? 'all');
$fdate = clean($conn, $_POST['date'] ?? '');

$draw = (int) ($_POST['draw'] ?? 1);
$start = (int) ($_POST['start'] ?? 0);
$length = (int) ($_POST['length'] ?? 15);
$search = clean($conn, $_POST['search']['value'] ?? '');


$cols = [
    0 => 'a.id',
    1 => 'u.name',
    2 => 'u.phone',
    3 => 'u.address',
    4 => 'a.document_type',
    5 => 'a.purpose',
    6 => 'a.appt_date',
    7 => 'a.status',
];
$orderCol = $cols[(int) ($_POST['order'][0]['column'] ?? 0)] ?? 'a.id';
$orderDir = ($_POST['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';


$where = "WHERE 1=1";
if (!$show_deleted)
    $where .= " AND a.deleted_at IS NULL";
if ($fstatus !== 'all')
    $where .= " AND a.status = '$fstatus'";
if ($fdate)
    $where .= " AND a.appt_date = '$fdate'";


$searchClause = '';
if ($search) {
    $searchClause = " AND (
    u.name          LIKE '%$search%' OR
    u.email         LIKE '%$search%' OR
    u.phone         LIKE '%$search%' OR
    a.document_type LIKE '%$search%' OR
    a.purpose       LIKE '%$search%' OR
    a.appt_date     LIKE '%$search%' OR
    a.status        LIKE '%$search%'
  )";
}

$baseFrom = "FROM appointments a
             LEFT JOIN users u ON a.user_id = u.id
             $where $searchClause";


$totalAll = (int) $conn->query(
    "SELECT COUNT(*) c FROM appointments a LEFT JOIN users u ON a.user_id=u.id $where"
)->fetch_assoc()['c'];

$totalFiltered = (int) $conn->query(
    "SELECT COUNT(*) c $baseFrom"
)->fetch_assoc()['c'];

// ── Fetch rows ────────────────────────────────────────────────────
$sql = "SELECT a.*, 
               u.name    AS u_name,
               u.email   AS u_email,
               u.phone   AS u_phone,
               u.address AS u_address
        $baseFrom
        ORDER BY $orderCol $orderDir
        LIMIT $start, $length";

$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// ── Build response ────────────────────────────────────────────────
$data = [];
foreach ($rows as $a) {
    $is_del = !empty($a['deleted_at']);
    $id = (int) $a['id'];
    $status = $a['status'] ?? 'pending';

    // Status badge
    $badgeMap = [
        'pending' => "<span class='badge badge-pending'>⏳ Pending</span>",
        'approved' => "<span class='badge badge-approved'>✅ Approved</span>",
        'rejected' => "<span class='badge badge-rejected'>❌ Rejected</span>",
        'rescheduled' => "<span class='badge badge-rescheduled'>📅 Rescheduled</span>",
    ];
    $statusBadge = $is_del
        ? "<span class='badge badge-deleted'>🗑️ Deleted</span>"
        : ($badgeMap[$status] ?? ucfirst($status));

    // Actions column
    if (!$is_del) {
        $aJson = htmlspecialchars(json_encode($a, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES);
        $printBtn = $status === 'approved'
            ? "<a href='../print.php?id=$id' target='_blank' class='btn btn-success btn-sm'>🖨️</a>"
            : '';
        $actions = "
      <button class='btn btn-secondary btn-sm' onclick='openView({$aJson})'>View</button>
      <button class='btn btn-primary btn-sm'   onclick='openManage({$aJson})'>Manage</button>
      $printBtn";
    } else {
        $actions = "
      <form method='POST' style='display:inline;'>
        <input type='hidden' name='appt_id' value='$id'>
        <input type='hidden' name='action_type' value='restore'>
        <button class='btn btn-warning btn-sm'>↩️ Restore</button>
      </form>
      <form method='POST' style='display:inline;' onsubmit=\"return confirm('Permanently delete?')\">
        <input type='hidden' name='appt_id' value='$id'>
        <input type='hidden' name='action_type' value='hard_delete'>
        <button class='btn btn-danger btn-sm'>🗑️ Purge</button>
      </form>";
    }

    $data[] = [
        'DT_RowClass' => $is_del ? 'soft-del' : '',
        'id' => $id,
        'resident' => "
<strong style='color:#003087;'>" . htmlspecialchars($a['u_name'] ?? $a['walkin_name'] ?? '-') . "</strong><br>
<small style='color:" . (!empty($a['u_email']) ? '#6c757d' : '#dc3545') . "; font-weight:500;'>
" . (!empty($a['u_email'])
            ? htmlspecialchars($a['u_email'])
            : "Walk-in Applicant") . "
</small>",
        'contact' => "📱 " . htmlspecialchars($a['u_phone'] ?? $a['walkin_phone'] ?? '-'),

        'address' => htmlspecialchars($a['u_address'] ?? $a['walkin_address'] ?? '-'),

        'document' => htmlspecialchars($a['document_type'] ?? '-'),

        'purpose' => htmlspecialchars($a['purpose'] ?? '-'),

        'datetime' => "📅 " . htmlspecialchars($a['appt_date'] ?? '-') . "<br>
              ⏰ " . htmlspecialchars($a['appt_time'] ?? '-'),
        'status' => $statusBadge,
        'actions' => $actions,
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalAll,
    'recordsFiltered' => $totalFiltered,
    'data' => $data,
]);