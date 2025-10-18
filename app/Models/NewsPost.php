<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'category_id',
        'author_id',
        'images',
        'status',
        'published_at',
        'views_count',
        'shares_count',
    ];

    protected $casts = [
        'images' => 'array',
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'shares_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (!$post->slug) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function savedBy()
    {
        return $this->belongsToMany(User::class, 'saved_news', 'news_post_id', 'user_id')
            ->withTimestamps();
    }

    public function reports()
    {
        return $this->hasMany(NewsReport::class, 'news_post_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('views_count');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('published_at');
    }
}
