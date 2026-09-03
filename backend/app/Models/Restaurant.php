<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'address', 'city', 'cuisine_type',
        'phone', 'cover_image', 'opening_time', 'closing_time',
        'avg_rating', 'rating_count',
    ];

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class, 'restaurant_id');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function recalculateRating(): void
    {
        $this->avg_rating = $this->ratings()->avg('stars') ?? 0;
        $this->rating_count = $this->ratings()->count();
        $this->save();
    }
}
