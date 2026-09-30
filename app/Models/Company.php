<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')->withTimestamps();
    }
}
