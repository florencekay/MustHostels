<?php
require_once '../includes/config.php';
requireLogin(['admin','operator']);
$db = getDB();
$pageTitle = 'Payments';

// Handle verify/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payId = (int)$_POST['payment_id'];
    $action = clean($_POST['action']);

    if ($action === 'verify') {
        $db->prepare("UPDATE payments SET status='verified', verified_by=? WHERE id=?")
           ->execute([$_SESSION['user_id'], $payId]);
        $db->prepare("UPDATE invoices SET status='paid' WHERE id=(SELECT invoice_id FROM payments WHERE id=?)")
           ->execute([$payId]);

        // Get payment + student info to generate receipt
        $pay = $db->prepare("SELECT p.*, i.*, u.full_name, u.email, u.reg_number FROM payments p JOIN invoices i ON p.invoice_id=i.id JOIN users u ON p.student_id=u.id WHERE p.id=?");
        $pay->execute([$payId]);
        $payData = $pay->fetch();

        if ($payData) {
            require_once '../includes/email.php';
            $alloc = $db->prepare("SELECT * FROM allocations WHERE student_id=? LIMIT 1");
            $alloc->execute([$payData['student_id']]);
            $allocData = $alloc->fetch();
            $roomInfo = $allocData ? $allocData['hall_or_block'] . ' - ' . $allocData['room_number'] : 'Pending';
            sendReceiptEmail($payData['email'], $payData['full_name'], $payData['receipt_number'], $payData['amount_paid'], $roomInfo);
        }
        setFlash('success', 'Payment verified and receipt sent to student.');
    } elseif ($action === 'reject') {
        $db->prepare("UPDATE payments SET status='rejected' WHERE id=?")->execute([$payId]);
        setFlash('error', 'Payment rejected.');
    }

    header('Location: payments.php');
    exit;
}

$payments = $db->query(
    "SELECT p.*, i.invoice_number, i.amount as invoice_amount,
     u.full_name, u.reg_number, u.email,
     al.hall_or_block, al.room_number
     FROM payments p
     JOIN invoices i ON p.invoice_id = i.id
     JOIN users u ON p.student_id = u.id
     LEFT JOIN allocations al ON al.student_id = p.student_id
     ORDER BY p.paid_at DESC"
)->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header"><h2>Payment Management</h2><p>Verify uploaded payment slips and generate receipts</p></div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Reg #</th><th>Student</th><th>Invoice</th><th>Amount Paid</th>
          <th>Method</th><th>Trans ID</th><th>Receipt #</th><th>Submitted</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:#888">No payments submitted yet</td></tr>
        <?php endif; ?>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><strong><?= htmlspecialchars($p['reg_number']) ?></strong></td>
          <td><?= htmlspecialchars($p['full_name']) ?></td>
          <td><small><?= htmlspecialchars($p['invoice_number']) ?></small></td>
          <td>MWK <?= number_format($p['amount_paid'], 2) ?></td>
          <td><?= ucfirst(str_replace('_',' ',$p['payment_method'])) ?></td>
          <td><small><?= htmlspecialchars($p['transaction_id'] ?: '—') ?></small></td>
          <td><small><?= htmlspecialchars($p['receipt_number']) ?></small></td>
          <td><?= date('d M Y', strtotime($p['paid_at'])) ?></td>
          <td>
            <?php $b = ['pending'=>'warning','verified'=>'success','rejected'=>'danger'][$p['status']] ?? 'gray'; ?>
            <span class="badge badge-<?= $b ?>"><?= ucfirst($p['status']) ?></span>
          </td>
          <td>
            <div class="gap-8">
              <?php if ($p['payslip_path']): ?>
              <a href="<?= SITE_URL . '/' . $p['payslip_path'] ?>" target="_blank" class="btn btn-sm btn-outline">📎 Slip</a>
              <?php endif; ?>
              <?php if ($p['status'] === 'pending'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="verify">
                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Verify this payment?')">✅ Verify</button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this payment?')">❌ Reject</button>
              </form>
              <?php endif; ?>
              <?php if ($p['status'] === 'verified'): ?>
              <a href="view_receipt.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-primary">🧾 Receipt</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
