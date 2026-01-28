<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProxyServerConfig extends Model
{
    use SoftDeletes;

    protected $table = 'proxyservers';

    protected $fillable = [
        'name',
        'slug',
        'server',
        'password',
        'username',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}