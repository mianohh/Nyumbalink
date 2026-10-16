<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\ReceiptPdfService;
use Symfony\Component\HttpFoundation\Response;

class PaymentReceiptController extends Controller
{
    public function __invoke(Payment $payment, ReceiptPdfService $service): Response
    {
        $this->authorize('view', $payment);

        $pdf = $service->build($payment);

        $filename = $payment->receipt_number.'.pdf';
        $disposition = request()->boolean('download') ? 'attachment' : 'inline';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }
}
