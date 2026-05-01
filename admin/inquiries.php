<?php
require_once '../includes/config.php';
requireLogin(['admin','operator']);
$db = getDB();
$pageTitle = 'Inquiries';
$pageSubtitle = 'Student inquiries and special needs requests';

// Handle response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond'])) {
    $inquiryId = (int)$_POST['inquiry_id'];
    $response = clean($_POST['response']);

    $db->prepare("UPDATE inquiries SET status='resolved', response=?, responded_by=?, responded_at=NOW() WHERE id=?")
       ->execute([$response, $_SESSION['user_id'], $inquiryId]);

    // Send email
    $inq = $db->prepare("SELECT i.*, u.email, u.full_name FROM inquiries i JOIN users u ON i.student_id=u.id WHERE i.id=?");
    $inq->execute([$inquiryId]);
    $inqData = $inq->fetch();
    if ($inqData) {
        require_once '../includes/email.php';
        sendInquiryResponseEmail($inqData['email'], $inqData['full_name'], $inqData['subject'], $response);
    }

    setFlash('success', 'Response sent to student via email.');
    header('Location: inquiries.php');
    exit;
}

$status = clean($_GET['status'] ?? '');
$where = $status ? "WHERE status=?" : "WHERE 1=1";
$params = $status ? [$status] : [];

$stmt = $db->prepare("SELECT i.*, u.full_name, u.email FROM inquiries i JOIN users u ON i.student_id=u.id $where ORDER BY i.created_at DESC");
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header flex-between">
  <div><h2>Student Inquiries</h2></div>
  <div class="gap-8">
    <a href="?status=open" class="btn btn-sm <?= $status==='open'?'btn-primary':'btn-outline' ?>">Open</a>
    <a href="?status=in_progress" class="btn btn-sm <?= $status==='in_progress'?'btn-primary':'btn-outline' ?>">In Progress</a>
    <a href="?status=resolved" class="btn btn-sm <?= $status==='resolved'?'btn-primary':'btn-outline' ?>">Resolved</a>
    <a href="inquiries.php" class="btn btn-sm btn-outline">All</a>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Student</th><th>Subject</th><th>Special Needs</th><th>Date</th><th>Status</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php if (empty($inquiries)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#888">No inquiries found</td></tr>
        <?php endif; ?>
        <?php foreach ($inquiries as $i => $inq): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td>
            <strong><?= htmlspecialchars($inq['full_name']) ?></strong><br>
            <small class="text-muted"><?= htmlspecialchars($inq['reg_number']) ?></small>
          </td>
          <td><?= htmlspecialchars($inq['subject']) ?></td>
          <td>
            <?php if ($inq['has_special_needs']): ?>
            <span class="badge badge-purple">♿ <?= htmlspecialchars($inq['special_need_type'] ?? 'Yes') ?></span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= date('d M Y', strtotime($inq['created_at'])) ?></td>
          <td>
            <?php $b = ['open'=>'danger','in_progress'=>'warning','resolved'=>'success'][$inq['status']] ?? 'gray'; ?>
            <span class="badge badge-<?= $b ?>"><?= str_replace('_',' ',ucfirst($inq['status'])) ?></span>
          </td>
          <td>
            <button onclick="openInquiry(<?= $inq['id'] ?>, '<?= htmlspecialchars(addslashes($inq['full_name'])) ?>', '<?= htmlspecialchars(addslashes($inq['subject'])) ?>', '<?= htmlspecialchars(addslashes($inq['message'])) ?>', '<?= htmlspecialchars(addslashes($inq['response'] ?? '')) ?>')"
                    class="btn btn-sm btn-primary">
              <?= $inq['status'] === 'resolved' ? '👁 View' : '💬 Respond' ?>
            </button>
            <?php if ($inq['has_special_needs'] && $inq['status'] !== 'resolved'): ?>
            <a href="../admin/manual_allocate.php?reg=<?= urlencode($inq['reg_number']) ?>" class="btn btn-sm btn-gold">🏠 Allocate</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Response Modal -->
<div class="modal-overlay" id="inqModal" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalTitle">Inquiry Details</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div style="background:var(--gray-50);border-radius:8px;padding:16px;margin-bottom:16px">
        <strong>From:</strong> <span id="modalStudent"></span><br>
        <strong>Subject:</strong> <span id="modalSubject"></span><br>
        <br>
        <strong>Message:</strong><br>
        <div id="modalMessage" style="margin-top:6px;color:#444"></div>
      </div>
      <div id="prevResponse" style="display:none;margin-bottom:16px">
        <strong>Previous Response:</strong>
        <div id="modalPrevResp" style="background:#f0fdf4;padding:12px;border-radius:6px;margin-top:6px;color:#065f46"></div>
      </div>
      <form method="POST" id="respondForm">
        <input type="hidden" name="respond" value="1">
        <input type="hidden" name="inquiry_id" id="modalInquiryId">
        <div class="form-group">
          <label>Your Response</label>
          <textarea name="response" id="responseText" rows="4" placeholder="Type your response here..." required></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="document.getElementById('respondForm').submit()">Send Response 📧</button>
    </div>
  </div>
</div>

<script>
function openInquiry(id, student, subject, message, prevResp) {
    document.getElementById('modalInquiryId').value = id;
    document.getElementById('modalTitle').textContent = 'Inquiry #' + id;
    document.getElementById('modalStudent').textContent = student;
    document.getElementById('modalSubject').textContent = subject;
    document.getElementById('modalMessage').textContent = message;
    if (prevResp) {
        document.getElementById('prevResponse').style.display = '';
        document.getElementById('modalPrevResp').textContent = prevResp;
        document.getElementById('responseText').value = prevResp;
    } else {
        document.getElementById('prevResponse').style.display = 'none';
        document.getElementById('responseText').value = '';
    }
    document.getElementById('inqModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('inqModal').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>
