<?php

namespace Devletes\ChronoPicker\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'stay' => 'array',
    ];

    public function legs(): HasMany
    {
        return $this->hasMany(Leg::class);
    }
}
