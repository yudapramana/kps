<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminSecureFileController extends Controller
{
    /**
     * GET SIGNED URL payment proof dari Customer App
     */
    // public function paymentProof(int $paymentId)
    // {
    //     // ==================================================
    //     // 1️⃣ Ambil service token (auto decrypt)
    //     // ==================================================
    //     $service = ServiceAccount::where('name', 'Customer API')->firstOrFail();
    //     $token   = $service->token;

    //     Log::info('[AdminSecureFile] Using service token', [
    //         'service' => $service->name,
    //         'payment_id' => $paymentId,
    //     ]);

    //     // ==================================================
    //     // 2️⃣ Call Customer API
    //     // ==================================================
    //     $customerApi = rtrim(config('services.customer_api.url'), '/');

    //     $response = Http::withToken($token)
    //         ->acceptJson()
    //         ->get(
    //             $customerApi . '/api/secure/payments/proof/' . $paymentId . '/signed'
    //         );

    //     // ==================================================
    //     // 3️⃣ LOG RESPONSE (INI YANG KAMU MINTA)
    //     // ==================================================
    //     Log::info('[AdminSecureFile] Customer API response', [
    //         'url'        => $customerApi . '/api/secure/payments/proof/' . $paymentId . '/signed',
    //         'status'     => $response->status(),
    //         'successful' => $response->successful(),
    //         'headers'    => [
    //             'content-type' => $response->header('Content-Type'),
    //         ],
    //         'body'       => $response->body(), // ⚠️ aman karena hanya signed URL
    //     ]);

    //     // ==================================================
    //     // 4️⃣ HANDLE ERROR
    //     // ==================================================
    //     if (! $response->successful()) {
    //         abort(
    //             $response->status(),
    //             'Failed to get signed payment proof URL'
    //         );
    //     }

    //     // ==================================================
    //     // 5️⃣ RETURN KE FRONTEND
    //     // ==================================================
    //     return response()->json($response->json());
    // }

    // public function paymentProof(int $paymentId)
    // {
    //     $service = ServiceAccount::where('name', 'Customer API')->firstOrFail();

    //     \Log::info('Using service token', [
    //         'token_preview' => substr($service->token, 0, 10) . '***'
    //     ]);


    //     // DEBUG OPTIONAL
    //     abort_if(! $service->token, 500, 'Service token missing');

    //     $response = Http::withToken($service->token)->get(
    //         config('services.customer_api.url')
    //         . "/api/secure/payments/proof/{$paymentId}/stream"
    //     );

    //     abort_if(! $response->successful(), 403, 'Customer API rejected');

    //     $contentType = $response->header('Content-Type') ?? 'image/jpeg';
    //     $body        = $response->body();

    //     abort_if(empty($body), 500, 'Empty image stream from customer');

    //     return response($body, 200, [
    //         'Content-Type'  => $contentType,
    //         'Cache-Control' => 'no-store',
    //     ]);
    // }

    public function paymentProof(int $paymentId)
    {
        $service = ServiceAccount::where('name', 'Customer API')->firstOrFail();

        abort_if(! $service->token, 500, 'Service token missing');

        $baseUrl = rtrim(config('services.customer_api.url'), '/');
        $secret  = 'kelakaronly'; // sebaiknya pindah ke env/config

        /*
        |--------------------------------------------------------------------------
        | PRE-FLIGHT CALL (BYPASS MAINTENANCE MODE)
        |--------------------------------------------------------------------------
        | Laravel maintenance secret HARUS lewat path: /{secret}
        */

        \Log::info('Start Using preflight', [
            'url' => "{$baseUrl}/{$secret}"
        ]);


        $preflight = Http::get("{$baseUrl}/{$secret}");

        if ($preflight->status() === 503) {
            abort(503, 'Customer API in maintenance mode');
        }

        \Log::info('Customer API maintenance bypass success');

        /*
        |--------------------------------------------------------------------------
        | MAIN REQUEST (TOKEN AUTH)
        |--------------------------------------------------------------------------
        */
        \Log::info('Using service token', [
            'token_preview' => substr($service->token, 0, 10) . '***'
        ]);

        $response = Http::withToken($service->token)->get(
            "{$baseUrl}/api/secure/payments/proof/{$paymentId}/stream"
        );

        abort_if(! $response->successful(), 403, 'Customer API rejected');

        $contentType = $response->header('Content-Type') ?? 'image/jpeg';
        $body        = $response->body();

        abort_if(empty($body), 500, 'Empty image stream from customer');

        return response($body, 200, [
            'Content-Type'  => $contentType,
            'Cache-Control' => 'no-store',
        ]);
    }





}
