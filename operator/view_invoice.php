<?php
require_once '../includes/config.php';
require_once '../includes/pdf.php';
requireLogin(['admin','operator','student']);
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT i.*, u.full_name, u.reg_number, u.email FROM invoices i JOIN users u ON i.student_id=u.id WHERE i.id=?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) die('Invoice not found.');

// Students can only view their own
if ($_SESSION['role'] === 'student' && $invoice['student_id'] != $_SESSION['user_id']) {
    die('Unauthorized.');
}

$student = ['full_name' => $invoice['full_name'], 'reg_number' => $invoice['reg_number'], 'email' => $invoice['email']];
echo generateInvoiceHTML($invoice, $student);
