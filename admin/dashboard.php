<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

$pageTitle = 'Admin Dashboard';
$pageSubtitle = 'Overview of the hostel booking system';

// Stats
$totalStudents = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalApplications = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$allocated = $db->query("SELECT COUNT(*) FROM applications WHERE status='allocated'")->fetchColumn();
$notAllocated = $db->query("SELECT COUNT(*) FROM applications WHERE status='not_allocated'")->fetchColumn();
$pending = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$paid = $db->query("SELECT COUNT(*) FROM payments WHERE status='verified'")->fetchColumn();
$unpaid = $db->query("SELECT COUNT(DISTINCT student_id) FROM invoices WHERE status='unpaid'")->fetchColumn();
$openInquiries = $db->query("SELECT COUNT(*) FROM inquiries WHERE status='open'")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE status='verified'")->fetchColumn();

// Recent applications
$recentApps = $db->query(
    "SELECT a.*, u.email FROM applications a JOIN users u ON a.student_id=u.id ORDER BY a.applied_at DESC LIMIT 8"
)->fetchAll();

// Capacity
$totalAllocations = (int)$allocated;
$capacityPct = MAX_CAPACITY > 0 ? round(($totalAllocations / MAX_CAPACITY) * 100) : 0;

require_once '../includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#dbeafe">🎓</div>
    <div class="stat-info">
      <div class="value"><?= number_format($totalStudents) ?></div>
      <div class="label">Registered Students</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7">📋</div>
    <div class="stat-info">
      <div class="value"><?= number_format($totalApplications) ?></div>
      <div class="label">Applications</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#d1fae5">🏠</div>
    <div class="stat-info">
      <div class="value"><?= number_format($allocated) ?></div>
      <div class="label">Allocated Rooms</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fee2e2">❌</div>
    <div class="stat-info">
      <div class="value"><?= number_format($notAllocated) ?></div>
      <div class="label">Not Allocated</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7">⏳</div>
    <div class="stat-info">
      <div class="value"><?= number_format($pending) ?></div>
      <div class="label">Pending</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#d1fae5">💰</div>
    <div class="stat-info">
      <div class="value">MWK <?= number_format($totalRevenue) ?></div>
      <div class="label">Revenue Collected</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe">📬</div>
    <div class="stat-info">
      <div class="value"><?= number_format($openInquiries) ?></div>
      <div class="label">Open Inquiries</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fee2e2">💸</div>
    <div class="stat-info">
      <div class="value"><?= number_format($unpaid) ?></div>
      <div class="label">Unpaid Invoices</div>
    </div>
  </div>
</div>

<!-- Capacity Bar + Actions -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
  <div class="card">
    <div class="card-header"><h3>🏨 Hostel Capacity</h3></div>
    <div class="card-body">
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span class="text-muted">Used: <?= $allocated ?> / <?= MAX_CAPACITY ?></span>
        <span style="font-weight:700;color:var(--navy)"><?= $capacityPct ?>%</span>
      </div>
      <div class="progress" style="height:14px">
        <div class="progress-bar" style="width:<?= $capacityPct ?>%;background:<?= $capacityPct >= 90 ? 'var(--red)' : ($capacityPct >= 70 ? 'var(--orange)' : 'var(--green)') ?>"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:16px;text-align:center">
        <div style="background:var(--gray-50);border-radius:8px;padding:10px">
          <div style="font-size:20px;font-weight:800;color:var(--navy)"><?= MAX_CAPACITY - $allocated ?></div>
          <div class="text-muted">Available</div>
        </div>
        <div style="background:var(--green-light);border-radius:8px;padding:10px">
          <div style="font-size:20px;font-weight:800;color:var(--green)"><?= $allocated ?></div>
          <div class="text-muted">Allocated</div>
        </div>
        <div style="background:var(--red-light);border-radius:8px;padding:10px">
          <div style="font-size:20px;font-weight:800;color:var(--red)"><?= $notAllocated ?></div>
          <div class="text-muted">Rejected</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>⚡ Quick Actions</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
      <?php if ($pending > 0): ?>
      <form method="POST" action="run_allocation.php" onsubmit="return confirm('Run batch allocation for <?= $pending ?> pending applications?')">
        <button type="submit" class="btn btn-gold" style="width:100%">
          🎲 Run Batch Allocation (<?= $pending ?> pending)
        </button>
      </form>
      <?php else: ?>
      <div class="alert alert-info" style="margin:0">✅ No pending applications to allocate.</div>
      <?php endif; ?>
      <a href="applications.php" class="btn btn-outline" style="width:100%;justify-content:center">📋 View All Applications</a>
      <a href="reports.php" class="btn btn-outline" style="width:100%;justify-content:center">📈 Generate Report</a>
      <a href="rooms.php" class="btn btn-outline" style="width:100%;justify-content:center">🏨 Manage Rooms</a>
    </div>
  </div>
</div>

<!-- Recent Applications -->
<div class="card">
  <div class="card-header">
    <h3>📋 Recent Applications</h3>
    <a href="applications.php" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Reg Number</th>
          <th>Full Name</th>
          <th>Year</th>
          <th>Gender</th>
          <th>Applied</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentApps)): ?>
        <tr><td colspan="7" style="text-align:center;padding:30px;color:#888">No applications yet</td></tr>
        <?php endif; ?>
        <?php foreach ($recentApps as $app): ?>
        <tr>
          <td><strong><?= htmlspecialchars($app['reg_number']) ?></strong></td>
          <td><?= htmlspecialchars($app['full_name']) ?></td>
          <td>Year <?= $app['year_of_study'] ?></td>
          <td><?= ucfirst($app['gender']) ?></td>
          <td><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
          <td>
            <?php
            $badges = [
              'pending' => 'warning',
              'allocated' => 'success',
              'not_allocated' => 'danger',
              'waitlisted' => 'info'
            ];
            $b = $badges[$app['status']] ?? 'gray';
            ?>
            <span class="badge badge-<?= $b ?>"><?= str_replace('_', ' ', $app['status']) ?></span>
          </td>
          <td>
            <?php if ($app['status'] === 'pending' || $app['status'] === 'not_allocated'): ?>
            <a href="manual_allocate.php?id=<?= $app['id'] ?>" class="btn btn-sm btn-primary">Allocate</a>
            <?php else: ?>
            <a href="allocations.php?student=<?= urlencode($app['reg_number']) ?>" class="btn btn-sm btn-outline">View</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
