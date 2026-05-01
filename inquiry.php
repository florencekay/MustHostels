<?php
require_once '../includes/config.php';
requireLogin('student');
$db = getDB();

$pageTitle = 'Submit Inquiry';
$pageSubtitle = 'Contact the hostel office for assistance';
$userId = $_SESSION['user_id'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject      = clean($_POST['subject'] ?? '');
    $message      = clean($_POST['message'] ?? '');
    $hasSpecial   = isset($_POST['has_special_needs']) ? 1 : 0;
    $specialType  = clean($_POST['special_need_type'] ?? '');

    if (empty($subject) || empty($message)) {
        $error = 'Subject and message are required.';
    } else {
        $db->prepare(
            "INSERT INTO inquiries (student_id, reg_number, subject, message, has_special_needs, special_need_type)
             VALUES (?,?,?,?,?,?)"
        )->execute([$userId, $_SESSION['reg_number'], $subject, $message, $hasSpecial, $specialType]);

        setFlash('success', 'Your inquiry has been submitted. The operator will respond soon.');
        header('Location: inquiry.php');
        exit;
    }
}

// Get past inquiries
$myInquiries = $db->prepare("SELECT * FROM inquiries WHERE student_id=? ORDER BY created_at DESC");
$myInquiries->execute([$userId]);
$myInquiries = $myInquiries->fetchAll();

require_once '../includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
  <!-- Submission Form -->
  <div>
    <div class="page-header"><h2>New Inquiry</h2></div>

    <?php if ($error): ?>
    <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <form method="POST">
          <div class="form-group">
            <label>Subject <span class="req">*</span></label>
            <input type="text" name="subject" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                   placeholder="e.g. Wheelchair accessibility, Room change request..." required>
          </div>

          <div class="form-group">
            <label>Message <span class="req">*</span></label>
            <textarea name="message" rows="5" required
                      placeholder="Describe your issue or request in detail..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" name="has_special_needs" id="hasSpecial"
                     onchange="toggleSpecial()"
                     <?= isset($_POST['has_special_needs']) ? 'checked' : '' ?>>
              ♿ I have a disability or special need
            </label>
          </div>

          <div class="form-group" id="specialTypeGroup" style="display:<?= isset($_POST['has_special_needs']) ? '' : 'none' ?>">
            <label>Type of Disability/Special Need</label>
            <input type="text" name="special_need_type"
                   value="<?= htmlspecialchars($_POST['special_need_type'] ?? '') ?>"
                   placeholder="e.g. Wheelchair user, Visual impairment, Hearing impairment...">
            <div class="form-hint">The operator will arrange a suitable ground floor room for you.</div>
          </div>

          <button type="submit" class="btn btn-primary">📤 Submit Inquiry</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Past Inquiries -->
  <div>
    <div class="page-header"><h2>My Inquiries</h2></div>

    <?php if (empty($myInquiries)): ?>
    <div class="card">
      <div class="empty-state" style="padding:40px">
        <div class="empty-icon">📬</div>
        <h4>No Inquiries Yet</h4>
        <p>Submit your first inquiry using the form.</p>
      </div>
    </div>
    <?php endif; ?>

    <?php foreach ($myInquiries as $inq): ?>
    <div class="card" style="margin-bottom:14px">
      <div class="card-header" style="padding:14px 18px">
        <div>
          <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($inq['subject']) ?></div>
          <div class="text-muted"><?= date('d M Y', strtotime($inq['created_at'])) ?></div>
        </div>
        <?php $b=['open'=>'danger','in_progress'=>'warning','resolved'=>'success'][$inq['status']]??'gray'; ?>
        <span class="badge badge-<?= $b ?>"><?= str_replace('_',' ',ucfirst($inq['status'])) ?></span>
      </div>
      <div class="card-body" style="padding:14px 18px">
        <p style="font-size:13px;color:#555;margin-bottom:8px"><?= nl2br(htmlspecialchars($inq['message'])) ?></p>
        <?php if ($inq['response']): ?>
        <div style="background:var(--green-light);border-radius:6px;padding:12px;margin-top:8px">
          <div style="font-size:11px;font-weight:700;color:var(--green);margin-bottom:4px">✅ OPERATOR RESPONSE</div>
          <div style="font-size:13px;color:#065f46"><?= nl2br(htmlspecialchars($inq['response'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function toggleSpecial() {
    const checked = document.getElementById('hasSpecial').checked;
    document.getElementById('specialTypeGroup').style.display = checked ? '' : 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>
