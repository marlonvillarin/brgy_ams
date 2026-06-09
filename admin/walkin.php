<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$documents = [
    'Barangay Clearance' => 'For employment, loans, and general purposes.',
    'Barangay Certificate of Residency' => 'Proof of residency.',
    'Barangay Indigency Certificate' => 'For financial assistance.',
    'Barangay Business Clearance' => 'For business permit.',
    'Certificate of Good Moral Character' => 'For school/employment.',
    'Barangay Blotter Report' => 'Official incident record.',
];

$time_slots = ["08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM"];

$err = '';
$old = [];

/**
 * SLOT LIMIT CHECK (10 PER HOUR)
 */
function getSlotCount($conn, $date, $time)
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM appointments 
        WHERE appt_date=? 
          AND appt_time=? 
          AND status!='rejected' 
          AND deleted_at IS NULL
    ");
    $stmt->bind_param("ss", $date, $time);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}
function validatePhone($phone)
{
    // Remove any spaces, dashes, or special characters
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Check if it's exactly 11 digits and starts with 09
    if (strlen($phone) !== 11) {
        return ['valid' => false, 'message' => 'Phone number must be exactly 11 digits.'];
    }

    if (!preg_match('/^09/', $phone)) {
        return ['valid' => false, 'message' => 'Phone number must start with 09.'];
    }

    return ['valid' => true, 'message' => '', 'cleaned' => $phone];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old = $_POST;

    // WALK-IN DETAILS
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // APPOINTMENT DETAILS
    $doc = trim($_POST['document_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $date = trim($_POST['appt_date'] ?? '');
    $time = trim($_POST['appt_time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$name || !$phone || !$address || !$doc || !$purpose || !$date || !$time) {
        $err = "Please fill in all required fields.";
    } elseif (!array_key_exists($doc, $documents)) {
        $err = "Invalid document type.";
    } elseif ($date < date('Y-m-d')) {
        $err = "Invalid date.";
    } else {


        $count = getSlotCount($conn, $date, $time);

        if ($count >= 10) {
            $err = "This time slot is FULL. Choose another schedule.";
        } else {

            // WALK-IN (no user_id)
            $user_id = null;

            $ins = $conn->prepare("
                INSERT INTO appointments
                (user_id, document_type, purpose, appt_date, appt_time, notes, status, walkin_name, walkin_phone, walkin_address)
                VALUES (?, ?, ?, ?, ?, ?, 'approved', ?, ?, ?)
            ");

            $ins->bind_param(
                "issssssss",
                $user_id,
                $doc,
                $purpose,
                $date,
                $time,
                $notes,
                $name,
                $phone,
                $address
            );

            if ($ins->execute()) {
                setFlash('success', "✅ Walk-in appointment saved successfully!");
                header("Location:index.php");
                exit();
            } else {
                $err = "Failed to save walk-in.";
            }
        }
    }
}

$page_title = "Walk-in Appointment — Admin";
?>

<?php require_once '../includes/header.php'; ?>
<?php showFlash(); ?>

<div class="page-hdr">
    <div>
        <div class="page-title">🚶 Walk-in Appointment</div>
        <div class="page-sub">Create appointment for walk-in residents.</div>
    </div>
</div>

<?php if ($err): ?>
    <div class="flash flash-error">❌ <?= $err ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-hdr">Walk-in Form</div>

    <div class="card-body">

        <form method="POST">

            <div class="fg">
                <label class="flabel">Full Name</label>
                <input type="text" name="name" class="fc" value="<?= e($old['name'] ?? '') ?>">
            </div>

            <div class="frow">
                <div class="fg">
                    <label class="flabel">Contact Number <span class="req">*</span></label>
                    <input type="tel" name="phone" id="phone" class="fc" placeholder="09XXXXXXXXX"
                        value="<?= e($old['phone'] ?? '') ?>" maxlength="11" pattern="09[0-9]{9}" required>
                    <div class="fhint" style="color:#6c757d; font-size:11px;">
                        📱 Must be 11 digits and start with 09 (e.g., 09123456789)
                    </div>
                </div>

                <div class="fg">
                    <label class="flabel">Address</label>
                    <input type="text" name="address" class="fc" value="<?= e($old['address'] ?? '') ?>">
                </div>
            </div>

            <div class="fg">
                <label class="flabel">Document Type</label>
                <select name="document_type" class="fc">
                    <option value="">Select Document</option>
                    <?php foreach ($documents as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= ($old['document_type'] ?? '') === $k ? 'selected' : '' ?>>
                            <?= e($k) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fg">
                <label class="flabel">Purpose</label>
                <input type="text" name="purpose" class="fc" value="<?= e($old['purpose'] ?? '') ?>">
            </div>

            <div class="frow">
                <div class="fg">
                    <label class="flabel">Date</label>
                    <input type="date" name="appt_date" class="fc" min="<?= date('Y-m-d') ?>"
                        value="<?= e($old['appt_date'] ?? '') ?>">
                </div>

                <div class="fg">
                    <label class="flabel">Time</label>
                    <select name="appt_time" class="fc">
                        <?php foreach ($time_slots as $t): ?>
                            <option value="<?= $t ?>" <?= ($old['appt_time'] ?? '') === $t ? 'selected' : '' ?>>
                                <?= $t ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="fg">
                <label class="flabel">Notes</label>
                <textarea name="notes" class="fc" rows="3"><?= e($old['notes'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">
                    💾 Save Walk-in
                </button>
                <a href="index.php" class="btn btn-secondary" style="flex:1;text-align:center;">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>