<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    protected $fillable = [
        'client_id',
        'full_name',
        'role',
        'contact_type',
        'email',
        'phone',
        'whatsapp',
        'linkedin_url',
        'birthday',
        'email_opt_in',
        'sms_opt_in',
        'is_primary',
    ];

    protected $casts = [
        'is_primary'    => 'boolean',
        'email_opt_in'  => 'boolean',
        'sms_opt_in'    => 'boolean',
        'birthday'      => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
