<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'reservation_id' => 'nullable|exists:reservations,id',
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = $request->user()->id;

        $rating = Rating::create($data);
        $rating->restaurant->recalculateRating();

        return response()->json($rating, 201);
    }
}
