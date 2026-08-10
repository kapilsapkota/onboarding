<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteSignature extends Model
{
   protected $fillable = [
       'quote_id',
       'company_name',
       'authorised_person',
       'position',
       'signature_path',
       'ip_address',
       'user_agent',
       'signed_at',
   ];

   protected $casts = [
       'signed_at' => 'datetime',
   ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
