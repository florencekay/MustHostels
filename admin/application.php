<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

$pageTitle = 'Applications';
$pageSubtitle = 'All student hostel applications';

// Filters
$statusFilter = clean($_GET['status'] ?? '');
$genderFilter = clean($_GET['gender'] ?? '');
$search = clean($_GET['search'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($statusFilter) { $where .= " AND a.status=?"; $params[] = $statusFilter; }
if ($genderFilter) { $where .= " AND a.gender=?"; $params[] = $genderFilter; }
if ($search) {
    $where .= " AND (a.reg_number LIKE ? OR a.full_name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}

$stmt = $db->prepare("SELECT a.*, u.email FROM applications a JOIN users u ON a.student_id=u.id $where ORDER BY a.applied_at DESC");
$stmt->execute($params);
$applications = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header flex-between">
  <div>
    <h2>Applications</h2>
    <p>Total: <?= count($applications) ?> applications found</p>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="margin:0;flex:1;min-width:160px">
        <label style="margin-bottom:4px">Search</label>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Reg # or Name...">
      </div>
      <div class="form-group" style="margin:0">
        <label style="margin-bottom:4px">Status</label>
        <select name="status">
          <option value="">All</option>
          <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
          <option value="allocated" <?= $statusFilter==='allocated'?'selected':'' ?>>Allocated</option>
          <option value="not_allocated" <?= $statusFilter==='not_allocated'?'selected':'' ?>>Not Allocated</option>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label style="margin-bottom:4px">Gender</label>
        <select name="gender">
          <option value="">All</option>
          <option value="male" <?= $genderFilter==='male'?'selected':'' ?>>Male</option>
          <option value="female" <?= $genderFilter==='female'?'selected':'' ?>>Female</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="applications.php" class="btn btn-outline">Reset</a>
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
          <th>Gender</th>
          <th>Year</th>
          <th>Email</th>
          <th>Special Needs</th>
          <th>Applied</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($applications)): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:#888">No applications found</td></tr>
        <?php endif; ?>
        <?php foreach ($applications as $i => $app): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($app['reg_number']) ?></strong></td>
          <td><?= htmlspecialchars($app['full_name']) ?></td>
          <td><?= ucfirst($app['gender']) ?></td>
          <td>Year <?= $app['year_of_study'] ?></td>
          <td><small><?= htmlspecialchars($app['email']) ?></small></td>
          <td>
            <?php if ($app['special_needs']): ?>
            <span class="badge badge-purple" title="<?= htmlspecialchars($app['special_needs']) ?>">♿ Yes</span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
          <td>
            <?php
            $badges = ['pending'=>'warning','allocated'=>'success','not_allocated'=>'danger','waitlisted'=>'info'];
            $b = $badges[$app['status']] ?? 'gray';
            ?>
            <span class="badge badge-<?= $b ?>"><?= str_replace('_',' ',$app['status']) ?></span>
          </td>
          <td>
            <div class="gap-8">
              <?php if ($app['status'] !== 'allocated'): ?>
              <a href="manual_allocate.php?id=<?= $app['id'] ?>" class="btn btn-sm btn-primary">🏠 Allocate</a>
              <?php endif; ?>
              <a href="view_application.php?id=<?= $app['id'] ?>" class="btn btn-sm btn-outline">👁 View</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
