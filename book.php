<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
if (isAdmin()) {
  header("Location:admin/index.php");
  exit();
}

$documents = [
  'Barangay Clearance' => '🏠 For employment, loans, and general purposes.',
  'Barangay Certificate of Residency' => '📍 Proof that you reside in this barangay.',
  'Barangay Indigency Certificate' => '🤝 For those needing financial or medical assistance.',
  'Barangay Business Clearance' => '🏪 Required for business permit applications.',
  'Certificate of Good Moral Character' => '✨ For schools, employment, and organizations.',
  'Barangay Blotter Report' => '📝 Official record of an incident or complaint.',
];

$time_slots = ["08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM"];

$err = '';
$old = [];

/**
 * GET SLOT COUNT FUNCTION
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old = $_POST;

  $uid = $_SESSION['user_id'];
  $doc = trim($_POST['document_type'] ?? '');
  $purp = trim($_POST['purpose'] ?? '');
  $date = trim($_POST['appt_date'] ?? '');
  $time = trim($_POST['appt_time'] ?? '');
  $notes = trim($_POST['notes'] ?? '');

  if (!$doc || !$purp || !$date || !$time) {
    $err = "Please fill in all required fields.";
  } elseif (!array_key_exists($doc, $documents)) {
    $err = "Invalid document type.";
  } elseif ($date < date('Y-m-d')) {
    $err = "Please select a future date.";
  } else {

    // 🔥 SLOT LIMIT CHECK (10 per hour)
    $count = getSlotCount($conn, $date, $time);

    if ($count >= 10) {
      $err = "This time slot is already FULL. Please choose another time.";
    } else {

      $s = $conn->prepare("
        SELECT id FROM appointments 
        WHERE user_id=? 
          AND appt_date=? 
          AND appt_time=? 
          AND status!='rejected' 
          AND deleted_at IS NULL
      ");
      $s->bind_param("iss", $uid, $date, $time);
      $s->execute();
      $s->store_result();

      if ($s->num_rows > 0) {
        $err = "You already have an appointment at that date and time.";
      } else {

        $ins = $conn->prepare("
          INSERT INTO appointments(user_id,document_type,purpose,appt_date,appt_time,notes)
          VALUES(?,?,?,?,?,?)
        ");
        $ins->bind_param("isssss", $uid, $doc, $purp, $date, $time, $notes);

        if ($ins->execute()) {
          $appt_id = $conn->insert_id;

          notify(
            $conn,
            $uid,
            'Appointment Submitted',
            'Your appointment for ' . $doc . ' on ' . $date . ' at ' . $time . ' is now pending review.'
          );

          $s2 = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
          if ($ad = $s2->fetch_assoc()) {
            notify(
              $conn,
              $ad['id'],
              'New Appointment Request',
              $_SESSION['name'] . ' has requested ' . $doc . ' on ' . $date . '.'
            );
          }

          setFlash('success', '✅ Appointment submitted! Awaiting barangay approval.');
          header("Location:dashboard.php");
          exit();
        } else {
          $err = "Failed to book. Please try again.";
        }
      }
    }
  }
}

$page_title = "Book Appointment — Barangay AMS";
?>
<?php require_once 'includes/header.php'; ?>
<?php showFlash(); ?>

<div class="page-hdr">
  <div>
    <div class="page-title">Book an Appointment</div>
    <div class="page-sub">Choose a document and schedule your visit.</div>
  </div>
</div>

<?php if ($err): ?>
  <div class="flash flash-error"><span>❌</span><span><?= $err ?></span></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

  <!-- DOCUMENTS -->
  <div class="card">
    <div class="card-hdr">📄 Select Document Type</div>
    <div class="card-body" style="padding:12px;">
      <?php foreach ($documents as $name => $desc): ?>
        <label
          style="display:flex;gap:10px;padding:12px;border:1.5px solid #e9ecef;border-radius:6px;margin-bottom:8px;cursor:pointer;">
          <input type="radio" name="document_select" value="<?= e($name) ?>" form="bookform"
            onchange="document.getElementById('doc_type').value=this.value">
          <div>
            <div style="font-weight:600;color:#003087;"><?= e($name) ?></div>
            <div style="font-size:11px;color:#6c757d;"><?= $desc ?></div>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- FORM -->
  <div class="card">
    <div class="card-hdr">📅 Schedule Details</div>
    <div class="card-body">

      <form id="bookform" method="POST">
        <input type="hidden" name="document_type" id="doc_type">

        <div class="fg">
          <label class="flabel">Purpose</label>
          <input type="text" name="purpose" class="fc" required>
        </div>

        <div class="frow">
          <div class="fg">
            <label class="flabel">Date</label>
            <input type="date" name="appt_date" id="appt_date" class="fc" min="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="fg">
            <label class="flabel">Time</label>
            <select name="appt_time" id="appt_time" class="fc" required>
              <?php foreach ($time_slots as $t): ?>
                <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;">
          Submit
        </button>
      </form>

    </div>
  </div>
</div>

<script>
  // disable full slots dynamically
  document.getElementById('appt_date').addEventListener('change', function () {
    let date = this.value;

    fetch("check_slots.php?date=" + date)
      .then(res => res.json())
      .then(data => {
        let select = document.getElementById('appt_time');

        [...select.options].forEach(opt => {
          if (data.full.includes(opt.value)) {
            opt.disabled = true;
            opt.text = opt.value + " (FULL)";
          } else {
            opt.disabled = false;
            opt.text = opt.value;
          }
        });
      });
  });
</script>

<?php require_once 'includes/footer.php'; ?>