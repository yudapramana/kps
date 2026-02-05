<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceAccount;
use Illuminate\Support\Facades\Crypt;

class SyncCustomerServiceToken extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'oldold:sync-customer-token';

    /**
     * The console command description.
     */
    protected $description = 'Sync service token from Customer App and store it encrypted';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Checking Customer API service token...');

        return DB::transaction(function () {

            // =====================================
            // 1️⃣ CEK TOKEN SUDAH ADA
            // =====================================
            $service = ServiceAccount::where('name', 'Customer API')->first();

            if ($service && $service->encrypted_token) {
                $this->info('✅ Service token already exists. No action needed.');
                return Command::SUCCESS;
            }

            // =====================================
            // 2️⃣ REQUEST TOKEN KE CUSTOMER APP
            // =====================================
            $this->info('📡 Requesting new token from Customer App... URL: ' . config('services.customer_api.url') . '/api/internal/service-token');
            $this->info('📡 URL: ' . config('services.customer_api.url') . '/api/internal/service-token');

            $response = Http::timeout(10)->post(
                config('services.customer_api.url') . '/api/internal/service-token',
                [
                    'service'   => 'admin-app',
                    'abilities' => ['read-payment-proof'],
                ]
            );

            if (! $response->successful()) {
                $this->error('❌ Failed to retrieve token from Customer App');
                return Command::FAILURE;
            }

            $plainToken = $response->json('token');

            if (! $plainToken) {
                $this->error('❌ Token not found in response');
                return Command::FAILURE;
            }

            // =====================================
            // 3️⃣ SIMPAN TOKEN TER-ENKRIPSI
            // =====================================
            ServiceAccount::updateOrCreate(
                ['name' => 'Customer API'],
                [
                    'encrypted_token' => Crypt::encryptString($plainToken),
                ]
            );
            $this->info('Plain Token: ' . $plainToken);
            $this->info('🔐 Service token stored securely (encrypted)');
            $this->info('🎉 Sync completed successfully');

            return Command::SUCCESS;
        });
    }
}
