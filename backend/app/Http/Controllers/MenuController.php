<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function storeDish(Request $request, Menu $menu)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
        ]);
        $data['menu_id'] = $menu->id;

        return response()->json(Dish::create($data), 201);
    }

    public function updateDish(Request $request, Dish $dish)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $dish->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0',
        ]));
        return response()->json($dish);
    }

    public function destroyDish(Request $request, Dish $dish)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $dish->delete();
        return response()->json(['message' => 'Jelo obrisano.']);
    }
}
