<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class ProxyServerConfig extends Model
{
    use SoftDeletes;

    protected $table = 'proxyservers';

    protected $fillable = [
        'name',
        'slug',
        'server',
        'type',
        'password',
        'username',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Mask used when password is unchanged on edit (do not overwrite stored value) */
    public const PASSWORD_PLACEHOLDER = '*****';

    /**
     * Set the password attribute - encrypt so it can be decrypted for backend requests.
     * Ignores placeholder so we don't overwrite when user didn't change password.
     */
    public function setPasswordAttribute($password): void
    {
        if ($password !== null && $password !== '' && $password !== self::PASSWORD_PLACEHOLDER) {
            $this->attributes['password'] = Crypt::encryptString($password);
        }
    }

    /**
     * Get the password attribute - always return masked value for display/API.
     */
    public function getPasswordAttribute($value): string
    {
        return self::PASSWORD_PLACEHOLDER;
    }

    /**
     * Get the decrypted password for server-side use only (e.g. Basic Auth).
     * Do not expose this in API responses or logs.
     */
    public function getDecryptedPassword(): ?string
    {
        $raw = $this->attributes['password'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }
}