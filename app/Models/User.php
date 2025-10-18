<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

   // app/Models/User.php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'profile_image',
     'profile_public_id',
];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relationships
    public function books()
    {
        return $this->hasMany(Book::class, 'seller_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function sellerRequest()
    {
        return $this->hasOne(SellerRequest::class);
    }

   public function notifications()
{
    return $this->hasMany(Notification::class);
}

    // News relationships
    public function newsPosts()
    {
        return $this->hasMany(NewsPost::class, 'author_id');
    }

    public function savedNews()
    {
        return $this->belongsToMany(NewsPost::class, 'saved_news', 'user_id', 'news_post_id')
            ->withTimestamps();
    }

    public function newsReports()
    {
        return $this->hasMany(NewsReport::class);
    }


    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

 
    public function loadDetails()
{
    return $this->makeVisible([
        'id', 'name', 'email', 'role', 'profile_image', 'created_at'
    ]);
}

}
