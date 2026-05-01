<?php
require_once '../includes/config.php';
requireLogin('student');
$db = getDB();

$pageTitle = 'My Application Status';
$userId = $_SESSION['user_id'];

$appStmt = $db->prepare("SELECT * FROM applications WHERE student_id=? ORDER BY applied_at DESC LIMIT 1");
$appStmt->execute([$userId]);
$application = $appStmt->fetch();

$allocation = null;
if ($application) {
    $alStmt = $db->prepare("SELECT * FROM allocations WHERE application_id=? LIMIT 1");
    $alStmt->execute([$application['id']]);
    $allocation = $alStmt->fetch();
}

require_once '../includes/header.php';
?>

<div style="max-width:700px">
  <div class="page-header"><h2>My Application Status</h2></div>

  <?php if (!$application): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon">📋</div>
      <h4>No Application Found</h4>
      <p>You haven't applied for hostel accommodation yet.</p>
      <a href="apply.php" class="btn btn-primary mt-16">Apply Now →</a>
    </div>
  </div>
  <?php else: ?>

  <!-- Status Timeline -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>Application Timeline</h3></div>
    <div class="card-body">
      <?php
      $steps = [
        ['label'=>'Application Submitted','done'=>true,'date'=>date('d M Y', strtotime($application['applied_at']))],
        ['label'=>'Invoice Generated','done'=>true,'date'=>date('d M Y', strtotime($application['applied_at']))],
        ['label'=>'Room Allocation Processing','done'=>in_array($application['status'],['allocated','not_allocated']),'date'=>''],
        ['label'=>'Room Allocated','done'=>$application['status']==='allocated','date'=>$allocation?date('d M Y',strtotime($allocation['allocated_at'])):''],
      ];
      ?>
      <div style="position:relative;padding-left:30px">
        <?php foreach ($steps as $step): ?>
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;position:relative">
          <div style="position:absolute;left:-30px;top:0;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;
            background:<?= $step['done'] ? 'var(--green)' : 'var(--gray-200)' ?>;
            color:<?= $step['done'] ? 'white' : 'var(--gray-500)' ?>">
            <?= $step['done'] ? '✓' : '·' ?>
          </div>
          <div>
            <div style="font-weight:600;color:<?= $step['done']?'var(--navy)':'var(--gray-500)' ?>"><?= $step['label'] ?></div>
            <?php if ($step['date']): ?><div class="text-muted"><?= $step['date'] ?></div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Application Details -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <h3>Application Details</h3>
      <span class="badge badge-<?= ['pending'=>'warning','allocated'=>'success','not_allocated'=>'danger'][$application['status']]??'gray' ?>">
        <?= str_replace('_',' ',ucfirst($application['status'])) ?>
      </span>
    </div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div><label class="text-muted">Reg Number</label><div class="text-bold"><?= htmlspecialchars($application['reg_number']) ?></div></div>
        <div><label class="text-muted">Full Name</label><div><?= htmlspecialchars($application['full_name']) ?></div></div>
        <div><label class="text-muted">Year of Study</label><div>Year <?= $application['year_of_study'] ?></div></div>
        <div><label class="text-muted">Gender</label><div><?= ucfirst($application['gender']) ?></div></div>
        <div><label class="text-muted">Email</label><div><?= htmlspecialchars($application['email']) ?></div></div>
        <div><label class="text-muted">Applied On</label><div><?= date('d M Y, h:i A', strtotime($application['applied_at'])) ?></div></div>
        <?php if ($application['special_needs']): ?>
        <div style="grid-column:1/-1"><label class="text-muted">Special Needs</label><div><?= htmlspecialchars($application['special_needs']) ?></div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Allocation Result -->
  <?php if ($allocation): ?>
  <div class="card" style="border:2px solid var(--green)">
    <div class="card-header" style="background:var(--green-light)">
      <h3 style="color:var(--green)">🎉 Room Allocation Successful!</h3>
    </div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div><label class="text-muted">Hall / Block</label><div class="text-bold" style="font-size:18px;color:var(--navy)"><?= htmlspecialchars($allocation['hall_or_block']) ?></div></div>
        <div><label class="text-muted">Room Number</label><div class="text-bold" style="font-size:18px;color:var(--navy)"><?= htmlspecialchars($allocation['room_number']) ?></div></div>
        <div><label class="text-muted">Floor</label><div><?= htmlspecialchars($allocation['floor_level']) ?></div></div>
        <div><label class="text-muted">Allocated On</label><div><?= date('d M Y', strtotime($allocation['allocated_at'])) ?></div></div>
      </div>
      <div class="alert alert-info" style="margin-top:16px;margin-bottom:0">
        💡 Please ensure your accommodation fee is paid to secure your room. <a href="payment.php">Upload payment →</a>
      </div>
    </div>
  </div>
  <?php elseif ($application['status'] === 'not_allocated'): ?>
  <div class="card" style="border:2px solid var(--red)">
    <div class="card-body" style="text-align:center;padding:30px">
      <div style="font-size:40px;margin-bottom:12px">😔</div>
      <h3 style="color:var(--red)">Not Allocated</h3>
      <p style="color:#666;margin-top:8px">Unfortunately, all hostel spaces have been filled. You have been placed on the waiting list. If space becomes available, you will be notified at <strong><?= htmlspecialchars($application['email']) ?></strong>.</p>
      <a href="inquiry.php" class="btn btn-primary mt-16">📬 Submit Inquiry for Assistance</a>
    </div>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:30px">
      <div style="font-size:40px;margin-bottom:12px">⏳</div>
      <h3 style="color:var(--orange)">Allocation Pending</h3>
      <p style="color:#666;margin-top:8px">Your application is being processed. You will be notified at <strong><?= htmlspecialchars($application['email']) ?></strong> once allocation is done.</p>
    </div>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
