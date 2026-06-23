<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class XeroConnection extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_type',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'xero_user_id',
        'xero_user_email',
        'xero_user_name',
        'json',
        'needs_reauth',
        'reauth_reason'
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTokenExpired(): bool
    {
        return Carbon::now()->gte($this->token_expires_at->subMinutes(5));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function tenants()
    {
        return $this->hasMany(XeroTenant::class);
    }
}
