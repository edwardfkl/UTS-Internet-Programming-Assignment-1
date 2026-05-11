<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DRAFT = 'draft';

    /**
     * Product statuses. `active` = listed (frontend visible), `inactive` = unlisted,
     * `draft` = staged (admin-only).
     *
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_DRAFT,
    ];

    protected $fillable = [
        'name',
        'description',
        'price',
        'image_url',
        'stock',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Restrict the query to publicly listed (active) products only.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
