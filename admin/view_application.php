<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();
$pageTitle = 'View Application';

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT a.*, u.email FROM applications a JOIN users u ON a.student_id = u.id WHERE a.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result ? $result->fetch_assoc() : null;

if (!$app) {
    setFlash('error', 'Application not found.');
    header('Location: applications.php');
    exit;
}

$alloc = $db->prepare("SELECT * FROM allocations WHERE application_id = ?");
$alloc->bind_param('i', $id);
$alloc->execute();
$allocResult = $alloc->get_result();
$allocation = $allocResult ? $allocResult->fetch_assoc() : null;

$inv = $db->prepare("SELECT * FROM invoices WHERE student_id = ?");
$inv->bind_param('i', $app['student_id']);
$inv->execute();
$invResult = $inv->get_result();
$invoice = $invResult ? $invResult->fetch_assoc() : null;

$pay = null;
if ($invoice) {
    $payStmt = $db->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_at DESC LIMIT 1");
    $payStmt->bind_param('i', $invoice['id']);
    $payStmt->execute();
    $payResult = $payStmt->get_result();
    $pay = $payResult ? $payResult->fetch_assoc() : null;
}

require_once '../includes/header.php';
?>

<div style="max-width:750px">
  <div class="page-header flex-between">
    <h2>Application Details</h2>
    <a href="applications.php" class="btn btn-outline">← Back</a>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <h3>Student Info</h3>
      <span class="badge badge-<?= ['pending'=>'warning','allocated'=>'success','not_allocated'=>'danger'][$app['status']]??'gray' ?>">
        <?= str_replace('_',' ',ucfirst($app['status'])) ?>
      </span>
    </div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div><label class="text-muted">Reg Number</label><div class="text-bold"><?= htmlspecialchars($app['reg_number']) ?></div></div>
      <div><label class="text-muted">Full Name</label><div><?= htmlspecialchars($app['full_name']) ?></div></div>
      <div><label class="text-muted">Gender</label><div><?= ucfirst($app['gender']) ?></div></div>
      <div><label class="text-muted">Year</label><div>Year <?= $app['year_of_study'] ?></div></div>
      <div><label class="text-muted">Email</label><div><?= htmlspecialchars($app['email']) ?></div></div>
      <div><label class="text-muted">Applied</label><div><?= date('d M Y', strtotime($app['applied_at'])) ?></div></div>
      <?php if ($app['special_needs']): ?>
      <div style="grid-column:1/-1">
        <label class="text-muted">Special Needs</label>
        <div><span class="badge badge-purple">♿ <?= htmlspecialchars($app['special_needs']) ?></span></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($allocation): ?>
  <div class="card" style="margin-bottom:16px;border:1px solid var(--green)">
    <div class="card-header" style="background:var(--green-light)"><h3 style="color:var(--green)">🏠 Allocation</h3></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div><label class="text-muted">Hall/Block</label><div class="text-bold"><?= htmlspecialchars($allocation['hall_or_block']) ?></div></div>
      <div><label class="text-muted">Room</label><div class="text-bold"><?= htmlspecialchars($allocation['room_number']) ?></div></div>
      <div><label class="text-muted">Floor</label><div><?= htmlspecialchars($allocation['floor_level']) ?></div></div>
      <div><label class="text-muted">Type</label><div><?= $allocation['is_manual'] ? '👤 Manual' : '🎲 Auto' ?></div></div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($invoice): ?>
  <div class="card" style="margin-bottom:16px">
    <div class="card-header flex-between">
      <h3>🧾 Invoice</h3>
      <a href="view_invoice.php?id=<?= $invoice['id'] ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
    </div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div><label class="text-muted">Invoice #</label><div><?= htmlspecialchars($invoice['invoice_number']) ?></div></div>
      <div><label class="text-muted">Amount</label><div>MWK <?= number_format($invoice['amount'],2) ?></div></div>
      <div><label class="text-muted">Due Date</label><div><?= date('d M Y',strtotime($invoice['due_date'])) ?></div></div>
      <div><label class="text-muted">Status</label>
        <div><span class="badge badge-<?= ['unpaid'=>'danger','paid'=>'success','partial'=>'warning'][$invoice['status']] ?>"><?= ucfirst($invoice['status']) ?></span></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($pay): ?>
  <div class="card">
    <div class="card-header flex-between">
      <h3>💳 Payment</h3>
      <?php if ($pay['status']==='verified'): ?>
      <a href="view_receipt.php?id=<?= $pay['id'] ?>" target="_blank" class="btn btn-sm btn-success">🧾 Receipt</a>
      <?php endif; ?>
    </div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div><label class="text-muted">Receipt #</label><div><?= htmlspecialchars($pay['receipt_number']) ?></div></div>
      <div><label class="text-muted">Amount Paid</label><div>MWK <?= number_format($pay['amount_paid'],2) ?></div></div>
      <div><label class="text-muted">Method</label><div><?= ucfirst(str_replace('_',' ',$pay['payment_method'])) ?></div></div>
      <div><label class="text-muted">Status</label>
        <div><span class="badge badge-<?= ['pending'=>'warning','verified'=>'success','rejected'=>'danger'][$pay['status']] ?>"><?= ucfirst($pay['status']) ?></span></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($app['status'] !== 'allocated'): ?>
  <div style="margin-top:16px">
    <a href="manual_allocate.php?id=<?= $app['id'] ?>" class="btn btn-primary">🏠 Manually Allocate Room</a>
  </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
