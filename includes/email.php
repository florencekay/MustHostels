<?php
require_once __DIR__ . '/config.php';

function emailTemplate($title, $content, $color = '#1a3a5c') {
    return "
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
  .header { background: {$color}; padding: 30px; text-align: center; }
  .header img { width: 60px; margin-bottom: 10px; }
  .header h1 { color: #fff; margin: 0; font-size: 22px; letter-spacing: 1px; }
  .header p { color: rgba(255,255,255,0.8); margin: 5px 0 0; font-size: 13px; }
  .body { padding: 35px 40px; color: #333; line-height: 1.7; }
  .body h2 { color: {$color}; margin-top: 0; }
  .info-box { background: #f8fafc; border-left: 4px solid {$color}; padding: 15px 20px; border-radius: 4px; margin: 20px 0; }
  .info-box p { margin: 5px 0; font-size: 14px; }
  .info-box strong { color: {$color}; }
  .badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; margin: 10px 0; }
  .badge-success { background: #d1fae5; color: #065f46; }
  .badge-danger { background: #fee2e2; color: #991b1b; }
  .badge-warning { background: #fef3c7; color: #92400e; }
  .footer { background: #f8fafc; padding: 20px 40px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; }
  .btn { display: inline-block; background: {$color}; color: #fff !important; padding: 12px 28px; border-radius: 5px; text-decoration: none; font-weight: bold; margin: 15px 0; }
</style>
</head>
<body>
<div class='wrapper'>
  <div class='header'>
    <h1>🏫 MUST Hostel Office</h1>
    <p>Malawi University of Science and Technology</p>
  </div>
  <div class='body'>
    <h2>{$title}</h2>
    {$content}
  </div>
  <div class='footer'>
    <p>This is an automated message from MUST Hostel Booking System.<br>
    Do not reply to this email. For queries, visit the hostel office or log in to the system.</p>
    <p>&copy; " . date('Y') . " Malawi University of Science and Technology. All rights reserved.</p>
  </div>
</div>
</body>
</html>";
}

function sendInvoiceEmail($studentEmail, $studentName, $invoiceNumber, $amount, $dueDate) {
    $content = "
    <p>Dear <strong>{$studentName}</strong>,</p>
    <p>Your hostel room application has been received. Please find your accommodation invoice details below.</p>
    <div class='info-box'>
        <p><strong>Invoice Number:</strong> {$invoiceNumber}</p>
        <p><strong>Amount Due:</strong> MWK " . number_format($amount, 2) . "</p>
        <p><strong>Due Date:</strong> {$dueDate}</p>
        <p><strong>Payment Methods:</strong> Bank Transfer, Mobile Money (Airtel/TNM)</p>
    </div>
    <p>After making payment, please log into the MUST Hostel System and upload your payment slip or transaction ID screenshot to confirm your payment.</p>
    <p><strong>Note:</strong> Room allocation is subject to available spaces and will be processed after payment verification.</p>
    <p>Regards,<br><strong>MUST Hostel Office</strong></p>
    ";
    return sendEmail($studentEmail, "Hostel Accommodation Invoice - {$invoiceNumber}", emailTemplate("Invoice: {$invoiceNumber}", $content), 'invoice');
}

function sendReceiptEmail($studentEmail, $studentName, $receiptNumber, $amountPaid, $roomInfo) {
    $content = "
    <p>Dear <strong>{$studentName}</strong>,</p>
    <p>Your payment has been verified and a receipt has been generated. <span class='badge badge-success'>✓ Payment Verified</span></p>
    <div class='info-box'>
        <p><strong>Receipt Number:</strong> {$receiptNumber}</p>
        <p><strong>Amount Paid:</strong> MWK " . number_format($amountPaid, 2) . "</p>
        <p><strong>Room Assigned:</strong> {$roomInfo}</p>
        <p><strong>Academic Year:</strong> " . date('Y') . "/" . (date('Y')+1) . "</p>
    </div>
    <p>Please keep this receipt for your records. You may also log into the system to download a PDF copy.</p>
    <p>Regards,<br><strong>MUST Hostel Office</strong></p>
    ";
    return sendEmail($studentEmail, "Payment Receipt - {$receiptNumber}", emailTemplate("Receipt Issued", $content, '#065f46'), 'receipt');
}

function sendAllocationEmail($studentEmail, $studentName, $roomInfo, $hallInfo) {
    $content = "
    <p>Dear <strong>{$studentName}</strong>,</p>
    <p>Congratulations! You have been successfully allocated a hostel room for the upcoming academic year. <span class='badge badge-success'>✓ Allocated</span></p>
    <div class='info-box'>
        <p><strong>Hall/Block:</strong> {$hallInfo}</p>
        <p><strong>Room:</strong> {$roomInfo}</p>
        <p><strong>Academic Year:</strong> " . date('Y') . "/" . (date('Y')+1) . "</p>
    </div>
    <p>Please ensure your accommodation fee is paid before the due date to secure your room. Unpaid rooms may be reallocated.</p>
    <p>Regards,<br><strong>MUST Hostel Office</strong></p>
    ";
    return sendEmail($studentEmail, "Room Allocation Successful - MUST Hostel", emailTemplate("Room Allocation Notice", $content, '#1a3a5c'), 'allocation');
}

function sendRejectionEmail($studentEmail, $studentName) {
    $content = "
    <p>Dear <strong>{$studentName}</strong>,</p>
    <p>We regret to inform you that you have not been allocated a hostel room this semester due to limited capacity. <span class='badge badge-danger'>Not Allocated</span></p>
    <div class='info-box'>
        <p><strong>Reason:</strong> All available hostel spaces have been filled.</p>
        <p><strong>Status:</strong> You have been placed on the waiting list.</p>
    </div>
    <p>If a space becomes available, you will be notified immediately. You may also contact the hostel office for alternative accommodation arrangements.</p>
    <p>We apologize for any inconvenience caused.</p>
    <p>Regards,<br><strong>MUST Hostel Office</strong></p>
    ";
    return sendEmail($studentEmail, "Hostel Allocation Update - MUST", emailTemplate("Allocation Status", $content, '#991b1b'), 'rejection');
}

function sendInquiryResponseEmail($studentEmail, $studentName, $subject, $response) {
    $content = "
    <p>Dear <strong>{$studentName}</strong>,</p>
    <p>Your inquiry has been reviewed and a response has been provided.</p>
    <div class='info-box'>
        <p><strong>Subject:</strong> {$subject}</p>
        <p><strong>Response:</strong></p>
        <p>{$response}</p>
    </div>
    <p>If you have further questions, please submit a new inquiry through the hostel portal.</p>
    <p>Regards,<br><strong>MUST Hostel Office</strong></p>
    ";
    return sendEmail($studentEmail, "Response to Your Inquiry - MUST Hostel", emailTemplate("Inquiry Response", $content, '#7c3aed'), 'inquiry_response');
}
?>
