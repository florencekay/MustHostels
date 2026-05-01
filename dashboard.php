<?php
require_once '../includes/config.php';
requireLogin('student');
$db = getDB();

$pageTitle = 'My Dashboard';
$pageSubtitle = 'Welcome to MUST Hostel Allocation System';

$userId = $_SESSION['user_id'];

// Get application
$appStmt = $db->prepare("SELECT * FROM applications WHERE student_id=? ORDER BY applied_at DESC LIMIT 1");
$appStmt->execute([$userId]);
$application = $appStmt->fetch();

// Get allocation
$allocation = null;
if ($application) {
    $alStmt = $db->prepare("SELECT * FROM allocations WHERE application_id=? LIMIT 1");
    $alStmt->execute([$application['id']]);
    $allocation = $alStmt->fetch();
}

// Get invoice
$invStmt = $db->prepare("SELECT * FROM invoices WHERE student_id=? ORDER BY issued_at DESC LIMIT 1");
$invStmt->execute([$userId]);
$invoice = $invStmt->fetch();

// Get payment
$payment = null;
if ($invoice) {
    $payStmt = $db->prepare("SELECT * FROM payments WHERE invoice_id=? ORDER BY paid_at DESC LIMIT 1");
    $payStmt->execute([$invoice['id']]);
    $payment = $payStmt->fetch();
}

require_once '../includes/header.php';
?>

<!-- Welcome Banner -->
<div style="background:linear-gradient(135deg,var(--navy-dark),var(--navy-light));border-radius:12px;padding:28px 32px;color:white;margin-bottom:24px;position:relative;overflow:hidden">
  <div style="position:absolute;top:-30px;right:-30px;width:150px;height:150px;background:rgba(201,164,60,0.12);border-radius:50%"></div>
  <div style="font-size:13px;opacity:0.7;margin-bottom:4px">Welcome back,</div>
  <div style="font-size:26px;font-weight:900;margin-bottom:6px"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
  <div style="font-size:13px;opacity:0.7">📚 <?= htmlspecialchars($_SESSION['reg_number']) ?> &nbsp;|&nbsp; 📧 <?= genEmail($_SESSION['reg_number']) ?></div>
  <?php if (!$application): ?>
  <a href="apply.php" style="display:inline-flex;align-items:center;gap:8px;margin-top:20px;background:var(--gold);color:var(--navy-dark);padding:12px 24px;border-radius:8px;font-weight:800;text-decoration:none;font-size:14px">
    ✍️ Apply for Hostel Room →
  </a>
  <?php endif; ?>
</div>

<!-- Status Cards -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon" style="background:#dbeafe">📋</div>
    <div class="stat-info">
      <div class="value" style="font-size:18px">
        <?php if ($application): ?>
        <span class="badge badge-<?= ['pending'=>'warning','allocated'=>'success','not_allocated'=>'danger'][$application['status']]??'gray' ?>"><?= str_replace('_',' ',ucfirst($application['status'])) ?></span>
        <?php else: ?><span class="badge badge-gray">Not Applied</span><?php endif; ?>
      </div>
      <div class="label">Application Status</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#d1fae5">🏠</div>
    <div class="stat-info">
      <div class="value" style="font-size:16px">
        <?= $allocation ? htmlspecialchars($allocation['hall_or_block']) : '—' ?>
      </div>
      <div class="label">Room Allocation</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7">🧾</div>
    <div class="stat-info">
      <div class="value" style="font-size:16px">
        <?php if ($invoice): ?>
        <span class="badge badge-<?= ['unpaid'=>'danger','paid'=>'success','partial'=>'warning'][$invoice['status']] ?>">
          <?= ucfirst($invoice['status']) ?>
        </span>
        <?php else: ?>—<?php endif; ?>
      </div>
      <div class="label">Invoice Status</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe">💳</div>
    <div class="stat-info">
      <div class="value" style="font-size:16px">
        <?php if ($payment): ?>
        <span class="badge badge-<?= ['pending'=>'warning','verified'=>'success','rejected'=>'danger'][$payment['status']] ?>">
          <?= ucfirst($payment['status']) ?>
        </span>
        <?php else: ?>—<?php endif; ?>
      </div>
      <div class="label">Payment Status</div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
  <!-- Application Details -->
  <div class="card">
    <div class="card-header"><h3>📋 My Application</h3></div>
    <div class="card-body">
      <?php if ($application): ?>
      <table style="width:100%">
        <tr><td class="text-muted" style="padding:6px 0">Reg Number</td><td><strong><?= htmlspecialchars($application['reg_number']) ?></strong></td></tr>
        <tr><td class="text-muted" style="padding:6px 0">Full Name</td><td><?= htmlspecialchars($application['full_name']) ?></td></tr>
        <tr><td class="text-muted" style="padding:6px 0">Year of Study</td><td>Year <?= $application['year_of_study'] ?></td></tr>
        <tr><td class="text-muted" style="padding:6px 0">Gender</td><td><?= ucfirst($application['gender']) ?></td></tr>
        <tr><td class="text-muted" style="padding:6px 0">Applied On</td><td><?= date('d M Y', strtotime($application['applied_at'])) ?></td></tr>
        <tr><td class="text-muted" style="padding:6px 0">Status</td><td>
          <span class="badge badge-<?= ['pending'=>'warning','allocated'=>'success','not_allocated'=>'danger'][$application['status']]??'gray' ?>"><?= str_replace('_',' ',ucfirst($application['status'])) ?></span>
        </td></tr>
      </table>
      <?php if ($allocation): ?>
      <div class="alert alert-success" style="margin-top:16px;margin-bottom:0">
        🎉 <strong>Room Assigned:</strong> <?= htmlspecialchars($allocation['hall_or_block']) ?>, Room <?= htmlspecialchars($allocation['room_number']) ?> — <?= htmlspecialchars($allocation['floor_level']) ?>
      </div>
      <?php endif; ?>
      <?php if ($application['status'] === 'not_allocated'): ?>
      <div class="alert alert-danger" style="margin-top:16px;margin-bottom:0">
        ❌ You were not allocated a room this semester due to limited capacity. Please contact the hostel office.
      </div>
      <?php endif; ?>
      <?php else: ?>
      <div class="empty-state" style="padding:30px">
        <div class="empty-icon">📋</div>
        <h4>No Application Yet</h4>
        <p>Apply for hostel accommodation to get started.</p>
        <a href="apply.php" class="btn btn-primary mt-16">Apply Now →</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Invoice & Payment -->
  <div class="card">
    <div class="card-header"><h3>💰 Invoice & Payment</h3></div>
    <div class="card-body">
      <?php if ($invoice): ?>
      <table style="width:100%">
        <tr><td class="text-muted" style="padding:6px 0">Invoice #</td><td><small><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></small></td></tr>
        <tr><td class="text-muted" style="padding:6px 0">Amount Due</td><td><strong>MWK <?= number_format($invoice['amount'], 2) ?></strong></td></tr>
        <tr><td class="text-muted" style="padding:6px 0">Due Date</td><td><?= date('d M Y', strtotime($invoice['due_date'])) ?></td></tr>
        <tr><td class="text-muted" style="padding:6px 0">Status</td><td>
          <span class="badge badge-<?= ['unpaid'=>'danger','paid'=>'success','partial'=>'warning'][$invoice['status']] ?>"><?= ucfirst($invoice['status']) ?></span>
        </td></tr>
      </table>
      <div style="display:flex;gap:8px;margin-top:16px">
        <a href="../operator/view_invoice.php?id=<?= $invoice['id'] ?>" target="_blank" class="btn btn-outline btn-sm">🧾 View Invoice</a>
        <?php if ($invoice['status'] !== 'paid'): ?>
        <a href="payment.php" class="btn btn-primary btn-sm">💳 Upload Payment</a>
        <?php endif; ?>
        <?php if ($payment && $payment['status'] === 'verified'): ?>
        <a href="receipt.php" class="btn btn-success btn-sm">📄 View Receipt</a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="empty-state" style="padding:30px">
        <div class="empty-icon">🧾</div>
        <h4>No Invoice Yet</h4>
        <p>An invoice will be generated after you submit an application.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
