<?php
require_once '../includes/config.php';
require_once '../includes/allocation.php';
requireLogin('admin');
$db = getDB();

$pageTitle = 'Manual Room Allocation';
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT a.*, u.email FROM applications a JOIN users u ON a.student_id=u.id WHERE a.id=?");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    setFlash('error', 'Application not found.');
    header('Location: applications.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hallOrBlock = clean($_POST['hall_or_block'] ?? '');
    $roomNumber = clean($_POST['room_number'] ?? '');
    $floorLevel = clean($_POST['floor_level'] ?? '');

    if (empty($hallOrBlock) || empty($roomNumber) || empty($floorLevel)) {
        $error = 'Please fill all allocation fields.';
    } else {
        if (manualAllocate($id, $hallOrBlock, $roomNumber, $floorLevel, $_SESSION['user_id'])) {
            setFlash('success', "Room allocated successfully to {$app['full_name']}");
            header('Location: allocations.php');
            exit;
        } else {
            $error = 'Allocation failed. Please try again.';
        }
    }
}

// Halls for dropdown
$halls = $db->query("SELECT * FROM halls ORDER BY id")->fetchAll();
$blocks = $db->query("SELECT * FROM extension_blocks ORDER BY block_name")->fetchAll();

require_once '../includes/header.php';
?>

<div style="max-width:700px">
  <div class="page-header">
    <h2>Manual Room Allocation</h2>
    <p>Assign a specific room for this student</p>
  </div>

  <!-- Student Info -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>👤 Student Details</h3></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div><label class="text-muted">Registration Number</label><div class="text-bold"><?= htmlspecialchars($app['reg_number']) ?></div></div>
        <div><label class="text-muted">Full Name</label><div class="text-bold"><?= htmlspecialchars($app['full_name']) ?></div></div>
        <div><label class="text-muted">Gender</label><div><?= ucfirst($app['gender']) ?></div></div>
        <div><label class="text-muted">Year of Study</label><div>Year <?= $app['year_of_study'] ?></div></div>
        <div><label class="text-muted">Email</label><div><?= htmlspecialchars($app['email']) ?></div></div>
        <div>
          <label class="text-muted">Special Needs</label>
          <div>
            <?php if ($app['special_needs']): ?>
            <span class="badge badge-purple">♿ <?= htmlspecialchars($app['special_needs']) ?></span>
            <?php else: ?>
            <span class="text-muted">None</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if ($app['special_needs']): ?>
      <div class="alert alert-warning" style="margin-top:16px;margin-bottom:0">
        ⚠️ <strong>Special needs noted:</strong> <?= htmlspecialchars($app['special_needs']) ?>. Consider ground floor allocation.
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Allocation Form -->
  <?php if ($error): ?>
  <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h3>🏠 Assign Room</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label>Location Type <span class="req">*</span></label>
          <select id="locType" onchange="toggleLocFields()">
            <option value="hall">Hall (1-8)</option>
            <option value="extension">Extension Block (A-N)</option>
          </select>
        </div>

        <!-- Hall fields -->
        <div id="hallFields">
          <div class="form-row">
            <div class="form-group">
              <label>Hall <span class="req">*</span></label>
              <select id="hallSelect" onchange="updateHallName()">
                <?php foreach ($halls as $hall): ?>
                <option value="<?= htmlspecialchars($hall['hall_name']) ?>"
                  data-type="<?= $hall['hall_type'] ?>">
                  <?= htmlspecialchars($hall['hall_name']) ?>
                  (<?= $hall['hall_type'] === 'male_senior' ? 'Senior Male' : ucfirst($hall['hall_type']) ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Floor <span class="req">*</span></label>
              <select id="hallFloor" onchange="updateFloor()">
                <option value="Ground Floor">Ground Floor</option>
                <option value="First Floor">First Floor</option>
                <option value="Second Floor">Second Floor</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Room Number <span class="req">*</span></label>
            <input type="text" id="hallRoom" placeholder="e.g. H1-ground-01">
            <div class="form-hint">
              Suggested based on year <?= $app['year_of_study'] ?>:
              <?php
              $suggested = '';
              if ($app['year_of_study'] == 1) $suggested = 'Ground Floor';
              elseif ($app['year_of_study'] <= 3) $suggested = 'First Floor';
              else $suggested = 'Second Floor';
              echo $suggested;
              ?>
            </div>
          </div>
        </div>

        <!-- Extension Block Fields -->
        <div id="extFields" style="display:none">
          <div class="form-row">
            <div class="form-group">
              <label>Block <span class="req">*</span></label>
              <select id="blockSelect" onchange="updateBlockName()">
                <?php foreach ($blocks as $blk): ?>
                <option value="Extension Block <?= $blk['block_name'] ?>"
                  data-gender="<?= $blk['gender'] ?>">
                  Block <?= $blk['block_name'] ?> (<?= ucfirst($blk['gender']) ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Room Number <span class="req">*</span></label>
              <input type="text" id="extRoom" placeholder="e.g. A-01">
            </div>
          </div>
        </div>

        <!-- Hidden fields submitted -->
        <input type="hidden" name="hall_or_block" id="hallOrBlock">
        <input type="hidden" name="room_number" id="roomNumber">
        <input type="hidden" name="floor_level" id="floorLevel">

        <div style="display:flex;gap:10px;margin-top:8px">
          <button type="submit" class="btn btn-primary" onclick="prepareSubmit()">✅ Confirm Allocation</button>
          <a href="applications.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleLocFields() {
    const type = document.getElementById('locType').value;
    document.getElementById('hallFields').style.display = type === 'hall' ? '' : 'none';
    document.getElementById('extFields').style.display = type === 'extension' ? '' : 'none';
}

function prepareSubmit() {
    const type = document.getElementById('locType').value;
    if (type === 'hall') {
        document.getElementById('hallOrBlock').value = document.getElementById('hallSelect').value;
        document.getElementById('roomNumber').value = document.getElementById('hallRoom').value;
        document.getElementById('floorLevel').value = document.getElementById('hallFloor').value;
    } else {
        document.getElementById('hallOrBlock').value = document.getElementById('blockSelect').value;
        document.getElementById('roomNumber').value = document.getElementById('extRoom').value;
        document.getElementById('floorLevel').value = 'Ground Floor';
    }
    return true;
}
</script>

<?php require_once '../includes/footer.php'; ?>
