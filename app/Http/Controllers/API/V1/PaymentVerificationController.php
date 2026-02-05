<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentVerificationController extends Controller
{
    /**
     * LIST PAYMENT PENDING (QUEUE)
     */
    public function queue(Request $request)
    {
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Payment::with([
            'registration.participant:id,full_name,email',
            'registration.bank:id,name,account_name',
        ])
        ->where('status', 'pending')
        ->orderBy('created_at');

        if ($request->search) {
            $query->whereHas('registration.participant', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * VERIFY PAYMENT (APPROVE / REJECT)
     */
    public function verify(Request $request, Payment $payment)
    {
        return DB::transaction(function () use ($request, $payment) {

            // LOCK ROW
            $payment = Payment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($payment->status !== 'pending', 409, 'Payment already processed');

            $validated = $request->validate([
                'action' => 'required|in:approve,reject',
                'notes'  => 'nullable|string',
            ]);

            // SIMPAN AUDIT LOG VERIFIKASI
            PaymentVerification::create([
                'payment_id'  => $payment->id,
                'verified_by' => Auth::id(),
                'action'      => $validated['action'], // ✅ TAMBAHAN
                'verified_at' => now(),
                'notes'       => $validated['notes'] ?? null,
            ]);

            if ($validated['action'] === 'approve') {
                $payment->update([
                    'status'  => 'verified',
                    'paid_at' => now(),
                ]);

                $payment->registration->update([
                    'status'       => 'paid',
                    'payment_step' => 'paid',
                ]);
            } else {
                $payment->update([
                    'status' => 'rejected',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Verifikasi pembayaran berhasil',
            ]);
        });
    }


    /**
     * VERIFICATION HISTORY (PAYMENT BASED)
     */
    public function history(Request $request)
    {
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Payment::with([
            'registration.participant:id,full_name,email',
            'registration.bank:id,name',
            'verifications:id,payment_id,verified_by,action,verified_at,notes',
            'verifications.verifiedBy:id,name',
        ])
        ->whereHas('verifications')
        ->orderByDesc(
            PaymentVerification::select('verified_at')
                ->whereColumn('payment_id', 'payments.id')
                ->latest()
                ->limit(1)
        );

        if ($request->admin) {
            $query->whereHas('verifications.verifiedBy', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->admin}%");
            });
        }

        if ($request->date) {
            $query->whereHas('verifications', function ($q) use ($request) {
                $q->whereDate('verified_at', $request->date);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }



}
