<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientCharge extends Model
{
    protected $fillable = [
        'client_id',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'description',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Helper: dollars from cents
    public function getAmountInDollarsAttribute(): string
    {
        return number_format($this->amount / 100, 2);
    }
}
