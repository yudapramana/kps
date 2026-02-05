<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Payment::with([
            'registration.event:id,name',
            'registration.participant:id,full_name,email',
            'registration.bank:id,name',
        ])->orderByDesc('created_at');

        /* ================= FILTER ================= */

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->bank_id) {
            $query->whereHas('registration', function ($q) use ($request) {
                $q->where('bank_id', $request->bank_id);
            });
        }

        if ($request->date_from && $request->date_to) {
            $query->whereBetween('created_at', [
                $request->date_from . ' 00:00:00',
                $request->date_to   . ' 23:59:59',
            ]);
        }

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


    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:pending,verified,rejected',
        ]);

        $payment->update([
            'status' => $request->status,
            'paid_at' => $request->status === 'verified'
                ? now()
                : null,
        ]);

        // sinkron ke registration
        if ($request->status === 'verified') {
            $payment->registration->update([
                'status' => 'paid',
                'payment_step' => 'paid',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran diperbarui',
        ]);
    }
}
