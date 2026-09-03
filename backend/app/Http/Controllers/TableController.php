<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;

/**
 * Administratorsko upravljanje stolovima u okviru restorana (FR-06, CRUD stolova, poglavlje 5.5).
 */
class TableController extends Controller
{
    public function index(Request $request, Restaurant $restaurant)
    {
        return response()->json($restaurant->tables()->orderBy('label')->get());
    }

    public function store(Request $request, Restaurant $restaurant)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'label' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1|max:30',
            'status' => 'sometimes|in:available,reserved,maintenance',
        ]);
        $data['restaurant_id'] = $restaurant->id;

        return response()->json(RestaurantTable::create($data), 201);
    }

    public function update(Request $request, RestaurantTable $table)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'label' => 'sometimes|string|max:50',
            'capacity' => 'sometimes|integer|min:1|max:30',
            'status' => 'sometimes|in:available,reserved,maintenance',
        ]);

        $table->update($data);

        return response()->json($table);
    }

    public function destroy(Request $request, RestaurantTable $table)
    {
        $this->authorizeAdmin($request);

        abort_if(
            $table->reservations()->whereIn('status', ['pending', 'confirmed'])->exists(),
            422,
            'Sto ima aktivne rezervacije i ne može biti obrisan.'
        );

        $table->delete();

        return response()->json(['message' => 'Sto je obrisan.']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Nemate dozvolu za ovu akciju.');
    }
}
