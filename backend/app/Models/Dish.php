<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    protected $fillable = ['menu_id', 'name', 'category', 'price', 'order_count'];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
