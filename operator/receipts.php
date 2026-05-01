<?php
require_once '../includes/config.php';
requireLogin(['admin','operator']);
$db = getDB();
$pageTitle = 'Receipts';
$pageSubtitle = 'All verified payment receipts';

$receipts = $db->query(
    "SELECT p.*, i.invoice_number, u.full_name, u.reg_number, al.hall_or_block, al.room_number
     FROM payments p
     JOIN invoices i ON p.invoice_id=i.id
     JOIN users u ON p.student_id=u.id
     LEFT JOIN allocations al ON al.student_id=p.student_id
     WHERE p.status='verified'
     ORDER BY p.paid_at DESC"
)->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header"><h2>Receipts</h2><p><?= count($receipts) ?> receipts issued</p></div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Receipt #</th><th>Reg #</th><th>Student</th><th>Room</th><th>Amount Paid</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($receipts)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#888">No receipts yet</td></tr>
        <?php endif; ?>
        <?php foreach ($receipts as $r): ?>
        <tr>
          <td><small><strong><?= htmlspecialchars($r['receipt_number']) ?></strong></small></td>
          <td><?= htmlspecialchars($r['reg_number']) ?></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td><?= htmlspecialchars(($r['hall_or_block'] ?? '') . ' - ' . ($r['room_number'] ?? 'N/A')) ?></td>
          <td>MWK <?= number_format($r['amount_paid'], 2) ?></td>
          <td><?= date('d M Y', strtotime($r['paid_at'])) ?></td>
          <td class="gap-8">
            <a href="../admin/view_receipt.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-primary">🧾 View</a>
            <a href="../admin/view_receipt.php?id=<?= $r['id'] ?>" target="_blank" onclick="setTimeout(()=>window.frames[0]?.print(),500)" class="btn btn-sm btn-outline">🖨 Print</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>



