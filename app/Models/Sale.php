<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = [];

    public function setOrderedAtAttribute($value): void
    {
        $this->attributes['ordered_at'] = Carbon::parse($value)->toDateString();
    }

    protected function casts(): array
    {
        return ['ordered_at' => 'date', 'amount_cents' => 'integer'];
    }
}
