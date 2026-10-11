<?php

namespace App\Services;

use App\Models\Payment;
use TCPDF;

class ReceiptPdfService
{
    /**
     * Build the branded PDF receipt for a payment.
     */
    public function build(Payment $payment): TCPDF
    {
        $payment->loadMissing(['tenant.house']);

        $tenant = $payment->tenant;
        $house = $tenant->house;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor(config('app.name'));
        $pdf->SetTitle('Receipt - '.$payment->receipt_number);
        $pdf->SetSubject('Payment Receipt');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->AddPage();

        // Header
        $pdf->SetFillColor(30, 58, 95);
        $pdf->Rect(0, 0, 210, 45, 'F');

        $logoPath = public_path('images/logo.jpg');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 5, 25, 25, '', '', '', true, 300);
        }

        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(45, 8);
        $pdf->Cell(150, 8, config('app.name'), 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(45, 17);
        $pdf->Cell(150, 6, 'Payment Receipt', 0, 1, 'L');

        $pdf->SetFillColor(245, 130, 32);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(45, 25);
        $pdf->Cell(60, 8, 'RECEIPT #'.$payment->receipt_number, 0, 1, 'L', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);

        // Receipt info
        $this->sectionHeader($pdf, 'RECEIPT INFORMATION', 240, 244, 248, 30, 58, 95);

        $pdf->SetFont('helvetica', '', 10);
        $this->row($pdf, [
            ['Receipt Number:', $payment->receipt_number],
            ['Payment Date:', format_date($payment->payment_date)],
        ]);
        $this->row($pdf, [
            ['Recorded On:', $payment->created_at?->format('d M Y H:i') ?? '—'],
        ]);
        $pdf->Ln(5);

        // Tenant
        $this->sectionHeader($pdf, 'TENANT DETAILS', 30, 58, 95, 255, 255, 255);

        $this->row($pdf, [
            ['Full Name:', (string) $tenant->name],
            ['ID Number:', (string) $tenant->id_number],
        ]);
        $this->row($pdf, [
            ['Phone Number:', (string) $tenant->contact],
        ]);
        $this->row($pdf, [
            ['House Number:', (string) ($house->house_number ?? 'N/A')],
            ['Location:', (string) ($house->location ?? 'N/A')],
        ]);
        $pdf->Ln(5);

        // Payment
        $this->sectionHeader($pdf, 'PAYMENT DETAILS', 30, 58, 95, 255, 255, 255);

        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetX(20);
        $pdf->Cell(60, 8, 'Amount Paid:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(30, 58, 95);
        $pdf->Cell(0, 8, format_currency($payment->amount_paid), 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);

        if ($house?->rent_amount) {
            $previousPaid = (float) $tenant->payments()
                ->where('id', '!=', $payment->id)
                ->whereMonth('payment_date', $payment->payment_date->month)
                ->whereYear('payment_date', $payment->payment_date->year)
                ->sum('amount_paid');

            $balance = (float) $house->rent_amount - $previousPaid - (float) $payment->amount_paid;

            $this->row($pdf, [
                ['Monthly Rent:', format_currency($house->rent_amount)],
            ]);
            $this->row($pdf, [
                ['Paid Before (this month):', format_currency($previousPaid)],
            ]);
            $this->row($pdf, [
                ['Balance After Payment:', format_currency($balance)],
            ]);
        }

        if ($payment->notes) {
            $pdf->Ln(3);
            $this->sectionHeader($pdf, 'NOTES', 240, 244, 248, 30, 58, 95);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetX(20);
            $pdf->MultiCell(170, 6, $payment->notes, 0, 'L');
        }

        $pdf->Ln(15);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 6, 'This is a computer-generated receipt and is valid without a signature.', 0, 1, 'C');

        return $pdf;
    }

    private function sectionHeader(TCPDF $pdf, string $title, int $r, int $g, int $b, int $tr, int $tg, int $tb): void
    {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor($r, $g, $b);
        $pdf->SetTextColor($tr, $tg, $tb);
        $pdf->Cell(180, 8, '  '.$title, 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }

    /**
     * Draw a row of label/value pairs (each pair = label cell + value cell).
     *
     * @param  array<int, array{0: string, 1: string}>  $pairs
     */
    private function row(TCPDF $pdf, array $pairs): void
    {
        $pdf->SetX(20);

        foreach ($pairs as $pair) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(45, 6, $pair[0], 0, 0);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(50, 6, $pair[1], 0, 0);
        }

        $pdf->Ln();
    }
}
