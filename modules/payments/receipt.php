<?php
require_once __DIR__ . '/../../includes/core.php';
require_once __DIR__ . '/../../vendor/autoload.php';
requireAuth();



$db = getDB();
$paymentId = (int)($_GET['id'] ?? 0);
$download = isset($_GET['download']);

if (!$paymentId) {
    setFlash('error', 'Invalid payment ID.');
    redirect(url('modules/payments/index.php'));
}

// Get payment details with full balance info
$stmt = $db->prepare("
    SELECT p.*, t.name as tenant_name, t.contact as tenant_contact, t.id_number,
           h.house_number, h.location, h.rent_amount
    FROM payments p 
    JOIN tenants t ON p.tenant_id = t.tenant_id 
    LEFT JOIN houses h ON t.house_id = h.house_id 
    WHERE p.payment_id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    setFlash('error', 'Payment not found.');
    redirect(url('modules/payments/index.php'));
}

// Calculate balance
$balance = 0;
if ($payment['rent_amount']) {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(amount_paid), 0) 
        FROM payments 
        WHERE tenant_id = ? AND MONTH(payment_date) = MONTH(?) 
        AND YEAR(payment_date) = YEAR(?) AND payment_id != ?
    ");
    $stmt->execute([$payment['tenant_id'], $payment['payment_date'], $payment['payment_date'], $paymentId]);
    $previousPaid = $stmt->fetchColumn();
    $balance = $payment['rent_amount'] - $previousPaid - $payment['amount_paid'];
}

// Create PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Receipt - ' . $payment['receipt_number']);
$pdf->SetSubject('Payment Receipt');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

$pdf->AddPage();

// === HEADER ===
$pdf->SetFillColor(30, 58, 95); // Navy blue from logo
$pdf->Rect(0, 0, 210, 45, 'F');

// Logo
$logoPath = __DIR__ . '/../../assets/images/logo.jpg';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 5, 25, 25, '', '', '', true, 300);
}

$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(45, 8);
$pdf->Cell(150, 8, APP_NAME, 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(45, 17);
$pdf->Cell(150, 6, 'Payment Receipt', 0, 1, 'L');

// Receipt number badge
$pdf->SetFillColor(245, 130, 32); // Orange from Nyumbalink logo
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(45, 25);
$pdf->Cell(60, 8, 'RECEIPT #' . $payment['receipt_number'], 0, 1, 'L', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(10);

// === RECEIPT INFO ===
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(240, 244, 248);
$pdf->Cell(180, 8, '  RECEIPT INFORMATION', 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);

// Row 1
$pdf->SetX(20);
$pdf->Cell(45, 6, 'Receipt Number:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, $payment['receipt_number'], 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(35, 6, 'Payment Date:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, date('d M Y', strtotime($payment['payment_date'])), 0, 1);

// Row 2
$pdf->SetX(20);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(45, 6, 'Recorded On:', 0, 0);
$pdf->Cell(0, 6, date('d M Y H:i', strtotime($payment['created_at'])), 0, 1);

$pdf->Ln(5);

// === TENANT DETAILS ===
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(30, 58, 95); // Navy blue from logo
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(180, 8, '  TENANT DETAILS', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);

// Tenant info in two columns
$pdf->SetX(20);
$pdf->Cell(45, 6, 'Full Name:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(55, 6, $payment['tenant_name'], 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(35, 6, 'ID Number:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $payment['id_number'], 0, 1);

$pdf->SetX(20);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(45, 6, 'Phone Number:', 0, 0);
$pdf->Cell(0, 6, $payment['tenant_contact'], 0, 1);

$pdf->SetX(20);
$pdf->Cell(45, 6, 'House Number:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(55, 6, $payment['house_number'] ?? 'N/A', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(35, 6, 'Location:', 0, 0);
$pdf->Cell(0, 6, $payment['location'] ?? 'N/A', 0, 1);

$pdf->Ln(5);

// === PAYMENT DETAILS ===
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(245, 130, 32); // Orange from Nyumbalink logo
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(180, 8, '  PAYMENT DETAILS', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);

// Payment breakdown
$pdf->SetX(20);
$pdf->Cell(100, 6, 'Monthly Rent Amount:', 0, 0);
$pdf->Cell(0, 6, formatCurrency($payment['rent_amount'] ?? 0), 0, 1);

if ($previousPaid > 0) {
    $pdf->SetX(20);
    $pdf->Cell(100, 6, 'Previously Paid This Month:', 0, 0);
    $pdf->Cell(0, 6, formatCurrency($previousPaid), 0, 1);
}

$pdf->SetX(20);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(30, 58, 95); // Navy blue from logo
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(100, 10, '  AMOUNT PAID:', 0, 0, 'L', true);
$pdf->Cell(80, 10, formatCurrency($payment['amount_paid']), 0, 1, 'R', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Ln(2);

// Balance
if ($payment['rent_amount']) {
    $pdf->SetX(20);
    $pdf->Cell(100, 6, 'Remaining Balance:', 0, 0);
    $balanceColor = $balance > 0 ? [231, 76, 60] : [39, 174, 96];
    $pdf->SetTextColor(...$balanceColor);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, formatCurrency($balance), 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
}

$pdf->Ln(3);

// Notes
if (!empty($payment['notes'])) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetX(20);
    $pdf->Cell(0, 6, 'Notes:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetX(20);
    $pdf->MultiCell(160, 6, $payment['notes']);
    $pdf->Ln(3);
}

// === FOOTER ===
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Signature line
$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(20);
$pdf->Cell(70, 5, 'Received By: ___________________', 0, 0);
$pdf->Cell(50, 5, '', 0, 0);
$pdf->Cell(0, 5, 'Authorized Signature: ___________________', 0, 1);

$pdf->Ln(8);

// Disclaimer
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 4, 'This is a computer-generated receipt. No signature is required for this document.', 0, 1, 'C');
$pdf->Cell(0, 4, 'For any queries, please contact ' . APP_NAME . '.', 0, 1, 'C');
$pdf->Cell(0, 4, 'Generated on: ' . date('d M Y H:i:s') . ' | ' . APP_NAME . ' v' . APP_VERSION, 0, 1, 'C');

// Output PDF
$filename = 'Receipt_' . $payment['receipt_number'] . '.pdf';
if ($download) {
    $pdf->Output($filename, 'D');
} else {
    $pdf->Output($filename, 'I');
}
