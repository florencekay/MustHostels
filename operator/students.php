<?php
require_once '../includes/config.php';
requireLogin(['admin','operator']);
$db = getDB();
$pageTitle = 'Students';
$pageSubtitle = 'All registered students';

$students = $db->query(
    "SELECT u.*, 
     (SELECT status FROM applications WHERE student_id=u.id ORDER BY applied_at DESC LIMIT 1) as app_status,
     (SELECT status FROM invoices WHERE student_id=u.id ORDER BY issued_at DESC LIMIT 1) as pay_status
     FROM users u WHERE u.role='student' ORDER BY u.full_name"
)->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header"><h2>Registered Students</h2><p><?= count($students) ?> students</p></div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Reg #</th><th>Full Name</th><th>Gender</th><th>Email</th><th>Application</th><th>Payment</th></tr>
      </thead>
      <tbody>
        <?php if (empty($students)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#888">No students yet</td></tr>
        <?php endif; ?>
        <?php foreach ($students as $i => $s): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($s['reg_number']) ?></strong></td>
          <td><?= htmlspecialchars($s['full_name']) ?></td>
          <td><?= ucfirst($s['gender'] ?? '—') ?></td>
          <td><small><?= htmlspecialchars($s['email']) ?></small></td>
          <td>
            <?php if ($s['app_status']): ?>
            <?php $b=['pending'=>'warning','allocated'=>'success','not_allocated'=>'danger'][$s['app_status']]??'gray'; ?>
            <span class="badge badge-<?= $b ?>"><?= str_replace('_',' ',ucfirst($s['app_status'])) ?></span>
            <?php else: ?>
            <span class="text-muted">Not applied</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($s['pay_status']): ?>
            <?php $b=['unpaid'=>'danger','paid'=>'success','partial'=>'warning'][$s['pay_status']]??'gray'; ?>
            <span class="badge badge-<?= $b ?>"><?= ucfirst($s['pay_status']) ?></span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
