<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
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


    /**
     * Set the password attribute - hash and encrypt the password
     */
    public function setPasswordAttribute($password)
    {
        if ($password !== '*****') {
            $this->attributes['password'] = Hash::make($password);
        }
    }

    /**
     * Get the password attribute - always return masked value
     */
    public function getPasswordAttribute($password)
    {
        return '*****';
    }


    /**
     * Check if the provided password matches the stored password
     */
    public function checkPassword($password)
    {
        return Hash::check($password, $this->attributes['password']);
    }
}