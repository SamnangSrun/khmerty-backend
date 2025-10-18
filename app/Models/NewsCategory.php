<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (!$category->url) {
                $category->url = Str::slug($category->name);
            }
        });
    }

    // Relationships
    public function posts()
    {
        return $this->hasMany(NewsPost::class, 'category_id');
    }

    public function publishedPosts()
    {
        return $this->hasMany(NewsPost::class, 'category_id')
            ->where('status', 'published');
    }

    // Scope
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
