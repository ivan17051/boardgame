<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalAdditionalItem extends Model
{
    protected $table = 'rental_additional_item';

    public $timestamps = false;

    protected $fillable = [
        'id_rental',
        'id_additional_item',
        'nama',
        'harga',
        'qty',
        'subtotal',
    ];

    protected $casts = [
        'harga' => 'decimal:3',
        'subtotal' => 'decimal:3',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'id_rental');
    }

    public function additionalItem(): BelongsTo
    {
        return $this->belongsTo(AdditionalItem::class, 'id_additional_item');
    }
}
