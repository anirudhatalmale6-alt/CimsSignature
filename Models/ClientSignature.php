<?php

namespace Modules\CimsSignature\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSignature extends Model
{
    protected $table = 'client_signatures';

    protected $fillable = [
        'client_name',
        'client_reference',
        'id_number',
        'signature_data',
        'signature_hash',
        'purpose',
        'document_reference',
        'ip_address',
        'device_info',
        'captured_by',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    /**
     * Get signature as an image tag
     */
    public function getSignatureImageAttribute(): string
    {
        return '<img src="' . $this->signature_data . '" alt="Signature" style="max-width: 300px; max-height: 100px;">';
    }

    /**
     * Get the latest signature for a client
     */
    public static function getLatestForClient(string $clientReference): ?self
    {
        return self::where('client_reference', $clientReference)
            ->orderBy('captured_at', 'desc')
            ->first();
    }

    /**
     * Verify signature integrity
     */
    public function verifyIntegrity(): bool
    {
        if (!$this->signature_hash) {
            return false;
        }
        return hash('sha256', $this->signature_data) === $this->signature_hash;
    }
}
