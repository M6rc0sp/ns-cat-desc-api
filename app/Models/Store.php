<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'store_id',
        'store_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'store_data',
    ];

    protected $casts = [
        'store_data' => 'array',
        'token_expires_at' => 'datetime',
    ];
}
