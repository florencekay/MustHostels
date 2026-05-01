<?php
require_once '../includes/config.php';
requireLogin('operator');
$db = getDB();
$pageTitle = 'Operator Dashboard';
$pageSubtitle = 'Manage invoices, payments, receipts and inquiries';

$pendingPayments = $db->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn();
$openInquiries = $db->query("SELECT COUNT(*) FROM inquiries WHERE status='open'")->fetchColumn();
$totalInvoices = $db->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
$verifiedPayments = $db->query("SELECT COUNT(*) FROM payments WHERE status='verified'")->fetchColumn();

$recentPayments = $db->query(
    "SELECT p.*, u.full_name, u.reg_number FROM payments p
     JOIN users u ON p.student_id=u.id
     ORDER BY p.paid_at DESC LIMIT 6"
)->fetchAll();

require_once '../includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7">⏳</div>
    <div class="stat-info"><div class="value"><?= $pendingPayments ?></div><div class="label">Pending Payments</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe">📬</div>
    <div class="stat-info"><div class="value"><?= $openInquiries ?></div><div class="label">Open Inquiries</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dbeafe">🧾</div>
    <div class="stat-info"><div class="value"><?= $totalInvoices ?></div><div class="label">Total Invoices</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#d1fae5">✅</div>
    <div class="stat-info"><div class="value"><?= $verifiedPayments ?></div><div class="label">Verified Payments</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
  <div class="card">
    <div class="card-header"><h3>Recent Payment Submissions</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Student</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php if (empty($recentPayments)): ?>
          <tr><td colspan="4" style="text-align:center;padding:20px;color:#888">No payments yet</td></tr>
          <?php endif; ?>
          <?php foreach ($recentPayments as $p): ?>
          <tr>
            <td><strong><?= htmlspecialchars($p['reg_number']) ?></strong><br><small><?= htmlspecialchars($p['full_name']) ?></small></td>
            <td>MWK <?= number_format($p['amount_paid']) ?></td>
            <td>
              <?php $b=['pending'=>'warning','verified'=>'success','rejected'=>'danger'][$p['status']]??'gray'; ?>
              <span class="badge badge-<?= $b ?>"><?= ucfirst($p['status']) ?></span>
            </td>
            <td><a href="../admin/payments.php" class="btn btn-sm btn-outline">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>⚡ Quick Actions</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
      <a href="payments.php" class="btn btn-primary">💰 Review Payments</a>
      <a href="inquiries.php" class="btn btn-outline">📬 Handle Inquiries (<?= $openInquiries ?> open)</a>
      <a href="invoices.php" class="btn btn-outline">🧾 Manage Invoices</a>
      <a href="receipts.php" class="btn btn-outline">📄 View Receipts</a>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
