<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
