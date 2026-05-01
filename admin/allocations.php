<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();
$pageTitle = 'Room Allocations';
$pageSubtitle = 'All allocated students and their rooms';

$search = clean($_GET['search'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (al.reg_number LIKE ? OR a.full_name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}

$stmt = $db->prepare(
    "SELECT al.*, a.full_name, a.year_of_study, a.gender, a.special_needs,
            u.email,
            p.status as payment_status
     FROM allocations al
     JOIN applications a ON al.application_id = a.id
     JOIN users u ON al.student_id = u.id
     LEFT JOIN invoices i ON i.student_id = al.student_id
     LEFT JOIN payments p ON p.invoice_id = i.id AND p.status = 'verified'
     $where
     ORDER BY al.allocated_at DESC"
);
$stmt->execute($params);
$allocations = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header flex-between">
  <div><h2>Room Allocations</h2><p>Total: <?= count($allocations) ?> allocations</p></div>
  <a href="reports.php" class="btn btn-primary">📈 Full Report</a>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="padding:14px">
    <form method="GET" style="display:flex;gap:12px">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Reg # or Name..." style="max-width:300px">
      <button type="submit" class="btn btn-primary">Search</button>
      <a href="allocations.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Reg Number</th>
          <th>Full Name</th>
          <th>Hall / Block</th>
          <th>Room</th>
          <th>Floor</th>
          <th>Year</th>
          <th>Gender</th>
          <th>Payment</th>
          <th>Allocated</th>
          <th>Type</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($allocations)): ?>
        <tr><td colspan="11" style="text-align:center;padding:40px;color:#888">No allocations yet. Run batch allocation from dashboard.</td></tr>
        <?php endif; ?>
        <?php foreach ($allocations as $i => $al): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($al['reg_number']) ?></strong></td>
          <td><?= htmlspecialchars($al['full_name']) ?></td>
          <td><?= htmlspecialchars($al['hall_or_block']) ?></td>
          <td><strong><?= htmlspecialchars($al['room_number']) ?></strong></td>
          <td><?= htmlspecialchars($al['floor_level']) ?></td>
          <td>Year <?= $al['year_of_study'] ?></td>
          <td><?= ucfirst($al['gender']) ?></td>
          <td>
            <?php if ($al['payment_status'] === 'verified'): ?>
            <span class="badge badge-success">✅ Paid</span>
            <?php else: ?>
            <span class="badge badge-warning">⏳ Unpaid</span>
            <?php endif; ?>
          </td>
          <td><?= date('d M Y', strtotime($al['allocated_at'])) ?></td>
          <td>
            <?php if ($al['is_manual']): ?>
            <span class="badge badge-purple">👤 Manual</span>
            <?php else: ?>
            <span class="badge badge-navy">🎲 Auto</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
