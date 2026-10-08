<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSize extends Model
{
    use HasFactory;

    protected $table = 'product_sizes';

    protected $fillable = [
        'product_id',
        'size',
        'stock_total',
        'stock_available',
    ];

    protected $casts = [
        'stock_total' => 'integer',
        'stock_available' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
