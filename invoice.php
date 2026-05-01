<?php
require_once '../includes/config.php';
requireLogin('student');
$db = getDB();

$pageTitle = 'My Invoice';
$userId = $_SESSION['user_id'];

$invStmt = $db->prepare("SELECT * FROM invoices WHERE student_id=? ORDER BY issued_at DESC LIMIT 1");
$invStmt->execute([$userId]);
$invoice = $invStmt->fetch();

require_once '../includes/header.php';
?>

<div style="max-width:700px">
  <div class="page-header"><h2>My Invoice</h2></div>

  <?php if (!$invoice): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon">🧾</div>
      <h4>No Invoice Yet</h4>
      <p>An invoice will be generated automatically when you submit a hostel application.</p>
      <a href="apply.php" class="btn btn-primary mt-16">Apply Now →</a>
    </div>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-header flex-between">
      <h3>Invoice Details</h3>
      <div class="gap-8">
        <a href="../operator/view_invoice.php?id=<?= $invoice['id'] ?>" target="_blank" class="btn btn-outline btn-sm">🧾 Full Invoice</a>
        <a href="../operator/view_invoice.php?id=<?= $invoice['id'] ?>" target="_blank" onclick="setTimeout(()=>{},500)" class="btn btn-primary btn-sm">🖨 Print</a>
      </div>
    </div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div><label class="text-muted">Invoice Number</label><div class="text-bold"><?= htmlspecialchars($invoice['invoice_number']) ?></div></div>
        <div><label class="text-muted">Status</label><div>
          <span class="badge badge-<?= ['unpaid'=>'danger','paid'=>'success','partial'=>'warning'][$invoice['status']] ?>"><?= ucfirst($invoice['status']) ?></span>
        </div></div>
        <div><label class="text-muted">Amount Due</label><div style="font-size:22px;font-weight:900;color:var(--navy)">MWK <?= number_format($invoice['amount'], 2) ?></div></div>
        <div><label class="text-muted">Due Date</label><div style="color:var(--red)"><?= date('d F Y', strtotime($invoice['due_date'])) ?></div></div>
        <div><label class="text-muted">Issued On</label><div><?= date('d M Y', strtotime($invoice['issued_at'])) ?></div></div>
        <div><label class="text-muted">Academic Year</label><div><?= date('Y') ?>/<?= date('Y')+1 ?></div></div>
      </div>

      <?php if ($invoice['status'] !== 'paid'): ?>
      <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:16px;margin-top:20px">
        <strong>⚡ Payment Instructions:</strong>
        <div style="margin-top:8px;font-size:13px;color:#444;line-height:1.8">
          <strong>Bank Transfer:</strong> National Bank of Malawi | Account: 1234567890 | Branch: Thyolo<br>
          <strong>Airtel Money:</strong> 0991 234 567<br>
          <strong>TNM Mpamba:</strong> 0881 234 567<br>
          <strong>Reference:</strong> <?= htmlspecialchars($_SESSION['reg_number']) ?>
        </div>
        <a href="payment.php" class="btn btn-primary mt-16">💳 Upload Payment Proof →</a>
      </div>
      <?php else: ?>
      <div class="alert alert-success" style="margin-top:20px;margin-bottom:0">✅ Payment confirmed! <a href="receipt.php">View your receipt →</a></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
