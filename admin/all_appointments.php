<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';
requireAdmin();

$page_title = "All Appointments — Admin | Barangay AMS";

// Handle POST actions (delete, restore, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $appt_id = (int) ($_POST['appt_id'] ?? 0);
    $action = $_POST['action_type'];

    if ($action === 'soft_delete') {
        $conn->query("UPDATE appointments SET deleted_at = NOW() WHERE id = $appt_id");
        setFlash('warning', 'Appointment moved to trash.');
    } elseif ($action === 'restore') {
        $conn->query("UPDATE appointments SET deleted_at = NULL WHERE id = $appt_id");
        setFlash('success', 'Appointment restored.');
    } elseif ($action === 'hard_delete') {
        $conn->query("DELETE FROM appointments WHERE id = $appt_id");
        setFlash('success', 'Appointment permanently deleted.');
    }
    header("Location: all_appointments.php");
    exit();
}

// Get distinct document types for filter dropdown
$docTypes = $conn->query("SELECT DISTINCT document_type FROM appointments ORDER BY document_type")->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<style>
    /* Filter Row Styling */
    .filter-row {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .filter-group {
        flex: 1;
        min-width: 150px;
    }

    .filter-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #495057;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        font-size: 14px;
    }

    .btn-reset {
        background: #6c757d;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
    }

    .btn-reset:hover {
        background: #5a6268;
    }

    /* DataTable Custom Styling */
    .dataTables_wrapper .dataTables_length {
        float: left;
        margin-bottom: 15px;
    }

    .dataTables_wrapper .dataTables_filter {
        float: right;
        margin-bottom: 15px;
    }

    .dataTables_wrapper .dataTables_info {
        float: left;
        padding-top: 15px;
        font-size: 13px;
        color: #6c757d;
    }

    .dataTables_wrapper .dataTables_paginate {
        float: right;
        padding-top: 15px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 6px 12px;
        margin: 0 2px;
        border-radius: 4px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #003087;
        cursor: pointer;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #003087;
        color: white;
        border-color: #003087;
    }

    .dataTables_wrapper .dt-buttons {
        float: left;
        margin-right: 15px;
        margin-bottom: 15px;
    }

    .btn-excel {
        background: #28a745;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-excel:hover {
        background: #218838;
    }

    .btn-print {
        background: #17a2b8;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-print:hover {
        background: #138496;
    }

    /* Table Styling */
    table.dataTable {
        font-size: 13px;
    }

    table.dataTable thead th {
        background: #003087;
        color: white;
        padding: 12px 10px;
        font-weight: 600;
    }

    table.dataTable tbody td {
        padding: 10px;
        vertical-align: middle;
    }

    table.dataTable tbody tr:hover {
        background: #f8f9fa;
    }

    /* Make sure DataTables controls are visible */
    .dataTables_wrapper .dataTables_length {
        float: left;
        margin-bottom: 15px;
        display: block !important;
        visibility: visible !important;
    }

    .dataTables_wrapper .dataTables_length select {
        display: inline-block !important;
        width: auto;
        padding: 5px 10px;
        margin: 0 5px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
</style>

<script>
    $(document).ready(function () {
        const table = $('#appointmentsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'appointments_data.php',
                type: 'POST',
                data: function (d) {
                    d.document_type = $('#filterDocument').val();
                    d.status = $('#filterStatus').val();
                    d.date_from = $('#filterDateFrom').val();
                    d.date_to = $('#filterDateTo').val();
                    d.show_deleted = 0;
                }
            },
            columns: [
                { data: 'resident', title: 'RESIDENT NAME' },
                { data: 'email', title: 'EMAIL' },
                { data: 'contact', title: 'CONTACT' },
                { data: 'address', title: 'ADDRESS' },
                { data: 'document', title: 'DOCUMENT TYPE' },
                { data: 'purpose', title: 'PURPOSE' },
                { data: 'datetime', title: 'DATE & TIME' },
                { data: 'status', title: 'STATUS' },
                { data: 'actions', title: 'ACTIONS', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                search: "🔍 Search:",
                processing: '⏳ Loading appointments...',
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ appointments",
                infoEmpty: "No appointments found",
                infoFiltered: "(filtered from _MAX_ total appointments)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "→",
                    previous: "←"
                },
                zeroRecords: "No matching appointments found"
            },
            dom: 'lBfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '📊 Export to Excel',
                    className: 'btn-excel',
                    title: 'Barangay_Appointments_Report',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                },
                {
                    extend: 'print',
                    text: '🖨️ Print Report',
                    className: 'btn-print',
                    title: 'Barangay Appointments Report',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                }
            ]
        });

        // Apply filters when changed
        $('#filterDocument, #filterStatus, #filterDateFrom, #filterDateTo').on('change', function () {
            table.ajax.reload();
        });

        // Reset all filters
        $('#resetFilters').on('click', function () {
            $('#filterDocument').val('');
            $('#filterStatus').val('');
            $('#filterDateFrom').val('');
            $('#filterDateTo').val('');
            table.ajax.reload();
        });
    });

    function openView(a) {
        const isWalkin = !a.user_id;
        const name = isWalkin ? (a.walkin_name || '-') : (a.u_name || '-');
        const email = isWalkin ? 'Walk-in Client' : (a.u_email || '-');
        const phone = isWalkin ? (a.walkin_phone || '-') : (a.u_phone || '-');
        const address = isWalkin ? (a.walkin_address || '-') : (a.u_address || '-');

        document.getElementById('viewBody').innerHTML = `
    <table style="width:100%;font-size:13px;border-collapse:collapse;">
        ${isWalkin ? '<tr style="background:#fff3cd;"><td style="padding:7px 10px;color:#856404;width:140px;">Type<td style="padding:7px 10px;font-weight:600;">🚶 Walk-in Client</td></tr>' : ''}
        <tr><td style="padding:7px 10px;color:#6c757d;">Resident<td style="padding:7px 10px;font-weight:600;${isWalkin ? 'color:#dc3545;' : 'color:#003087;'}">${escapeHtml(name)}</td></tr>
        <tr style="background:#f8f9fa"><td style="padding:7px 10px;color:#6c757d;">Email<td style="padding:7px 10px;">${escapeHtml(email)}</td></tr>
        <tr><td style="padding:7px 10px;color:#6c757d;">Phone<td style="padding:7px 10px;">${escapeHtml(phone)}</td></tr>
        <tr style="background:#f8f9fa"><td style="padding:7px 10px;color:#6c757d;">Address<td style="padding:7px 10px;">${escapeHtml(address)}</td></tr>
        <tr><td style="padding:7px 10px;color:#6c757d;">Document<td style="padding:7px 10px;font-weight:600;">${escapeHtml(a.document_type)}</td></tr>
        <tr style="background:#f8f9fa"><td style="padding:7px 10px;color:#6c757d;">Purpose<td style="padding:7px 10px;">${escapeHtml(a.purpose)}</td></tr>
        <tr><td style="padding:7px 10px;color:#6c757d;">Date<td style="padding:7px 10px;">${escapeHtml(a.appt_date)}</td></tr>
        <tr style="background:#f8f9fa"><td style="padding:7px 10px;color:#6c757d;">Time<td style="padding:7px 10px;">${escapeHtml(a.appt_time)}</td></tr>
        <tr><td style="padding:7px 10px;color:#6c757d;">Status<td style="padding:7px 10px;">${escapeHtml(a.status).toUpperCase()}</td></tr>
        ${a.notes ? `<tr style="background:#f8f9fa"><td style="padding:7px 10px;color:#6c757d;">Notes<td style="padding:7px 10px;">${escapeHtml(a.notes)}</td></tr>` : ''}
        ${a.admin_note ? `<tr><td style="padding:7px 10px;color:#6c757d;">Admin Note<td style="padding:7px 10px;color:#dc3545;">${escapeHtml(a.admin_note)}</td></tr>` : ''}
    </table>
    ${a.status === 'approved' ? `<a href="../print.php?id=${a.id}" target="_blank" class="btn btn-success" style="margin-top:14px;width:100%;">🖨️ Print Document</a>` : ''}`;
        document.getElementById('viewModal').classList.add('show');
    }

    function openManage(a) {
        document.getElementById('manage_id').value = a.id;
        document.getElementById('del_id').value = a.id;
        const isWalkin = !a.user_id;
        const name = isWalkin ? (a.walkin_name || '-') : (a.u_name || '-');
        const phone = isWalkin ? (a.walkin_phone || '-') : (a.u_phone || '-');

        document.getElementById('manageInfo').innerHTML = `
        <strong style="color:${isWalkin ? '#dc3545' : '#003087'}">${escapeHtml(name)}</strong><br>
        📞 ${escapeHtml(phone)}<br>
        📄 ${escapeHtml(a.document_type)}<br>
        📅 ${escapeHtml(a.appt_date)} ⏰ ${escapeHtml(a.appt_time)}<br>
        🎯 ${escapeHtml(a.purpose)}`;
        document.getElementById('manageModal').classList.add('show');
    }

    function toggleReschedule(value) {
        document.getElementById('reschedFields').style.display = value === 'rescheduled' ? 'block' : 'none';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, function (m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
</script>

<?php
$extra_js = ob_get_clean();
require_once '../includes/header.php';
?>

<?php showFlash(); ?>

<div class="page-hdr">
    <div>
        <div class="page-title">📋 All Appointments</div>
        <div class="page-sub">Complete records - Filter by Document, Status, Date Range | Export to Excel | Print Report
        </div>
    </div>
</div>

<!-- FILTER ROW -->
<div class="filter-row">
    <div class="filter-group">
        <label>📄 Document Type</label>
        <select id="filterDocument">
            <option value="">All Documents</option>
            <?php foreach ($docTypes as $doc): ?>
                <option value="<?= e($doc['document_type']) ?>"><?= e($doc['document_type']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>📊 Status</label>
        <select id="filterStatus">
            <option value="">All Statuses</option>
            <option value="pending">⏳ Pending</option>
            <option value="approved">✅ Approved</option>
            <option value="rejected">❌ Rejected</option>
            <option value="rescheduled">📅 Rescheduled</option>
        </select>
    </div>
    <div class="filter-group">
        <label>📅 Date From</label>
        <input type="date" id="filterDateFrom">
    </div>
    <div class="filter-group">
        <label>📅 Date To</label>
        <input type="date" id="filterDateTo">
    </div>
    <div class="filter-group">
        <label>&nbsp;</label>
        <button id="resetFilters" class="btn-reset">⟳ Reset</button>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="tbl-wrap">
        <table id="appointmentsTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>RESIDENT NAME</th>
                    <th>EMAIL</th>
                    <th>CONTACT</th>
                    <th>ADDRESS</th>
                    <th>DOCUMENT TYPE</th>
                    <th>PURPOSE</th>
                    <th>DATE & TIME</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <!-- DataTables fills this automatically -->
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
                style="background:#f8f9fa;border-left:3px solid #003087;padding:14px;border-radius:6px;font-size:13px;margin-bottom:18px;">
            </div>
            <form method="POST">
                <input type="hidden" name="appt_id" id="manage_id">
                <div class="fg">
                    <label class="flabel">Action</label>
                    <select name="action_type" class="fc" id="manage_action" onchange="toggleReschedule(this.value)">
                        <option value="approved">✅ Approve</option>
                        <option value="rejected">❌ Reject</option>
                        <option value="rescheduled">📅 Reschedule</option>
                        <option value="pending">⏳ Reset to Pending</option>
                    </select>
                </div>
                <div id="reschedFields" style="display:none; margin-top:10px;">
                    <div class="frow">
                        <div class="fg">
                            <label class="flabel">New Date</label>
                            <input type="date" name="new_date" class="fc" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="fg">
                            <label class="flabel">New Time</label>
                            <select name="new_time" class="fc">
                                <?php foreach (["08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM"] as $t): ?>
                                    <option><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="fg">
                    <label class="flabel">Admin Note (Optional)</label>
                    <input type="text" name="admin_note" class="fc" placeholder="e.g., Bring valid ID">
                </div>
                <button type="submit" class="btn btn-success" style="width:100%;margin-top:10px;">✅ Confirm</button>
            </form>
            <hr style="margin:16px 0;">
            <form method="POST" onsubmit="return confirm('Move this appointment to trash?')">
                <input type="hidden" name="appt_id" id="del_id">
                <input type="hidden" name="action_type" value="soft_delete">
                <button type="submit" class="btn btn-danger" style="width:100%;">🗑️ Move to Trash</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>