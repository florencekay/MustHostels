<?php
require_once '../includes/config.php';
require_once '../includes/pdf.php';
requireLogin(['admin','operator']);
$db = getDB();

$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT p.*, i.invoice_number, i.amount, i.due_date FROM payments p JOIN invoices i ON p.invoice_id=i.id WHERE p.id=?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Receipt not found.');
}

$student = $db->prepare("SELECT * FROM users WHERE id=?");
$student->execute([$payment['student_id']]);
$student = $student->fetch();

$alloc = $db->prepare("SELECT * FROM allocations WHERE student_id=? LIMIT 1");
$alloc->execute([$payment['student_id']]);
$allocation = $alloc->fetch();

$invoice = ['invoice_number' => $payment['invoice_number'], 'amount' => $payment['amount'], 'due_date' => $payment['due_date']];

echo generateReceiptHTML($payment, $invoice, $student, $allocation);
