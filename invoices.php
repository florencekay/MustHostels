<?php
require_once '../includes/config.php';
requireLogin(['admin','operator']);
$db = getDB();
$pageTitle = 'Invoices';
$pageSubtitle = 'All student accommodation invoices';

$search = clean($_GET['search'] ?? '');
$statusF = clean($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND (u.reg_number LIKE ? OR u.full_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($statusF) { $where .= " AND i.status=?"; $params[] = $statusF; }

$stmt = $db->prepare(
    "SELECT i.*, u.full_name, u.reg_number, u.email FROM invoices i JOIN users u ON i.student_id=u.id $where ORDER BY i.issued_at DESC"
);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header flex-between">
  <div><h2>Invoices</h2><p><?= count($invoices) ?> invoices found</p></div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="padding:14px">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Reg # or name..." style="max-width:240px">
      <select name="status" style="max-width:150px">
        <option value="">All Status</option>
        <option value="unpaid" <?= $statusF==='unpaid'?'selected':'' ?>>Unpaid</option>
        <option value="paid" <?= $statusF==='paid'?'selected':'' ?>>Paid</option>
        <option value="partial" <?= $statusF==='partial'?'selected':'' ?>>Partial</option>
      </select>
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="invoices.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Invoice #</th><th>Reg #</th><th>Student</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#888">No invoices found</td></tr>
        <?php endif; ?>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td><small><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></small></td>
          <td><?= htmlspecialchars($inv['reg_number']) ?></td>
          <td><?= htmlspecialchars($inv['full_name']) ?></td>
          <td>MWK <?= number_format($inv['amount'], 2) ?></td>
          <td><?= date('d M Y', strtotime($inv['due_date'])) ?></td>
          <td>
            <?php $b=['unpaid'=>'danger','paid'=>'success','partial'=>'warning'][$inv['status']]??'gray'; ?>
            <span class="badge badge-<?= $b ?>"><?= ucfirst($inv['status']) ?></span>
          </td>
          <td>
            <a href="view_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="btn btn-sm btn-outline">🧾 View</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
