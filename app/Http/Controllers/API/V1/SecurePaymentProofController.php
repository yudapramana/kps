<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SecurePaymentProofController extends Controller
{
    public function show(Payment $payment)
    {
        // ======================================================
        // 1️⃣ AUTHORIZATION (ADMIN / FINANCE ONLY)
        // ======================================================
        abort_if(
            ! in_array(auth()->user()->role->slug, ['admin', 'superadmin', 'finance']),
            403,
            'Unauthorized access'
        );

        // ======================================================
        // 2️⃣ VALIDATE FILE EXISTS
        // ======================================================
        abort_if(
            ! $payment->proof_file ||
            ! Storage::disk('private')->exists($payment->proof_file),
            404,
            'Payment proof not found'
        );

        // ======================================================
        // 3️⃣ STREAM IMAGE (NO DOWNLOAD)
        // ======================================================
        $filePath = $payment->proof_file;
        $mime     = Storage::disk('private')->mimeType($filePath);

        return response(
            Storage::disk('private')->get($filePath),
            200,
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }
}
