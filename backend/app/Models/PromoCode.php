<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string|null $label
 * @property string $type
 * @property float $amount
 * @property float|null $min_subtotal
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property bool $is_active
 */
class PromoCode extends Model
{
    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENT = 'percent';

    /**
     * @var list<string>
     */
    public const TYPES = [self::TYPE_FIXED, self::TYPE_PERCENT];

    protected $table = 'promo_codes';

    protected $fillable = [
        'code',
        'label',
        'type',
        'amount',
        'min_subtotal',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Restrict the query to codes that are currently redeemable
     * (active and within their date window).
     *
     * @param  Builder<PromoCode>  $query
     */
    public function scopeRedeemable(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Compute the discount this code grants for the given subtotal, or
     * `null` if the code cannot currently be applied (inactive, outside
     * date window, or below min subtotal).
     */
    public function discountFor(float $subtotal): ?float
    {
        if (! $this->is_active) {
            return null;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return null;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return null;
        }
        if ($this->min_subtotal !== null && $subtotal < (float) $this->min_subtotal) {
            return null;
        }
        if ($subtotal <= 0) {
            return 0.0;
        }

        $amount = (float) $this->amount;
        $discount = $this->type === self::TYPE_PERCENT
            ? $subtotal * ($amount / 100)
            : $amount;

        return round(min($discount, $subtotal), 2);
    }
}
