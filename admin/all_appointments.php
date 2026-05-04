<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';
requireAdmin();

$time_slots = ["08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM"];

// ── Handle POST actions ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $appt_id = (int) ($_POST['appt_id'] ?? 0);
    $action = trim($_POST['action_type'] ?? '');
    $admin_note = clean($conn, $_POST['admin_note'] ?? '');

    $fa = $conn->query("SELECT a.*,u.name un,u.email ue FROM appointments a JOIN users u ON a.user_id=u.id WHERE a.id=$appt_id");
    $appt = $fa->fetch_assoc();

    if ($action === 'hard_delete') {
        $conn->query("DELETE FROM appointments WHERE id=$appt_id");
        setFlash('success', 'Appointment permanently deleted.');

    } elseif ($action === 'soft_delete') {
        $conn->query("UPDATE appointments SET deleted_at=NOW() WHERE id=$appt_id");
        if ($appt)
            notify($conn, $appt['user_id'], 'Appointment Removed', 'Your appointment #' . $appt_id . ' has been removed by admin.');
        setFlash('warning', 'Appointment marked as deleted (crossed out).');

    } elseif ($action === 'restore') {
        $conn->query("UPDATE appointments SET deleted_at=NULL WHERE id=$appt_id");
        setFlash('success', 'Appointment restored.');

    } elseif (in_array($action, ['approved', 'rejected', 'pending', 'rescheduled'])) {
        if ($action === 'rescheduled') {
            $nd = clean($conn, $_POST['new_date'] ?? '');
            $nt = clean($conn, $_POST['new_time'] ?? '');
            $conn->query("UPDATE appointments SET status='rescheduled',admin_note='$admin_note',appt_date='$nd',appt_time='$nt' WHERE id=$appt_id");
        } else {
            $conn->query("UPDATE appointments SET status='$action',admin_note='$admin_note' WHERE id=$appt_id");
        }
        if ($appt) {
            $label = ['approved' => 'Approved', 'rejected' => 'Rejected', 'rescheduled' => 'Rescheduled', 'pending' => 'Reset to Pending'][$action];
            notify($conn, $appt['user_id'], "Appointment {$label}", "Your appointment for {$appt['document_type']} on {$appt['appt_date']} has been {$action}." . ($admin_note ? " Note: $admin_note" : ''));
            sendStatusEmail($appt['ue'], $appt['un'], $action, $appt['document_type'], $appt['appt_date'], $admin_note);
        }
        setFlash('success', "Appointment {$action} successfully.");
    }

    header("Location: index.php");
    exit();
}

// ── Stats (still needed for the stat cards) ───────────────────────
$sq = $conn->query("SELECT status, COUNT(*) c FROM appointments WHERE deleted_at IS NULL GROUP BY status");
$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'rescheduled' => 0, 'total' => 0];
while ($r = $sq->fetch_assoc()) {
    $stats[$r['status']] = (int) $r['c'];
    $stats['total'] += $r['c'];
}
$deleted_count = $conn->query("SELECT COUNT(*) c FROM appointments WHERE deleted_at IS NOT NULL")->fetch_assoc()['c'];
$show_deleted = isset($_GET['show_deleted']);

ob_start(); ?>
<script>
    $(document).ready(function () {
        const table = $('#apptTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'appointments_data.php',
                type: 'POST',
                data: function (d) {
                    d.show_deleted = <?= $show_deleted ? 1 : 0 ?>;
                    d.status = $('#filterStatus').val();
                    d.date = $('#filterDate').val();
                }
            },
            columns: [
                { data: 'id' },
                { data: 'resident' },
                { data: 'contact' },
                { data: 'address' },
                { data: 'document' },
                { data: 'purpose' },
                { data: 'datetime' },
                { data: 'status' },
                { data: 'actions', orderable: false },
            ],
            pageLength: 15,
            language: { search: "🔍 Search:", processing: '⏳ Loading...' },
        });

        // Custom filter controls
        $('#filterStatus, #filterDate').on('change', function () {
            table.ajax.reload();
        });
        $('#filterSearchBtn').on('click', function () {
            table.search($('#filterQ').val()).draw();
        });
        $('#filterResetBtn').on('click', function () {
            $('#filterQ, #filterDate').val('');
            $('#filterStatus').val('all');
            table.search('').ajax.reload();
        });
    });

    // ── Modals ────────────────────────────────────────────────────────
    function openView(a) {
        document.getElementById('viewBody').innerHTML = `
  <table style="width:100%;font-size:13px;border-collapse:collapse;">
    <tr>
      <td style="padding:7px 10px;color:#6c757d;width:140px;">Resident</td>
      <td style="padding:7px 10px;font-weight:600;">${a.u_name ?? a.walkin_name ?? '-'}</td>
    </tr>
    <tr style="background:#f8f9fa">
      <td style="padding:7px 10px;color:#6c757d;">Email</td>
      <td style="padding:7px 10px;">${a.u_email ?? '-'}</td>
    </tr>
    <tr>
      <td style="padding:7px 10px;color:#6c757d;">Phone</td>
      <td style="padding:7px 10px;">${a.u_phone ?? a.walkin_phone ?? '-'}</td>
    </tr>
    <tr style="background:#f8f9fa">
      <td style="padding:7px 10px;color:#6c757d;">Document</td>
      <td style="padding:7px 10px;font-weight:600;">${a.document_type ?? '-'}</td>
    </tr>
    <tr>
      <td style="padding:7px 10px;color:#6c757d;">Purpose</td>
      <td style="padding:7px 10px;">${a.purpose ?? '-'}</td>
    </tr>
    <tr style="background:#f8f9fa">
      <td style="padding:7px 10px;color:#6c757d;">Date</td>
      <td style="padding:7px 10px;">${a.appt_date ?? '-'}</td>
    </tr>
    <tr>
      <td style="padding:7px 10px;color:#6c757d;">Time</td>
      <td style="padding:7px 10px;">${a.appt_time ?? '-'}</td>
    </tr>
    <tr style="background:#f8f9fa">
      <td style="padding:7px 10px;color:#6c757d;">Status</td>
      <td style="padding:7px 10px;">${a.status ? a.status.toUpperCase() : '-'}</td>
    </tr>
    ${a.notes ? `<tr><td style="padding:7px 10px;color:#6c757d;">Notes</td><td style="padding:7px 10px;">${a.notes}</td></tr>` : ''}
    ${a.admin_note ? `<tr style="background:#f8f9fa"><td style="padding:7px 10px;color:#6c757d;">Admin Note</td><td style="padding:7px 10px;color:#dc3545;">${a.admin_note}</td></tr>` : ''}
    <tr>
      <td style="padding:7px 10px;color:#6c757d;">Submitted</td>
      <td style="padding:7px 10px;">${a.created_at ?? '-'}</td>
    </tr>
  </table>
  ${a.status === 'approved' ? `<a href="../print.php?id=${a.id}" target="_blank" class="btn btn-success" style="margin-top:14px;width:100%;">🖨️ Print Document</a>` : ''}`;
        document.getElementById('viewModal').classList.add('show');
    }

    function openManage(a) {
        document.getElementById('manage_id').value = a.id;
        document.getElementById('del_id').value = a.id;
        document.getElementById('manageInfo').innerHTML =
            `<strong>${a.u_name ?? a.walkin_name ?? '-'}</strong> &mdash; 📞 ${a.u_phone ?? a.walkin_phone ?? '-'}<br>
     📄 ${a.document_type ?? '-'}<br>
     📅 ${a.appt_date ?? '-'} ⏰ ${a.appt_time ?? '-'}<br>
     🎯 ${a.purpose ?? '-'}`;
        document.getElementById('manageModal').classList.add('show');
    }

    function toggleReschedule(v) {
        document.getElementById('reschedFields').style.display = v === 'rescheduled' ? 'block' : 'none';
    }
</script>
<?php
$extra_js = ob_get_clean();
$page_title = "Appointments — Admin | Barangay AMS";
require_once '../includes/header.php';
?>

<?php showFlash(); ?>

<!-- Page Header -->
<div class="page-hdr">
    <div>
        <div class="page-title">🏠 All Appointments</div>
        <div class="page-sub">Review, approve, reject, or reschedule resident appointments.</div>
    </div>
    <a href="index.php<?= $show_deleted ? '' : '?show_deleted=1' ?>" class="btn btn-secondary btn-sm">
        🗑️ <?= $show_deleted ? 'Hide' : 'Show' ?> Deleted (<?= $deleted_count ?>)
    </a>
</div>


<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:flex-end;">
    <?php if ($show_deleted): ?>

    <?php endif; ?>
    <div style="flex:1;min-width:180px;">
        <label class="flabel" style="font-size:12px;">Search</label>
        <input type="text" id="filterQ" class="fc" placeholder="Name, document, date...">
    </div>
    <div style="min-width:150px;">
        <label class="flabel" style="font-size:12px;">Status</label>
        <select id="filterStatus" class="fc">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="rescheduled">Rescheduled</option>
        </select>
    </div>
    <div style="min-width:150px;">
        <label class="flabel" style="font-size:12px;">Date</label>
        <input type="date" id="filterDate" class="fc">
    </div>
    <button id="filterSearchBtn" class="btn btn-primary">🔍 Search</button>
    <button id="filterResetBtn" class="btn btn-secondary" type="button">Reset</button>
</div>

<!-- Table -->
<div class="card">
    <div class="tbl-wrap">
        <table id="apptTable" class="gtbl dtbl" data-server-side="true" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Resident</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Document</th>
                    <th>Purpose</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<!-- VIEW MODAL -->
<div id="viewModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-hdr">
            <h3>📋 Appointment Details</h3>
            <button class="modal-close"
                onclick="document.getElementById('viewModal').classList.remove('show')">×</button>
        </div>
        <div class="modal-body" id="viewBody"></div>
    </div>
</div>

<!-- MANAGE MODAL -->
<div id="manageModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-hdr">
            <h3>⚙️ Manage Appointment</h3>
            <button class="modal-close"
                onclick="document.getElementById('manageModal').classList.remove('show')">×</button>
        </div>
        <div class="modal-body">
            <div id="manageInfo"
                style="background:#f8f9fa;border-left:3px solid #003087;padding:14px;border-radius:0 6px 6px 0;font-size:13px;margin-bottom:18px;line-height:1.8;">
            </div>
            <form method="POST" id="manageForm">
                <input type="hidden" name="appt_id" id="manage_id">
                <div class="fg">
                    <label class="flabel">Action</label>
                    <select name="action_type" id="manage_action" class="fc" onchange="toggleReschedule(this.value)">
                        <option value="approved">✅ Approve</option>
                        <option value="rejected">❌ Reject</option>
                        <option value="rescheduled">📅 Reschedule</option>
                        <option value="pending">⏳ Reset to Pending</option>
                    </select>
                </div>
                <div id="reschedFields" style="display:none;">
                    <div class="frow">
                        <div class="fg">
                            <label class="flabel">New Date</label>
                            <input type="date" name="new_date" class="fc" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="fg">
                            <label class="flabel">New Time</label>
                            <select name="new_time" class="fc">
                                <?php foreach ($time_slots as $t): ?>
                                    <option><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="fg">
                    <label class="flabel">Admin Note (Optional)</label>
                    <input type="text" name="admin_note" class="fc"
                        placeholder="e.g. Bring valid ID, incomplete requirements...">
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-success" style="flex:1;">✅ Confirm</button>
                    <button type="button" onclick="document.getElementById('manageModal').classList.remove('show')"
                        class="btn btn-secondary" style="flex:1;">Cancel</button>
                </div>
            </form>
            <hr style="border:none;border-top:1px solid #e9ecef;margin:16px 0;">
            <form method="POST"
                onsubmit="return confirm('Mark as deleted? It will show crossed out but remain in records.')">
                <input type="hidden" name="action_type" value="soft_delete">
                <input type="hidden" name="appt_id" id="del_id">
                <button type="submit" class="btn btn-danger" style="width:100%;">🗑️ Delete</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>