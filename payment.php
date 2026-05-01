<?php
require_once '../includes/config.php';
requireLogin('student');
$db = getDB();

$pageTitle = 'Upload Payment';
$userId = $_SESSION['user_id'];

// Get invoice
$invStmt = $db->prepare("SELECT * FROM invoices WHERE student_id=? AND status='unpaid' ORDER BY issued_at DESC LIMIT 1");
$invStmt->execute([$userId]);
$invoice = $invStmt->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$invoice) {
        $error = 'No unpaid invoice found.';
    } else {
        $method  = clean($_POST['payment_method'] ?? '');
        $transId = clean($_POST['transaction_id'] ?? '');
        $amount  = (float)($_POST['amount_paid'] ?? 0);

        if (empty($method) || $amount <= 0) {
            $error = 'Please fill all required fields.';
        } else {
            // Handle file upload
            $payslipPath = '';
            if (!empty($_FILES['payslip']['name'])) {
                $allowed = ['jpg','jpeg','png','pdf'];
                $ext = strtolower(pathinfo($_FILES['payslip']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = 'Only JPG, PNG or PDF files allowed for payslip.';
                } else {
                    $filename = 'payslip_' . $userId . '_' . time() . '.' . $ext;
                    $dest = '../uploads/payslips/' . $filename;
                    if (move_uploaded_file($_FILES['payslip']['tmp_name'], $dest)) {
                        $payslipPath = 'uploads/payslips/' . $filename;
                    }
                }
            }

            if (!$error) {
                $receiptNum = genReceiptNumber();

                $db->prepare(
                    "INSERT INTO payments (invoice_id, student_id, receipt_number, amount_paid, payment_method, transaction_id, payslip_path)
                     VALUES (?,?,?,?,?,?,?)"
                )->execute([$invoice['id'], $userId, $receiptNum, $amount, $method, $transId, $payslipPath]);

                setFlash('success', 'Payment submitted! The operator will verify it shortly and send your receipt via email.');
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}

// Check for existing pending payment
$existingPay = null;
if ($invoice) {
    $payStmt = $db->prepare("SELECT * FROM payments WHERE invoice_id=? ORDER BY paid_at DESC LIMIT 1");
    $payStmt->execute([$invoice['id']]);
    $existingPay = $payStmt->fetch();
}

require_once '../includes/header.php';
?>

<div style="max-width:600px">
  <div class="page-header"><h2>Upload Payment Proof</h2><p>Submit your payment receipt or transaction ID for verification</p></div>

  <?php if (!$invoice): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon">🧾</div>
      <h4>No Unpaid Invoice</h4>
      <p>You don't have any outstanding invoice. Check your application status.</p>
      <a href="dashboard.php" class="btn btn-primary mt-16">Go to Dashboard</a>
    </div>
  </div>
  <?php elseif ($existingPay && $existingPay['status'] === 'verified'): ?>
  <div class="alert alert-success">✅ Your payment has already been verified. <a href="receipt.php">View Receipt →</a></div>
  <?php elseif ($existingPay && $existingPay['status'] === 'pending'): ?>
  <div class="alert alert-warning">
    ⏳ Your payment is under review. Please wait for the operator to verify it.<br>
    <small>Submitted: <?= date('d M Y, h:i A', strtotime($existingPay['paid_at'])) ?></small>
  </div>
  <?php else: ?>

  <?php if ($error): ?>
  <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Invoice Summary -->
  <div style="background:var(--navy-dark);color:white;border-radius:10px;padding:20px 24px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center">
    <div>
      <div style="font-size:11px;opacity:0.6;text-transform:uppercase;letter-spacing:1px">Amount Due</div>
      <div style="font-size:30px;font-weight:900">MWK <?= number_format($invoice['amount'], 2) ?></div>
      <div style="font-size:12px;opacity:0.7"><?= htmlspecialchars($invoice['invoice_number']) ?></div>
    </div>
    <div style="text-align:right">
      <div style="font-size:11px;opacity:0.6">Due Date</div>
      <div style="font-weight:700;color:<?= strtotime($invoice['due_date']) < time() ? '#fca5a5' : '#86efac' ?>"><?= date('d M Y', strtotime($invoice['due_date'])) ?></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>💳 Payment Details</h3></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label>Payment Method <span class="req">*</span></label>
          <select name="payment_method" required>
            <option value="">-- Select Method --</option>
            <option value="bank" <?= ($_POST['payment_method']??'')==='bank'?'selected':'' ?>>Bank Transfer</option>
            <option value="mobile_money" <?= ($_POST['payment_method']??'')==='mobile_money'?'selected':'' ?>>Mobile Money (Airtel/TNM)</option>
            <option value="cash" <?= ($_POST['payment_method']??'')==='cash'?'selected':'' ?>>Cash at Office</option>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Amount Paid (MWK) <span class="req">*</span></label>
            <input type="number" name="amount_paid" step="0.01"
                   value="<?= $_POST['amount_paid'] ?? $invoice['amount'] ?>"
                   placeholder="<?= $invoice['amount'] ?>" required min="1">
          </div>
          <div class="form-group">
            <label>Transaction ID / Reference</label>
            <input type="text" name="transaction_id" value="<?= htmlspecialchars($_POST['transaction_id'] ?? '') ?>"
                   placeholder="e.g. TXN123456789">
            <div class="form-hint">Mobile money confirmation number or bank reference</div>
          </div>
        </div>

        <div class="form-group">
          <label>Upload Payslip / Screenshot <span class="req">*</span></label>
          <input type="file" name="payslip" accept=".jpg,.jpeg,.png,.pdf" required>
          <div class="form-hint">Upload a screenshot of the payment confirmation or bank payslip. Max 5MB. JPG, PNG or PDF.</div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Submit payment for verification?')">
          📤 Submit Payment →
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
