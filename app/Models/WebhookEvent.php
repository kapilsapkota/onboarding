<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
       'source',
      'event_id',
      'event_type',
      'payload',
      'status',
      'attempts',
      'processed_at',
      'error_message',
      'processing_log',
    ];
}
