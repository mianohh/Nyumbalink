<?php

namespace App\Services;

use App\Models\House;
use App\Models\Payment;
use App\Models\Tenant;
use TCPDF;

class ReceiptPdfService
{
    /**
     * Page geometry (A4 portrait, millimetres).
     */
    public const LEFT = 15.0;

    public const RIGHT = 195.0;

    public const WIDTH = 180.0;

    /**
     * Brand palette sampled from the Nyumbalink logo.
     */
    public const NAVY = [15, 27, 67];

    public const ACCENT = [245, 130, 32];

    public const INK = [23, 23, 26];

    public const MUTED = [107, 114, 128];

    public const SURFACE = [246, 247, 249];

    public const LINE = [229, 231, 235];

    public const TINT = [236, 240, 247];

    /**
     * Build the branded PDF receipt for a payment.
     */
    public function build(Payment $payment): TCPDF
    {
        $payment->loadMissing(['tenant.house']);

        $tenant = $payment->tenant;
        $house = $tenant->house;

        $pdf = $this->newPdf($payment);

        $pdf->SetCreator((string) config('app.name'));
        $pdf->SetAuthor((string) config('app.name'));
        $pdf->SetTitle('Receipt - '.$payment->receipt_number);
        $pdf->SetSubject('Payment Receipt');
        $pdf->SetKeywords('payment receipt, rent, '.$payment->receipt_number);

        // Content starts below the 46 mm brand band drawn by Header().
        $pdf->SetMargins(self::LEFT, 50, 15);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetCellPadding(0);

        $pdf->AddPage();

        $this->referenceStrip($pdf, $payment);
        $this->parties($pdf, $payment, $tenant, $house);
        $this->amountPaid($pdf, $payment);

        if ($house !== null && $house->rent_amount) {
            $this->rentSummary($pdf, $payment, $tenant, $house);
        }

        if ($payment->notes) {
            $this->notes($pdf, $payment);
        }

        $this->closing($pdf);

        return $pdf;
    }

    /**
     * Draw the full-width brand band at the top of every page.
     */
    public function renderHeader(TCPDF $pdf, Payment $payment): void
    {
        $pdf->SetFillColor(...self::NAVY);
        $pdf->Rect(0, 0, 210, 44, 'F');

        $pdf->SetFillColor(...self::ACCENT);
        $pdf->Rect(0, 44, 210, 2, 'F');

        $logoPath = public_path('images/logo.jpg');
        if (file_exists($logoPath)) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->RoundedRect(self::LEFT, 8, 34, 27, 2, '1111', 'F');
            $pdf->Image($logoPath, 17, 10.25, 30, 22.5, '', '', '', true, 300);
        }

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(55, 11);
        $pdf->Cell(80, 8, (string) config('app.name'), 0, 0, 'L');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(176, 186, 210);
        $pdf->SetXY(55, 21.5);
        $pdf->Cell(80, 6, 'Rental Property Management', 0, 0, 'L');

        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(95, 11);
        $pdf->Cell(100, 8, 'PAYMENT RECEIPT', 0, 0, 'R');

        $pdf->SetFont('helvetica', 'B', 9);
        $chipWidth = $pdf->GetStringWidth($payment->receipt_number) + 10;
        $chipX = self::RIGHT - $chipWidth;
        $pdf->SetFillColor(...self::ACCENT);
        $pdf->RoundedRect($chipX, 21.5, $chipWidth, 8, 1.5, '1111', 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($chipX, 21.5);
        $pdf->Cell($chipWidth, 8, $payment->receipt_number, 0, 0, 'C', false, '', 0, false, 'T', 'M');

        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(176, 186, 210);
        $pdf->SetXY(95, 32.5);
        $pdf->Cell(100, 5, 'Issued '.format_date($payment->created_at), 0, 0, 'R');
    }

    /**
     * Draw the page footer: rule, receipt reference and page numbers.
     */
    public function renderFooter(TCPDF $pdf, Payment $payment): void
    {
        $y = $pdf->getPageHeight() - 12;

        $pdf->SetDrawColor(...self::LINE);
        $pdf->Line(self::LEFT, $y, self::RIGHT, $y);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(...self::MUTED);

        $pdf->SetXY(self::LEFT, $y + 2);
        $pdf->Cell(110, 5, (string) config('app.name').' · Receipt '.$payment->receipt_number, 0, 0, 'L');

        $pdf->SetXY(self::LEFT + 110, $y + 2);
        $pdf->Cell(70, 5, 'Page '.$pdf->getAliasNumPage().' of '.$pdf->getAliasNbPages(), 0, 0, 'R');
    }

    /**
     * Create the TCPDF instance wired to the branded header and footer.
     */
    private function newPdf(Payment $payment): TCPDF
    {
        return new class($this, $payment) extends TCPDF
        {
            public function __construct(
                private readonly ReceiptPdfService $receipts,
                private readonly Payment $payment,
            ) {
                parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
            }

            public function Header(): void
            {
                $this->receipts->renderHeader($this, $this->payment);
            }

            public function Footer(): void
            {
                $this->receipts->renderFooter($this, $this->payment);
            }
        };
    }

    /**
     * Three-column reference strip: receipt number, payment date, recorded timestamp.
     */
    private function referenceStrip(TCPDF $pdf, Payment $payment): void
    {
        $y = $pdf->GetY();

        $pdf->SetFillColor(...self::SURFACE);
        $pdf->Rect(self::LEFT, $y, self::WIDTH, 13, 'F');

        $pdf->SetDrawColor(...self::LINE);
        $pdf->Line(75, $y + 2.5, 75, $y + 10.5);
        $pdf->Line(135, $y + 2.5, 135, $y + 10.5);

        $fields = [
            [self::LEFT, 'RECEIPT NO.', $payment->receipt_number],
            [75.0, 'PAYMENT DATE', format_date($payment->payment_date)],
            [135.0, 'RECORDED', $payment->created_at?->format('d M Y H:i') ?? '—'],
        ];

        foreach ($fields as [$x, $label, $value]) {
            $pdf->SetFont('helvetica', 'B', 7.5);
            $pdf->SetTextColor(...self::MUTED);
            $pdf->SetXY($x + 4, $y + 2.5);
            $pdf->Cell(50, 4, $label, 0, 0, 'L');

            $pdf->SetFont('helvetica', 'B', 10.5);
            $pdf->SetTextColor(...self::INK);
            $pdf->SetXY($x + 4, $y + 6.5);
            $pdf->Cell(50, 5, $value, 0, 0, 'L');
        }

        $pdf->SetY($y + 13);
    }

    /**
     * Two aligned cards: who paid, and which property the payment covers.
     */
    private function parties(TCPDF $pdf, Payment $payment, Tenant $tenant, ?House $house): void
    {
        $y = $pdf->GetY() + 4;
        $width = 87.0;

        $this->card($pdf, self::LEFT, $y, $width, 34, 'RECEIVED FROM', (string) $tenant->name, [
            ['ID Number', (string) $tenant->id_number],
            ['Phone', (string) $tenant->contact],
        ]);

        $this->card($pdf, self::LEFT + $width + 6, $y, $width, 34, 'PROPERTY', $house instanceof House ? $house->house_number : 'Not assigned', [
            ['Location', $house instanceof House ? $house->location : 'N/A'],
            ['Rent period', $payment->payment_date->format('F Y')],
        ]);

        $pdf->SetY($y + 34);
    }

    /**
     * Draw a light information card with a title, headline and label/value rows.
     *
     * @param  array<int, array{0: string, 1: string}>  $rows
     */
    private function card(TCPDF $pdf, float $x, float $y, float $width, float $height, string $label, string $headline, array $rows): void
    {
        $pdf->SetDrawColor(...self::LINE);
        $pdf->SetFillColor(...self::SURFACE);
        $pdf->Rect($x, $y, $width, $height, 'DF');

        $pad = 4.0;
        $inner = $width - 2 * $pad;

        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetXY($x + $pad, $y + 3.5);
        $pdf->Cell($inner, 4, strtoupper($label), 0, 0, 'L');

        $pdf->SetFont('helvetica', 'B', 11.5);
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetXY($x + $pad, $y + 8.5);
        $pdf->Cell($inner, 6, $headline, 0, 0, 'L');

        $rowY = $y + 17;
        foreach ($rows as [$key, $value]) {
            $pdf->SetFont('helvetica', '', 8.5);
            $pdf->SetTextColor(...self::MUTED);
            $pdf->SetXY($x + $pad, $rowY);
            $pdf->Cell(30, 6, $key, 0, 0, 'L');

            $pdf->SetFont('helvetica', 'B', 9.5);
            $pdf->SetTextColor(...self::INK);
            $pdf->SetXY($x + $pad + 30, $rowY);
            $pdf->Cell($inner - 30, 6, $value, 0, 0, 'L');

            $rowY += 7;
        }
    }

    /**
     * The hero element: amount paid, set against the brand navy panel.
     */
    private function amountPaid(TCPDF $pdf, Payment $payment): void
    {
        $y = $pdf->GetY() + 4;
        $height = 27.0;

        $pdf->SetFillColor(...self::NAVY);
        $pdf->Rect(self::LEFT, $y, self::WIDTH, $height, 'F');

        $pdf->SetFillColor(...self::ACCENT);
        $pdf->Rect(self::LEFT, $y, 4, $height, 'F');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(150, 165, 200);
        $pdf->SetXY(24, $y + 8);
        $pdf->Cell(70, 5, 'AMOUNT PAID', 0, 0, 'L');

        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->SetTextColor(214, 221, 236);
        $pdf->SetXY(24, $y + 14);
        $pdf->Cell(70, 6, 'Received on '.format_date($payment->payment_date), 0, 0, 'L');

        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(96, $y + 3);
        $pdf->Cell(95, $height - 6, format_currency($payment->amount_paid), 0, 0, 'R', false, '', 0, false, 'T', 'M');

        $pdf->SetY($y + $height);
    }

    /**
     * Aligned description/amount table ending on the balance after payment.
     */
    private function rentSummary(TCPDF $pdf, Payment $payment, Tenant $tenant, House $house): void
    {
        $previousPaid = (float) $tenant->payments()
            ->where('id', '!=', $payment->id)
            ->whereMonth('payment_date', $payment->payment_date->month)
            ->whereYear('payment_date', $payment->payment_date->year)
            ->sum('amount_paid');

        $balance = (float) $house->rent_amount - $previousPaid - (float) $payment->amount_paid;

        $y = $pdf->GetY() + 5;

        $pdf->SetFillColor(238, 240, 244);
        $pdf->Rect(self::LEFT, $y, self::WIDTH, 7, 'F');

        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetXY(19, $y);
        $pdf->Cell(95, 7, 'DESCRIPTION', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->SetXY(120, $y);
        $pdf->Cell(72, 7, 'AMOUNT', 0, 0, 'R', false, '', 0, false, 'T', 'M');

        $y += 7;

        $rows = [
            ['Monthly rent', format_currency($house->rent_amount), false],
            ['Paid before this month', format_currency($previousPaid), false],
            ['Balance after payment', format_currency($balance), true],
        ];

        foreach ($rows as [$description, $amount, $isTotal]) {
            if ($isTotal) {
                $pdf->SetFillColor(...self::TINT);
                $pdf->Rect(self::LEFT, $y, self::WIDTH, 8, 'F');
            }

            $pdf->SetFont('helvetica', $isTotal ? 'B' : '', $isTotal ? 10 : 9.5);
            if ($isTotal) {
                $pdf->SetTextColor(...self::NAVY);
            } else {
                $pdf->SetTextColor(...self::INK);
            }

            $pdf->SetXY(19, $y);
            $pdf->Cell(95, 8, $description, 0, 0, 'L', false, '', 0, false, 'T', 'M');
            $pdf->SetXY(120, $y);
            $pdf->Cell(72, 8, $amount, 0, 0, 'R', false, '', 0, false, 'T', 'M');

            $pdf->SetDrawColor(...self::LINE);
            $pdf->Line(self::LEFT, $y + 8, self::RIGHT, $y + 8);

            $y += 8;
        }

        $pdf->SetY($y);
    }

    /**
     * Optional notes block on a light panel.
     */
    private function notes(TCPDF $pdf, Payment $payment): void
    {
        $y = $pdf->GetY() + 5;

        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetXY(self::LEFT, $y);
        $pdf->Cell(self::WIDTH, 5, 'NOTES', 0, 0, 'L');

        $pdf->SetCellPadding(2.5);
        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->SetTextColor(...self::INK);
        $pdf->SetFillColor(...self::SURFACE);
        $pdf->SetDrawColor(...self::LINE);
        $pdf->MultiCell(self::WIDTH, 6, (string) $payment->notes, 1, 'L', true, 1, self::LEFT, $y + 5.5, true, 0, false, true, 0, 'T');
        $pdf->SetCellPadding(0);
    }

    /**
     * Closing acknowledgement, validity disclaimer and optional contact line.
     */
    private function closing(TCPDF $pdf): void
    {
        $y = $pdf->GetY() + 8;

        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetXY(self::LEFT, $y);
        $pdf->Cell(self::WIDTH, 6, 'Thank you for your payment.', 0, 0, 'L');

        $pdf->SetFont('helvetica', 'I', 8.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetXY(self::LEFT, $y + 6.5);
        $pdf->Cell(self::WIDTH, 5, 'This is a computer-generated receipt and is valid without a signature.', 0, 0, 'L');

        $contact = (string) config('nyumbalink.receipt.contact');
        if ($contact !== '') {
            $pdf->SetFont('helvetica', '', 8.5);
            $pdf->SetXY(self::LEFT, $y + 12);
            $pdf->Cell(self::WIDTH, 5, 'Queries: '.$contact, 0, 0, 'L');
        }
    }
}
