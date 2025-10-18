<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'image_url',
        'slug'
    ];

    // Optional: Accessor for full image URL
    public function getFullImageUrlAttribute()
    {
        if ($this->image_url) {
            if (str_starts_with($this->image_url, 'http')) {
                return $this->image_url;
            }
            return asset('storage/' . $this->image_url);
        }
        return null;
    }
}