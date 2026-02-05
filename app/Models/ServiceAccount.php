<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ServiceAccount extends Model
{
    protected $fillable = [
        'name',
        'encrypted_token',
    ];

    protected $hidden = [
        'encrypted_token', // jangan bocor lewat JSON
    ];

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR
    |--------------------------------------------------------------------------
    | Mengambil token yang sudah di-decrypt
    | Pemakaian: $service->token
    */
    public function getTokenAttribute(): ?string
    {
        if (! $this->encrypted_token) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_token);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    | Cek apakah token tersedia & valid
    */
    public function hasToken(): bool
    {
        return ! empty($this->encrypted_token);
    }
}
