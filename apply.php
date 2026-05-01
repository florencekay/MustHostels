<?php
require_once '../includes/config.php';
require_once '../includes/email.php';
requireLogin('student');
$db = getDB();

$pageTitle = 'Apply for Room';
$pageSubtitle = 'Submit your hostel accommodation application';

$userId = $_SESSION['user_id'];

// Check if already applied
$existing = $db->prepare("SELECT * FROM applications WHERE student_id=? ORDER BY applied_at DESC LIMIT 1");
$existing->execute([$userId]);
$existingApp = $existing->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($existingApp) {
        $error = 'You have already submitted an application. Please check your application status.';
    } else {
        $regNumber  = strtoupper(clean($_POST['reg_number'] ?? ''));
        $fullName   = clean($_POST['full_name'] ?? '');
        $year       = (int)($_POST['year_of_study'] ?? 0);
        $gender     = clean($_POST['gender'] ?? '');
        $specialNeeds = clean($_POST['special_needs'] ?? '');

        if (empty($regNumber) || empty($fullName) || !$year || empty($gender)) {
            $error = 'Please fill in all required fields.';
        } elseif ($year < 1 || $year > 5) {
            $error = 'Year of study must be between 1 and 5.';
        } else {
            $email = genEmail($regNumber);

            try {
                // Insert application
                $db->prepare(
                    "INSERT INTO applications (student_id, reg_number, full_name, year_of_study, gender, email, special_needs)
                     VALUES (?,?,?,?,?,?,?)"
                )->execute([$userId, $regNumber, $fullName, $year, $gender, $email, $specialNeeds ?: null]);

                $appId = $db->lastInsertId();

                // Generate invoice
                $invoiceNum = genInvoiceNumber();
                $dueDate = date('Y-m-d', strtotime('+14 days'));

                $db->prepare(
                    "INSERT INTO invoices (application_id, student_id, invoice_number, amount, due_date)
                     VALUES (?,?,?,?,?)"
                )->execute([$appId, $userId, $invoiceNum, ACCOMMODATION_FEE, $dueDate]);

                // Send invoice email
                sendInvoiceEmail($email, $fullName, $invoiceNum, ACCOMMODATION_FEE, date('d F Y', strtotime($dueDate)));

                setFlash('success', 'Application submitted! An invoice has been sent to your MUST email: ' . $email);
                header('Location: dashboard.php');
                exit;

            } catch (Exception $e) {
                $error = 'Application failed. Please try again. (' . $e->getMessage() . ')';
            }
        }
    }
}

// Prefill from user record
$userRecord = $db->prepare("SELECT * FROM users WHERE id=?");
$userRecord->execute([$userId]);
$userRecord = $userRecord->fetch();

require_once '../includes/header.php';
?>

<?php if ($existingApp): ?>
<div class="alert alert-warning">
  ⚠️ You have already applied. <a href="status.php">Check your application status →</a>
</div>
<?php else: ?>

<div style="max-width:680px">
  <div class="page-header">
    <h2>Apply for Hostel Room</h2>
    <p>Fill in the form below to apply for hostel accommodation. Your email will be auto-generated from your registration number.</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Info box -->
  <div style="background:rgba(15,45,82,0.05);border:1px solid rgba(15,45,82,0.15);border-radius:10px;padding:18px;margin-bottom:24px">
    <strong style="color:var(--navy)">ℹ️ Before You Apply</strong>
    <ul style="margin:10px 0 0 20px;color:#444;font-size:13px;line-height:2">
      <li>There are <strong><?= max(0, MAX_CAPACITY - (int)$db->query("SELECT COUNT(*) FROM allocations")->fetchColumn()) ?></strong> spaces remaining out of <?= MAX_CAPACITY ?> total</li>
      <li>Allocation is random and not guaranteed</li>
      <li>An invoice of <strong>MWK <?= number_format(ACCOMMODATION_FEE) ?></strong> will be sent to your MUST email</li>
      <li>Year 1 students go to extension blocks; Years 2–3 to 1st floor; Years 4–5 to 2nd floor</li>
    </ul>
  </div>

  <div class="card">
    <div class="card-header"><h3>✍️ Application Form</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label>Registration Number <span class="req">*</span></label>
            <input type="text" name="reg_number"
                   value="<?= htmlspecialchars($_POST['reg_number'] ?? $userRecord['reg_number'] ?? '') ?>"
                   placeholder="e.g. MU/BSCS/24/001" required
                   oninput="updateEmail(this.value)">
          </div>
          <div class="form-group">
            <label>Generated Email</label>
            <input type="text" id="emailDisplay"
                   value="<?= genEmail($userRecord['reg_number'] ?? '') ?>"
                   readonly style="background:var(--gray-50);color:var(--gray-500)">
            <div class="form-hint">Notifications will be sent here</div>
          </div>
        </div>

        <div class="form-group">
          <label>Full Name <span class="req">*</span></label>
          <input type="text" name="full_name"
                 value="<?= htmlspecialchars($_POST['full_name'] ?? $userRecord['full_name'] ?? '') ?>"
                 placeholder="Your full name as on student ID" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Year of Study <span class="req">*</span></label>
            <select name="year_of_study" required>
              <option value="">-- Select Year --</option>
              <?php for ($y=1;$y<=5;$y++): ?>
              <option value="<?= $y ?>" <?= (($_POST['year_of_study'] ?? '') == $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Gender <span class="req">*</span></label>
            <select name="gender" required>
              <option value="">-- Select Gender --</option>
              <option value="male" <?= (($_POST['gender'] ?? $userRecord['gender'] ?? '') === 'male') ? 'selected' : '' ?>>Male</option>
              <option value="female" <?= (($_POST['gender'] ?? $userRecord['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Special Needs / Disability (optional)</label>
          <textarea name="special_needs" rows="3"
                    placeholder="If you have any disability or special needs (e.g. wheelchair user, visual impairment), please describe here so the operator can handle your allocation manually."><?= htmlspecialchars($_POST['special_needs'] ?? '') ?></textarea>
          <div class="form-hint">♿ If you use a wheelchair or have mobility challenges, your request will be manually reviewed for ground floor allocation.</div>
        </div>

        <div style="background:var(--gray-50);border-radius:8px;padding:14px;margin-bottom:20px;font-size:13px;color:#444">
          <strong>Allocation Rules:</strong>
          <ul style="margin:8px 0 0 18px;line-height:1.9">
            <li><strong>Year 1:</strong> Extension Blocks A–K (Male) or L–N (Female)</li>
            <li><strong>Year 2 & 3:</strong> Halls 1–4 (Male), Halls 5–7 (Female) — First Floor</li>
            <li><strong>Year 4 & 5:</strong> Hall 8 (Male Senior), Halls 5–7 (Female) — Second Floor</li>
          </ul>
        </div>

        <button type="submit" class="btn btn-primary btn-lg"
                onclick="return confirm('Submit your application? An invoice will be sent to your MUST email.')">
          🏠 Submit Application →
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function updateEmail(val) {
    const clean = val.replace(/[^a-zA-Z0-9\/\-]/g,'').toLowerCase();
    document.getElementById('emailDisplay').value = (clean || 'regnumber') + '@must.ac.mw';
}
</script>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
