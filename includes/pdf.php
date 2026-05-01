<?php
// Simple HTML-to-PDF helper for the hostel booking system.
// This file generates printable HTML receipts and invoices.
// For a full PDF library in production, install TCPDF: composer require tecnickcom/tcpdf

/**
 * Generate a receipt HTML page for a payment record.
 *
 * @param array $payment  Payment details including amount and receipt number.
 * @param array $invoice  Related invoice data.
 * @param array $student  Student profile details.
 * @param array $allocation  Allocation details for the room assignment.
 * @return string Printable HTML markup for the receipt.
 */
function generateReceiptHTML($payment, $invoice, $student, $allocation) {
    $receiptNum = $payment['receipt_number'];
    $date = date('d F Y', strtotime($payment['paid_at']));
    $amount = number_format($payment['amount_paid'], 2);
    $room = $allocation ? $allocation['hall_or_block'] . ' - Room ' . $allocation['room_number'] : 'Pending Allocation';
    
    return "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<title>Receipt - {$receiptNum}</title>
<style>
  @media print { body { margin: 0; } .no-print { display: none; } }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; padding: 30px 0; }
  .receipt { background: #fff; width: 700px; padding: 50px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
  .header { text-align: center; border-bottom: 3px double #1a3a5c; padding-bottom: 20px; margin-bottom: 30px; }
  .logo-text { font-size: 28px; font-weight: 900; color: #1a3a5c; letter-spacing: 2px; }
  .subtitle { color: #666; font-size: 13px; margin: 5px 0; }
  .badge { background: #1a3a5c; color: #fff; padding: 8px 20px; font-size: 18px; font-weight: bold; letter-spacing: 3px; display: inline-block; margin: 10px 0; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 25px 0; }
  .info-item label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 4px; }
  .info-item span { font-size: 15px; color: #222; font-weight: 600; }
  .amount-box { background: #1a3a5c; color: #fff; text-align: center; padding: 25px; border-radius: 8px; margin: 25px 0; }
  .amount-box .label { font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 2px; }
  .amount-box .amount { font-size: 36px; font-weight: 900; margin: 8px 0; }
  .stamp { border: 3px solid #065f46; color: #065f46; display: inline-block; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 16px; transform: rotate(-5deg); margin: 10px; }
  .footer { text-align: center; color: #aaa; font-size: 11px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
  .watermark { position: fixed; top: 40%; left: 50%; transform: translate(-50%,-50%) rotate(-30deg); font-size: 80px; font-weight: 900; color: rgba(26,58,92,0.04); z-index: 0; pointer-events: none; }
  .btn-print { position: fixed; top: 20px; right: 20px; background: #1a3a5c; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 15px; z-index: 100; }
</style>
</head>
<body>
<div class='watermark'>MUST</div>
<button class='btn-print no-print' onclick='window.print()'>🖨️ Print Receipt</button>
<div class='receipt'>
  <div class='header'>
    <div class='logo-text'>MUST</div>
    <div class='subtitle'>Malawi University of Science and Technology</div>
    <div class='subtitle'>Hostel Accommodation Office</div>
    <div class='badge'>OFFICIAL RECEIPT</div>
  </div>
  
  <div class='info-grid'>
    <div class='info-item'>
      <label>Receipt Number</label>
      <span>{$receiptNum}</span>
    </div>
    <div class='info-item'>
      <label>Date Issued</label>
      <span>{$date}</span>
    </div>
    <div class='info-item'>
      <label>Student Name</label>
      <span>" . htmlspecialchars($student['full_name']) . "</span>
    </div>
    <div class='info-item'>
      <label>Registration Number</label>
      <span>" . htmlspecialchars($student['reg_number']) . "</span>
    </div>
    <div class='info-item'>
      <label>Invoice Reference</label>
      <span>" . htmlspecialchars($invoice['invoice_number']) . "</span>
    </div>
    <div class='info-item'>
      <label>Room Allocated</label>
      <span>" . htmlspecialchars($room) . "</span>
    </div>
    <div class='info-item'>
      <label>Payment Method</label>
      <span>" . ucfirst(str_replace('_', ' ', $payment['payment_method'])) . "</span>
    </div>
    <div class='info-item'>
      <label>Transaction ID</label>
      <span>" . htmlspecialchars($payment['transaction_id'] ?: 'N/A') . "</span>
    </div>
  </div>
  
  <div class='amount-box'>
    <div class='label'>Amount Paid</div>
    <div class='amount'>MWK {$amount}</div>
    <div style='font-size:12px;opacity:0.8'>Accommodation Fee - " . date('Y') . "/" . (date('Y')+1) . " Academic Year</div>
  </div>
  
  <div style='text-align:center'>
    <span class='stamp'>✓ PAYMENT VERIFIED</span>
  </div>
  
  <div class='footer'>
    <p>This is a computer-generated receipt and does not require a physical signature.</p>
    <p>MUST Hostel Office | Thyolo, Malawi | hostel@must.ac.mw | +265 1 999 000</p>
  </div>
</div>
</body>
</html>";
}

/**
 * Generate an invoice HTML page for a student billing record.
 *
 * @param array $invoice Invoice details including amount, due date, and status.
 * @param array $student Student profile details for billing information.
 * @return string Printable HTML markup for the invoice.
 */
function generateInvoiceHTML($invoice, $student) {
    $invoiceNum = $invoice['invoice_number'];
    $date = date('d F Y', strtotime($invoice['issued_at']));
    $due = date('d F Y', strtotime($invoice['due_date']));
    $amount = number_format($invoice['amount'], 2);
    $isPaid = $invoice['status'] === 'paid';
    
    return "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<title>Invoice - {$invoiceNum}</title>
<style>
  @media print { body { margin: 0; } .no-print { display: none; } }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; padding: 30px 0; }
  .invoice { background: #fff; width: 700px; padding: 50px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1a3a5c; padding-bottom: 20px; margin-bottom: 30px; }
  .logo-text { font-size: 32px; font-weight: 900; color: #1a3a5c; }
  .subtitle { color: #666; font-size: 12px; }
  .invoice-title { text-align: right; }
  .invoice-title h2 { color: #1a3a5c; font-size: 28px; margin: 0; }
  .invoice-title .inv-num { font-size: 14px; color: #888; }
  table { width: 100%; border-collapse: collapse; margin: 20px 0; }
  th { background: #1a3a5c; color: #fff; padding: 12px 15px; text-align: left; font-size: 13px; }
  td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 14px; }
  .total-row td { font-weight: bold; font-size: 16px; background: #f8fafc; }
  .status-paid { color: #065f46; background: #d1fae5; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
  .status-unpaid { color: #991b1b; background: #fee2e2; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
  .footer { color: #aaa; font-size: 11px; text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
  .btn-print { position: fixed; top: 20px; right: 20px; background: #1a3a5c; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 15px; }
</style>
</head>
<body>
<button class='btn-print no-print' onclick='window.print()'>🖨️ Print Invoice</button>
<div class='invoice'>
  <div class='header'>
    <div>
      <div class='logo-text'>MUST</div>
      <div class='subtitle'>Malawi University of Science and Technology<br>Hostel Accommodation Office<br>Thyolo, Malawi</div>
    </div>
    <div class='invoice-title'>
      <h2>INVOICE</h2>
      <div class='inv-num'>{$invoiceNum}</div>
      <div style='margin-top:10px'>
        " . ($isPaid ? "<span class='status-paid'>✓ PAID</span>" : "<span class='status-unpaid'>⚡ UNPAID</span>") . "
      </div>
    </div>
  </div>
  
  <div style='display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-bottom:30px'>
    <div>
      <div style='font-size:11px;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px'>Billed To</div>
      <strong>" . htmlspecialchars($student['full_name']) . "</strong><br>
      <span style='color:#666'>" . htmlspecialchars($student['reg_number']) . "</span><br>
      <span style='color:#666'>" . htmlspecialchars($student['email']) . "</span>
    </div>
    <div style='text-align:right'>
      <div style='font-size:11px;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px'>Details</div>
      <div><strong>Issue Date:</strong> {$date}</div>
      <div><strong>Due Date:</strong> {$due}</div>
      <div><strong>Academic Year:</strong> " . date('Y') . "/" . (date('Y')+1) . "</div>
    </div>
  </div>
  
  <table>
    <thead><tr><th>Description</th><th>Academic Year</th><th>Amount (MWK)</th></tr></thead>
    <tbody>
      <tr>
        <td>Hostel Accommodation Fee<br><small style='color:#888'>Full Semester - Single Room</small></td>
        <td>" . date('Y') . "/" . (date('Y')+1) . "</td>
        <td>{$amount}</td>
      </tr>
    </tbody>
    <tfoot>
      <tr class='total-row'>
        <td colspan='2' style='text-align:right'>Total Amount Due:</td>
        <td>MWK {$amount}</td>
      </tr>
    </tfoot>
  </table>
  
  <div style='background:#f8fafc;border-radius:8px;padding:20px;margin:20px 0'>
    <strong>Payment Instructions:</strong><br>
    <small>Bank: National Bank of Malawi | Account: 1234567890 | Branch: Thyolo<br>
    Mobile Money: Airtel Money: 0991 234 567 | TNM Mpamba: 0881 234 567<br>
    Reference: Your Registration Number</small>
  </div>
  
  <div class='footer'>
    <p>MUST Hostel Office | Thyolo, Malawi | hostel@must.ac.mw | +265 1 999 000</p>
    <p>Please upload your payment slip on the portal after payment.</p>
  </div>
</div>
</body>
</html>";
}
?>
