<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$uid = $_SESSION['user_id'];

// Fetch ALL (including soft-deleted)
$res = $conn->prepare("SELECT * FROM appointments WHERE user_id=? ORDER BY created_at DESC");
$res->bind_param("i", $uid);
$res->execute();
$all = $res->get_result()->fetch_all(MYSQLI_ASSOC);

function badge($s, $deleted = false)
{
    if ($deleted)
        return "<span class='badge badge-deleted'>🗑️ Cancelled</span>";
    $m = ['pending' => ['badge-pending', '⏳ Pending'], 'approved' => ['badge-approved', '✅ Approved'], 'rejected' => ['badge-rejected', '❌ Rejected'], 'rescheduled' => ['badge-rescheduled', '📅 Rescheduled']];
    [$c, $l] = $m[$s] ?? ['', ''];
    return "<span class='badge $c'>$l</span>";
}

require_once 'includes/header.php';
?>

<div class="page-hdr">
    <div>
        <div class="page-title">Appointment Records</div>
        <div class="page-sub">View all your appointments.</div>
    </div>
</div>

<div class="card">
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
                            <td><?= badge($a['status'], $del) ?></td>
                            <td>
                                <?php if (!$del && $a['status'] === 'pending'): ?>
                                    <a href="dashboard.php?cancel=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>