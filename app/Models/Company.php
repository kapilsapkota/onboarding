<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'state',
        'city',
        'postal_code',
        'country',
        'logo',
    ];
}
