<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();
$pageTitle = 'Reports';
$pageSubtitle = 'System-wide reports and analytics';

// Stats
$totalApps = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$allocated = $db->query("SELECT COUNT(*) FROM applications WHERE status='allocated'")->fetchColumn();
$notAllocated = $db->query("SELECT COUNT(*) FROM applications WHERE status='not_allocated'")->fetchColumn();
$pending = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$paid = $db->query("SELECT COUNT(*) FROM invoices WHERE status='paid'")->fetchColumn();
$unpaid = $db->query("SELECT COUNT(*) FROM invoices WHERE status='unpaid'")->fetchColumn();
$totalRev = $db->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE status='verified'")->fetchColumn();
$male = $db->query("SELECT COUNT(*) FROM applications WHERE gender='male'")->fetchColumn();
$female = $db->query("SELECT COUNT(*) FROM applications WHERE gender='female'")->fetchColumn();

// Year breakdown
$byYear = $db->query(
    "SELECT year_of_study, COUNT(*) as cnt, 
     SUM(CASE WHEN status='allocated' THEN 1 ELSE 0 END) as alloc
     FROM applications GROUP BY year_of_study ORDER BY year_of_study"
)->fetchAll();

// Hall occupancy
$hallOcc = $db->query(
    "SELECT h.hall_name, h.hall_type,
     COUNT(r.id) as total_rooms,
     SUM(r.occupied) as total_occupied,
     SUM(r.capacity) as total_capacity
     FROM halls h LEFT JOIN rooms r ON r.hall_id = h.id
     GROUP BY h.id ORDER BY h.id"
)->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header flex-between">
  <div><h2>Reports & Analytics</h2><p>Academic Year <?= date('Y') ?>/<?= date('Y')+1 ?></p></div>
  <div class="gap-8">
    <a href="export_report.php" class="btn btn-primary">📄 Export PDF Report</a>
    <button onclick="window.print()" class="btn btn-outline">🖨️ Print</button>
  </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon" style="background:#dbeafe">📋</div>
    <div class="stat-info"><div class="value"><?= $totalApps ?></div><div class="label">Total Applications</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#d1fae5">✅</div>
    <div class="stat-info"><div class="value"><?= $allocated ?></div><div class="label">Allocated</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fee2e2">❌</div>
    <div class="stat-info"><div class="value"><?= $notAllocated ?></div><div class="label">Not Allocated</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7">⏳</div>
    <div class="stat-info"><div class="value"><?= $pending ?></div><div class="label">Pending</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#d1fae5">💰</div>
    <div class="stat-info"><div class="value"><?= $paid ?></div><div class="label">Paid Students</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fee2e2">💸</div>
    <div class="stat-info"><div class="value"><?= $unpaid ?></div><div class="label">Unpaid Students</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#d1fae5">💵</div>
    <div class="stat-info"><div class="value">MWK <?= number_format($totalRev) ?></div><div class="label">Total Revenue</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dbeafe">📊</div>
    <div class="stat-info"><div class="value"><?= $totalApps > 0 ? round($allocated/$totalApps*100) : 0 ?>%</div><div class="label">Allocation Rate</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <!-- Gender breakdown -->
  <div class="card">
    <div class="card-header"><h3>Gender Breakdown</h3></div>
    <div class="card-body">
      <div style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span>Male</span><strong><?= $male ?></strong>
        </div>
        <div class="progress"><div class="progress-bar" style="width:<?= $totalApps > 0 ? ($male/$totalApps*100) : 0 ?>%"></div></div>
      </div>
      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span>Female</span><strong><?= $female ?></strong>
        </div>
        <div class="progress"><div class="progress-bar" style="width:<?= $totalApps > 0 ? ($female/$totalApps*100) : 0 ?>%;background:var(--purple)"></div></div>
      </div>
    </div>
  </div>

  <!-- Year breakdown -->
  <div class="card">
    <div class="card-header"><h3>By Year of Study</h3></div>
    <div class="card-body">
      <?php foreach ($byYear as $yr): ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
          <span>Year <?= $yr['year_of_study'] ?></span>
          <span><strong><?= $yr['alloc'] ?></strong> / <?= $yr['cnt'] ?> allocated</span>
        </div>
        <div class="progress">
          <div class="progress-bar" style="width:<?= $yr['cnt'] > 0 ? ($yr['alloc']/$yr['cnt']*100) : 0 ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Hall Occupancy -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><h3>🏨 Hall Occupancy</h3></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Hall</th><th>Type</th><th>Total Rooms</th><th>Capacity</th><th>Occupied</th><th>Occupancy Rate</th></tr>
      </thead>
      <tbody>
        <?php foreach ($hallOcc as $h): ?>
        <?php $pct = $h['total_capacity'] > 0 ? round($h['total_occupied'] / $h['total_capacity'] * 100) : 0; ?>
        <tr>
          <td><strong><?= htmlspecialchars($h['hall_name']) ?></strong></td>
          <td><?= ucfirst(str_replace('_', ' ', $h['hall_type'])) ?></td>
          <td><?= $h['total_rooms'] ?></td>
          <td><?= $h['total_capacity'] ?></td>
          <td><?= $h['total_occupied'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress" style="flex:1;height:6px">
                <div class="progress-bar" style="width:<?= $pct ?>%"></div>
              </div>
              <span style="font-size:12px;font-weight:700;min-width:35px"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Paid vs Unpaid table -->
<div class="card">
  <div class="card-header">
    <h3>💰 Payment Status Report</h3>
    <a href="payments.php" class="btn btn-sm btn-outline">View All Payments</a>
  </div>
  <div class="table-wrap">
    <?php
    $paymentReport = $db->query(
        "SELECT a.reg_number, a.full_name, a.gender, a.year_of_study,
         i.invoice_number, i.amount, i.status as inv_status,
         p.receipt_number, p.amount_paid, p.status as pay_status,
         al.hall_or_block, al.room_number
         FROM applications a
         LEFT JOIN invoices i ON i.student_id = a.student_id
         LEFT JOIN payments p ON p.invoice_id = i.id AND p.status='verified'
         LEFT JOIN allocations al ON al.student_id = a.student_id
         WHERE a.status = 'allocated'
         ORDER BY a.full_name"
    )->fetchAll();
    ?>
    <table>
      <thead>
        <tr><th>Reg #</th><th>Name</th><th>Year</th><th>Room</th><th>Invoice #</th><th>Amount</th><th>Payment</th><th>Receipt #</th></tr>
      </thead>
      <tbody>
        <?php foreach ($paymentReport as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['reg_number']) ?></strong></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td>Year <?= $r['year_of_study'] ?></td>
          <td><?= htmlspecialchars($r['hall_or_block'] . ' - ' . $r['room_number']) ?></td>
          <td><small><?= htmlspecialchars($r['invoice_number'] ?? 'N/A') ?></small></td>
          <td>MWK <?= $r['amount'] ? number_format($r['amount']) : '—' ?></td>
          <td>
            <?php if ($r['pay_status'] === 'verified'): ?>
            <span class="badge badge-success">✅ Paid</span>
            <?php else: ?>
            <span class="badge badge-warning">⏳ Unpaid</span>
            <?php endif; ?>
          </td>
          <td><small><?= htmlspecialchars($r['receipt_number'] ?? '—') ?></small></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
