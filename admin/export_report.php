<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

$totalApps     = $db->query("SELECT COUNT(*) FROM applications");
$totalApps     = $totalApps ? $totalApps->fetch_row()[0] : 0;
$allocated     = $db->query("SELECT COUNT(*) FROM applications WHERE status='allocated'");
$allocated     = $allocated ? $allocated->fetch_row()[0] : 0;
$notAllocated  = $db->query("SELECT COUNT(*) FROM applications WHERE status='not_allocated'");
$notAllocated  = $notAllocated ? $notAllocated->fetch_row()[0] : 0;
$pending       = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'");
$pending       = $pending ? $pending->fetch_row()[0] : 0;
$paid          = $db->query("SELECT COUNT(*) FROM invoices WHERE status='paid'");
$paid          = $paid ? $paid->fetch_row()[0] : 0;
$unpaid        = $db->query("SELECT COUNT(*) FROM invoices WHERE status='unpaid'");
$unpaid        = $unpaid ? $unpaid->fetch_row()[0] : 0;
$totalRev      = $db->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE status='verified'");
$totalRev      = $totalRev ? $totalRev->fetch_row()[0] : 0;

$allocRes = $db->query(
    "SELECT al.reg_number, a.full_name, a.gender, a.year_of_study,
     al.hall_or_block, al.room_number, al.floor_level,
     COALESCE(i.status,'unpaid') as pay_status
     FROM allocations al
     JOIN applications a ON al.application_id=a.id
     LEFT JOIN invoices i ON i.student_id=al.student_id
     ORDER BY a.full_name"
);
$allocations = $allocRes ? $allocRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>MUST Hostel Report - <?= date('Y') ?></title>
<style>
  @media print { .no-print { display:none; } body { margin: 0; } }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; color: #333; }
  .report { max-width: 900px; margin: 0 auto; background: white; padding: 50px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
  .header { text-align: center; border-bottom: 3px solid #0f2d52; padding-bottom: 24px; margin-bottom: 30px; }
  .logo { font-size: 36px; font-weight: 900; color: #0f2d52; letter-spacing: 3px; }
  .subtitle { color: #666; font-size: 13px; margin-top: 4px; }
  .section-title { font-size: 16px; font-weight: 800; color: #0f2d52; margin: 30px 0 14px; border-left: 4px solid #c9a43c; padding-left: 12px; }
  .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 30px; }
  .stat { background: #f8fafc; border-radius: 8px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; }
  .stat .val { font-size: 28px; font-weight: 900; color: #0f2d52; }
  .stat .lbl { font-size: 11px; color: #888; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  th { background: #0f2d52; color: white; padding: 10px 12px; text-align: left; }
  td { padding: 9px 12px; border-bottom: 1px solid #f0f0f0; }
  tr:nth-child(even) td { background: #f8fafc; }
  .badge-paid { background: #d1fae5; color: #065f46; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
  .badge-unpaid { background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
  .footer { text-align: center; color: #aaa; font-size: 11px; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; }
  .btn-print { position: fixed; top: 20px; right: 20px; background: #0f2d52; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; }
</style>
</head>
<body>
<button class="btn-print no-print" onclick="window.print()">🖨️ Print / Save PDF</button>

<div class="report">
  <div class="header">
    <div class="logo">MUST</div>
    <div class="subtitle">Malawi University of Science and Technology</div>
    <div style="font-size:18px;font-weight:700;margin:12px 0 4px;color:#333">Hostel Accommodation Report</div>
    <div class="subtitle">Academic Year <?= date('Y') ?>/<?= date('Y')+1 ?> &nbsp;|&nbsp; Generated: <?= date('d F Y, h:i A') ?></div>
  </div>

  <div class="section-title">Summary Statistics</div>
  <div class="stats-grid">
    <div class="stat"><div class="val"><?= $totalApps ?></div><div class="lbl">Applications</div></div>
    <div class="stat"><div class="val"><?= $allocated ?></div><div class="lbl">Allocated</div></div>
    <div class="stat"><div class="val"><?= $notAllocated ?></div><div class="lbl">Not Allocated</div></div>
    <div class="stat"><div class="val"><?= $pending ?></div><div class="lbl">Pending</div></div>
    <div class="stat"><div class="val"><?= $paid ?></div><div class="lbl">Paid</div></div>
    <div class="stat"><div class="val"><?= $unpaid ?></div><div class="lbl">Unpaid</div></div>
    <div class="stat"><div class="val"><?= round($allocated/(max($totalApps,1))*100) ?>%</div><div class="lbl">Allocation Rate</div></div>
    <div class="stat"><div class="val">MWK <?= number_format($totalRev) ?></div><div class="lbl">Revenue</div></div>
  </div>

  <div class="section-title">Allocated Students</div>
  <table>
    <thead>
      <tr><th>#</th><th>Reg Number</th><th>Full Name</th><th>Gender</th><th>Year</th><th>Hall/Block</th><th>Room</th><th>Floor</th><th>Payment</th></tr>
    </thead>
    <tbody>
      <?php foreach ($allocations as $i => $al): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><strong><?= htmlspecialchars($al['reg_number']) ?></strong></td>
        <td><?= htmlspecialchars($al['full_name']) ?></td>
        <td><?= ucfirst($al['gender']) ?></td>
        <td>Year <?= $al['year_of_study'] ?></td>
        <td><?= htmlspecialchars($al['hall_or_block']) ?></td>
        <td><?= htmlspecialchars($al['room_number']) ?></td>
        <td><?= htmlspecialchars($al['floor_level']) ?></td>
        <td><span class="badge-<?= $al['pay_status']==='paid'?'paid':'unpaid' ?>"><?= ucfirst($al['pay_status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">
    <p>MUST Hostel Booking System &mdash; Confidential Document</p>
    <p>Malawi University of Science and Technology | Thyolo, Malawi</p>
  </div>
</div>
</body>
</html>
