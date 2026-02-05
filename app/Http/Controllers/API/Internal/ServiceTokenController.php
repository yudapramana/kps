<?php

namespace App\Http\Controllers\API\Internal;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ServiceTokenController extends Controller
{
    public function sync(Request $request)
    {
        return DB::transaction(function () {

            // ==================================================
            // 1️⃣ CEK APAKAH TOKEN SUDAH ADA
            // ==================================================
            $service = ServiceAccount::where('name', 'Customer API')->first();

            if ($service && $service->encrypted_token) {
                return response()->json([
                    'success' => true,
                    'message' => 'Service token already exists',
                ]);
            }

            // ==================================================
            // 2️⃣ REQUEST TOKEN KE CUSTOMER APP
            // ==================================================
            $response = Http::timeout(10)->post(
                config('services.customer_api.url') . '/api/internal/service-token',
                [
                    'service'   => 'admin-app',
                    'abilities' => ['read-payment-proof'],
                ]
            );

            if (! $response->successful()) {
                abort(500, 'Failed to retrieve service token from Customer App');
            }

            $plainToken = $response->json('token');

            // ==================================================
            // 3️⃣ SIMPAN TOKEN (ENCRYPTED)
            // ==================================================
            ServiceAccount::updateOrCreate(
                ['name' => 'Customer API'],
                [
                    'encrypted_token' => encrypt($plainToken),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Service token created and stored securely',
            ]);
        });
    }
}
