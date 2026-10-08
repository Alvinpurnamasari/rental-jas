<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'category_id', 'code', 'name', 'description',
        'size', 'color', 'brand', 'rental_price', 'deposit_price',
        'stock_total', 'stock_available', 'photo', 'qr_code',
        'condition', 'status', 'notes',
    ];

    protected $casts = [
        'rental_price' => 'decimal:2',
        'deposit_price' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'product_branch');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    public function getTotalStockAttribute(): int
    {
        if ($this->relationLoaded('sizes')) {
            return $this->sizes->sum('stock_total');
        }

        return (int) $this->sizes()->sum('stock_total');
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->relationLoaded('sizes')) {
            return $this->sizes->sum('stock_available');
        }

        return (int) $this->sizes()->sum('stock_available');
    }

    public function getSizesDataAttribute(): array
    {
        return $this->sizes->mapWithKeys(function ($size) {
            return [$size->size => [
                'stock_total' => $size->stock_total,
                'stock_available' => $size->stock_available,
            ]];
        })->toArray();
    }

    public function decrementSizeStock(string $size, int $quantity): int
    {
        $productSize = $this->sizes()->where('size', $size)->lockForUpdate()->first();
        if ($productSize) {
            if ((int) $productSize->stock_available < $quantity) {
                return -1;
            }
            $productSize->decrement('stock_available', $quantity);
        } else {
            $newStock = (int) $this->stock_available - $quantity;
            if ($newStock < 0) {
                return -1;
            }
            $this->stock_available = $newStock;
        }
        $this->recomputeAggregateStock(outOfStock: true);
        return $this->available_stock;
    }

    public function incrementSizeStock(string $size, int $quantity): void
    {
        $productSize = $this->sizes()->where('size', $size)->lockForUpdate()->first();
        if ($productSize) {
            $productSize->increment('stock_available', $quantity);
        } else {
            $this->stock_available = (int) $this->stock_available + $quantity;
        }
        $this->recomputeAggregateStock(outOfStock: false);
    }

    public function setSizeStockAvailable(ProductSize $productSize, int $stockAvailable): void
    {
        if ((int) $productSize->product_id !== (int) $this->id) {
            throw new \InvalidArgumentException('The size stock does not belong to this product.');
        }

        $stockDecreased = $stockAvailable < (int) $productSize->stock_available;
        $productSize->stock_available = $stockAvailable;
        $productSize->save();

        $this->recomputeAggregateStock(outOfStock: $stockDecreased);
    }

    protected function recomputeAggregateStock(bool $outOfStock = true): void
    {
        $this->load('sizes');
        $this->stock_available = $this->available_stock;
        $this->stock_total = $this->total_stock;
        if ($outOfStock) {
            $this->status = ($this->available_stock <= 0) ? 'rented' : $this->status;
        } else {
            $this->status = ($this->status === 'rented' && $this->available_stock > 0) ? 'available' : $this->status;
        }
        $this->save();
    }

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            // Jika URL external (http:// atau https://)
            if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
                return $this->photo;
            }
            return asset('storage/' . $this->photo);
        }
        // Default no product image
        return 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400&h=500&fit=crop';
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        if ($this->qr_code) {
            return asset('storage/' . $this->qr_code);
        }
        return null;
    }

    public function isAvailable(): bool
    {
        return $this->stock_available > 0 && $this->status === 'available';
    }

    public function getRentalPriceFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->rental_price, 0, ',', '.');
    }
}
