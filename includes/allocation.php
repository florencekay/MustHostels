<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email.php';

/**
 * MUST Hostel Allocation Engine
 * 
 * Rules:
 * - Year 1: Extension wing (Blocks A-K male, L-N female), else Ground floor if no extension space
 * - Year 2 & 3: First floor (Halls 1-4 male, 5-7 female)
 * - Year 4 & 5: Second floor (Hall 8 for male seniors, Halls 5-7 second floor for female)
 * - Total capacity: 500
 */

function getAllocationStats() {
    $db = getDB();
    $total = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $allocated = $db->query("SELECT COUNT(*) FROM applications WHERE status='allocated'")->fetchColumn();
    $pending = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
    $notAllocated = $db->query("SELECT COUNT(*) FROM applications WHERE status='not_allocated'")->fetchColumn();
    return compact('total', 'allocated', 'pending', 'notAllocated');
}

function runBatchAllocation() {
    $db = getDB();
    
    // Get pending applications, randomly ordered
    $applications = $db->query(
        "SELECT a.*, u.email FROM applications a 
         JOIN users u ON a.student_id = u.id 
         WHERE a.status = 'pending' 
         ORDER BY RAND()"
    )->fetchAll();
    
    $allocated = 0;
    $rejected = 0;
    $totalAlreadyAllocated = (int)$db->query("SELECT COUNT(*) FROM allocations")->fetchColumn();
    $remaining = MAX_CAPACITY - $totalAlreadyAllocated;
    
    foreach ($applications as $app) {
        if ($remaining <= 0) {
            // No more space - reject
            $db->prepare("UPDATE applications SET status='not_allocated' WHERE id=?")->execute([$app['id']]);
            sendRejectionEmail($app['email'], $app['full_name']);
            $rejected++;
            continue;
        }
        
        $room = findRoom($app['year_of_study'], $app['gender'], $db);
        
        if ($room) {
            allocateRoom($app, $room, $db, false);
            $remaining--;
            $allocated++;
        } else {
            $db->prepare("UPDATE applications SET status='not_allocated' WHERE id=?")->execute([$app['id']]);
            sendRejectionEmail($app['email'], $app['full_name']);
            $rejected++;
        }
    }
    
    return ['allocated' => $allocated, 'rejected' => $rejected];
}

function findRoom($year, $gender, $db) {
    // Year 1 → Extension wing
    if ($year == 1) {
        $extGender = ($gender === 'male') ? 'male' : 'female';
        $block = $db->prepare(
            "SELECT * FROM extension_blocks 
             WHERE gender = ? AND occupied_rooms < total_rooms 
             ORDER BY RAND() LIMIT 1"
        );
        $block->execute([$extGender]);
        $extBlock = $block->fetch();
        
        if ($extBlock) {
            return ['type' => 'extension', 'data' => $extBlock];
        }
        // Fallback to ground floor if extension full
        return findHallRoom($year, $gender, 'ground', $db);
    }
    
    // Year 2 & 3 → First floor
    if ($year == 2 || $year == 3) {
        return findHallRoom($year, $gender, 'first', $db);
    }
    
    // Year 4 & 5 → Second floor
    if ($year >= 4) {
        return findHallRoom($year, $gender, 'second', $db);
    }
    
    return null;
}

function findHallRoom($year, $gender, $floor, $db) {
    // Determine eligible hall IDs
    if ($gender === 'male') {
        if ($year >= 4) {
            $hallWhere = "h.id = 8"; // Hall 8 seniors
        } else {
            $hallWhere = "h.id IN (1,2,3,4)"; // Halls 1-4 male
        }
    } else {
        $hallWhere = "h.id IN (5,6,7)"; // Halls 5-7 female
    }
    
    $stmt = $db->prepare(
        "SELECT r.*, h.hall_name FROM rooms r 
         JOIN halls h ON r.hall_id = h.id
         WHERE {$hallWhere} AND r.floor = ? AND r.is_available = 1 AND r.occupied < r.capacity
         ORDER BY RAND() LIMIT 1"
    );
    $stmt->execute([$floor]);
    $room = $stmt->fetch();
    
    if ($room) {
        return ['type' => 'hall', 'data' => $room];
    }
    return null;
}

function allocateRoom($app, $room, $db, $isManual = false, $adminId = null) {
    $roomNumber = '';
    $hallOrBlock = '';
    $floorLevel = '';
    $roomId = null;
    $extBlockId = null;
    
    if ($room['type'] === 'extension') {
        $blk = $room['data'];
        $roomNum = $blk['occupied_rooms'] + 1;
        $roomNumber = $blk['block_name'] . '-' . str_pad($roomNum, 2, '0', STR_PAD_LEFT);
        $hallOrBlock = 'Extension Block ' . $blk['block_name'];
        $floorLevel = 'Ground';
        $extBlockId = $blk['id'];
        
        // Update extension block
        $db->prepare("UPDATE extension_blocks SET occupied_rooms = occupied_rooms + 1 WHERE id=?")->execute([$blk['id']]);
    } else {
        $rm = $room['data'];
        $roomNumber = $rm['room_number'];
        $hallOrBlock = $rm['hall_name'];
        $floorLevel = ucfirst($rm['floor']) . ' Floor';
        $roomId = $rm['id'];
        
        // Update room occupancy
        $db->prepare("UPDATE rooms SET occupied = occupied + 1, is_available = CASE WHEN occupied + 1 >= capacity THEN 0 ELSE 1 END WHERE id=?")->execute([$rm['id']]);
    }
    
    // Insert allocation
    $db->prepare(
        "INSERT INTO allocations (application_id, student_id, reg_number, room_id, extension_block_id, room_number, hall_or_block, floor_level, allocated_by, is_manual)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    )->execute([
        $app['id'], $app['student_id'], $app['reg_number'],
        $roomId, $extBlockId, $roomNumber, $hallOrBlock, $floorLevel,
        $adminId, $isManual ? 1 : 0
    ]);
    
    // Update application status
    $db->prepare("UPDATE applications SET status='allocated' WHERE id=?")->execute([$app['id']]);
    
    // Send allocation email
    $email = isset($app['email']) ? $app['email'] : genEmail($app['reg_number']);
    sendAllocationEmail($email, $app['full_name'], $roomNumber, $hallOrBlock);
    
    return ['room_number' => $roomNumber, 'hall_or_block' => $hallOrBlock, 'floor' => $floorLevel];
}

function manualAllocate($applicationId, $hallOrBlock, $roomNumber, $floorLevel, $adminId) {
    $db = getDB();
    $app = $db->prepare("SELECT a.*, u.email FROM applications a JOIN users u ON a.student_id=u.id WHERE a.id=?")->execute([$applicationId]);
    $app = $db->prepare("SELECT a.*, u.email FROM applications a JOIN users u ON a.student_id=u.id WHERE a.id=?")->execute([$applicationId]);
    // Use proper fetch
    $stmt = $db->prepare("SELECT a.*, u.email FROM applications a JOIN users u ON a.student_id=u.id WHERE a.id=?");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch();
    
    if (!$app) return false;
    
    // Remove existing allocation if any
    $db->prepare("DELETE FROM allocations WHERE application_id=?")->execute([$applicationId]);
    
    // Insert manual allocation
    $db->prepare(
        "INSERT INTO allocations (application_id, student_id, reg_number, room_number, hall_or_block, floor_level, allocated_by, is_manual)
         VALUES (?,?,?,?,?,?,?,1)"
    )->execute([$applicationId, $app['student_id'], $app['reg_number'], $roomNumber, $hallOrBlock, $floorLevel, $adminId]);
    
    $db->prepare("UPDATE applications SET status='allocated' WHERE id=?")->execute([$applicationId]);
    
    sendAllocationEmail($app['email'], $app['full_name'], $roomNumber, $hallOrBlock . ' - ' . $floorLevel);
    
    return true;
}
?>
