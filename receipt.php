<?php
require_once '../includes/config.php';
requireLogin('student');
$db = getDB();

$pageTitle = 'My Receipt';
$userId = $_SESSION['user_id'];

// Get verified payment
$stmt = $db->prepare(
    "SELECT p.*, i.invoice_number, i.amount, i.due_date
     FROM payments p
     JOIN invoices i ON p.invoice_id=i.id
     WHERE p.student_id=? AND p.status='verified'
     ORDER BY p.paid_at DESC LIMIT 1"
);
$stmt->execute([$userId]);
$payment = $stmt->fetch();

require_once '../includes/header.php';
?>

<div style="max-width:700px">
  <div class="page-header"><h2>My Receipt</h2></div>

  <?php if (!$payment): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon">🧾</div>
      <h4>No Receipt Available</h4>
      <p>Your receipt will appear here once the operator verifies your payment.</p>
      <?php
      $pendingPay = $db->prepare("SELECT * FROM payments WHERE student_id=? AND status='pending' LIMIT 1");
      $pendingPay->execute([$userId]);
      if ($pendingPay->fetch()):
      ?><div class="alert alert-warning" style="margin-top:16px">⏳ Your payment is currently under review.</div>
      <?php else: ?>
      <a href="payment.php" class="btn btn-primary mt-16">Upload Payment →</a>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-header flex-between">
      <h3>🧾 Payment Receipt</h3>
      <a href="../admin/view_receipt.php?id=<?= $payment['id'] ?>" target="_blank" class="btn btn-primary btn-sm">🖨 Print Receipt</a>
    </div>
    <div class="card-body">
      <?php
      $alloc = $db->prepare("SELECT * FROM allocations WHERE student_id=? LIMIT 1");
      $alloc->execute([$userId]);
      $allocation = $alloc->fetch();
      ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div><label class="text-muted">Receipt Number</label><div class="text-bold"><?= htmlspecialchars($payment['receipt_number']) ?></div></div>
        <div><label class="text-muted">Invoice Reference</label><div><?= htmlspecialchars($payment['invoice_number']) ?></div></div>
        <div><label class="text-muted">Amount Paid</label><div style="font-size:24px;font-weight:900;color:var(--green)">MWK <?= number_format($payment['amount_paid'], 2) ?></div></div>
        <div><label class="text-muted">Payment Method</label><div><?= ucfirst(str_replace('_',' ',$payment['payment_method'])) ?></div></div>
        <div><label class="text-muted">Transaction ID</label><div><?= htmlspecialchars($payment['transaction_id'] ?: 'N/A') ?></div></div>
        <div><label class="text-muted">Date Paid</label><div><?= date('d M Y, h:i A', strtotime($payment['paid_at'])) ?></div></div>
        <?php if ($allocation): ?>
        <div><label class="text-muted">Room Assigned</label><div class="text-bold"><?= htmlspecialchars($allocation['hall_or_block'] . ' - Room ' . $allocation['room_number']) ?></div></div>
        <div><label class="text-muted">Floor</label><div><?= htmlspecialchars($allocation['floor_level']) ?></div></div>
        <?php endif; ?>
      </div>

      <div class="alert alert-success" style="margin-top:20px;margin-bottom:0">
        ✅ <strong>Payment Verified!</strong> Your accommodation is confirmed for the <?= date('Y') ?>/<?= date('Y')+1 ?> academic year.
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
