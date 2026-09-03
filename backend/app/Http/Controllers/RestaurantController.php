<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RestaurantController extends Controller
{
    // GET /api/restaurants?search=&cuisine=&city=
    public function index(Request $request)
    {
        $query = Restaurant::query()->withCount('reservations');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('cuisine')) {
            $query->where('cuisine_type', $request->cuisine);
        }
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        return response()->json(
            $query->orderByDesc('avg_rating')->paginate(12)
        );
    }

    public function show(Restaurant $restaurant)
    {
        $restaurant->load(['tables', 'menus.dishes', 'ratings.user']);
        return response()->json($restaurant);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'cuisine_type' => 'nullable|string',
            'phone' => 'nullable|string',
            'opening_time' => 'required',
            'closing_time' => 'required',
        ]);

        $data['slug'] = Str::slug($data['name']).'-'.Str::random(4);
        $restaurant = Restaurant::create($data);

        return response()->json($restaurant, 201);
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $this->authorizeAdmin($request);
        $restaurant->update($request->all());
        return response()->json($restaurant);
    }

    public function destroy(Request $request, Restaurant $restaurant)
    {
        $this->authorizeAdmin($request);
        $restaurant->delete();
        return response()->json(['message' => 'Restoran obrisan.']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Nemate dozvolu za ovu akciju.');
    }
}
