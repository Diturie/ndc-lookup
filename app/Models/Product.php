<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'ndc_code',
        'brand_name',
        'generic_name',
        'labeler_name',
        'product_type',
        'source'
    ];

    // Kërkimi i shpejtë sipas kodit NDC
    public static function findByNdc($code)
    {
        return static::where('ndc_code', $code)->first();
    }
}