<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    public const STATUS_CART = 'cart';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    protected $fillable = [
        'user_id',
        'token',
        'status',
        'payment_method',
        'placed_at',
        'shipping_recipient_name',
        'shipping_phone',
        'shipping_line1',
        'shipping_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postcode',
        'shipping_country',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
