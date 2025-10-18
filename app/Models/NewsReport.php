<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'news_post_id',
        'user_id',
        'reason',
        'description',
        'status',
    ];

    // Relationships
    public function newsPost()
    {
        return $this->belongsTo(NewsPost::class, 'news_post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
