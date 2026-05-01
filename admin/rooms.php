<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();
$pageTitle = 'Rooms & Halls';
$pageSubtitle = 'Manage hostel halls and extension blocks';

$halls = $db->query(
    "SELECT h.*, 
     COUNT(r.id) as total_rooms,
     SUM(r.capacity) as total_capacity,
     SUM(r.occupied) as total_occupied,
     SUM(CASE WHEN r.is_available=1 THEN 1 ELSE 0 END) as available_rooms
     FROM halls h LEFT JOIN rooms r ON r.hall_id=h.id
     GROUP BY h.id"
)->fetchAll();

$blocks = $db->query("SELECT * FROM extension_blocks ORDER BY gender, block_name")->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header"><h2>Rooms & Halls</h2><p>Hostel capacity and occupancy overview</p></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
  <!-- Halls -->
  <div class="card">
    <div class="card-header"><h3>🏨 Halls (1–8)</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Hall</th><th>Type</th><th>Rooms</th><th>Capacity</th><th>Occupied</th><th>%</th></tr></thead>
        <tbody>
          <?php foreach ($halls as $h): ?>
          <?php $pct = $h['total_capacity'] > 0 ? round($h['total_occupied']/$h['total_capacity']*100) : 0; ?>
          <tr>
            <td><strong><?= htmlspecialchars($h['hall_name']) ?></strong></td>
            <td><?= ucfirst(str_replace('_',' ',$h['hall_type'])) ?></td>
            <td><?= $h['total_rooms'] ?></td>
            <td><?= $h['total_capacity'] ?></td>
            <td><?= $h['total_occupied'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:6px">
                <div class="progress" style="width:50px;height:5px">
                  <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                </div>
                <span style="font-size:11px"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Extension Blocks -->
  <div class="card">
    <div class="card-header"><h3>🏗 Extension Blocks</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Block</th><th>Gender</th><th>Total Rooms</th><th>Occupied</th><th>Available</th></tr></thead>
        <tbody>
          <?php foreach ($blocks as $b): ?>
          <tr>
            <td><strong>Block <?= htmlspecialchars($b['block_name']) ?></strong></td>
            <td><?= ucfirst($b['gender']) ?></td>
            <td><?= $b['total_rooms'] ?></td>
            <td><?= $b['occupied_rooms'] ?></td>
            <td>
              <?php $avail = $b['total_rooms'] - $b['occupied_rooms']; ?>
              <span class="badge badge-<?= $avail > 0 ? 'success' : 'danger' ?>"><?= $avail ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Room list by hall -->
<?php foreach ([1,2,3,4,5,6,7,8] as $hallId): ?>
<?php
$roomList = $db->prepare(
    "SELECT r.*, h.hall_name FROM rooms r JOIN halls h ON r.hall_id=h.id WHERE r.hall_id=? ORDER BY r.floor, r.room_number LIMIT 30"
);
$roomList->execute([$hallId]);
$rooms = $roomList->fetchAll();
if (empty($rooms)) continue;
?>
<details style="margin-bottom:12px">
  <summary style="cursor:pointer;padding:14px 20px;background:white;border-radius:8px;border:1px solid var(--gray-200);font-weight:700;color:var(--navy)">
    <?= htmlspecialchars($rooms[0]['hall_name']) ?> — <?= count($rooms) ?> rooms shown
  </summary>
  <div class="card" style="border-top-left-radius:0;border-top-right-radius:0;margin-top:-1px">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Room #</th><th>Floor</th><th>Capacity</th><th>Occupied</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($rooms as $rm): ?>
          <tr>
            <td><?= htmlspecialchars($rm['room_number']) ?></td>
            <td><?= ucfirst($rm['floor']) ?> Floor</td>
            <td><?= $rm['capacity'] ?></td>
            <td><?= $rm['occupied'] ?></td>
            <td><span class="badge badge-<?= $rm['is_available'] ? 'success' : 'danger' ?>"><?= $rm['is_available'] ? 'Available' : 'Full' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</details>
<?php endforeach; ?>

<?php require_once '../includes/footer.php'; ?>
