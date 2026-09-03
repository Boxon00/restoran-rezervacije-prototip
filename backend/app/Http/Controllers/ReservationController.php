<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    // GET /api/reservations  (korisnikove rezervacije, ili sve za admina)
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Reservation::with(['restaurant', 'table']);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        return response()->json(
            $query->orderByDesc('reservation_time')->paginate(15)
        );
    }

    // GET /api/restaurants/{restaurant}/availability?date=YYYY-MM-DD&time=HH:MM&guests=2
    public function availability(Request $request, $restaurantId)
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required',
            'guests' => 'required|integer|min:1|max:20',
        ]);

        $slot = $request->date.' '.$request->time.':00';

        $reservedTableIds = Reservation::where('restaurant_id', $restaurantId)
            ->where('reservation_time', $slot)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('table_id');

        $availableTables = RestaurantTable::where('restaurant_id', $restaurantId)
            ->where('capacity', '>=', $request->guests)
            ->whereNotIn('id', $reservedTableIds)
            ->where('status', '!=', 'maintenance')
            ->orderBy('capacity')
            ->get();

        return response()->json($availableTables);
    }

    // POST /api/reservations
    public function store(Request $request)
    {
        $data = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'table_id' => 'required|exists:tables,id',
            'reservation_time' => 'required|date',
            'guest_count' => 'required|integer|min:1|max:20',
            'note' => 'nullable|string|max:500',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = 'confirmed';

        // Transakcija sa row-lock proverom sprečava race-condition dupliranje
        // rezervacije istog stola u istom terminu (dva korisnika kliknu istovremeno).
        try {
            $reservation = DB::transaction(function () use ($data) {
                $conflict = Reservation::where('table_id', $data['table_id'])
                    ->where('reservation_time', $data['reservation_time'])
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->lockForUpdate()
                    ->exists();

                if ($conflict) {
                    throw ValidationException::withMessages([
                        'table_id' => 'Izabrani sto je u međuvremenu rezervisan za taj termin. Molimo izaberite drugi termin ili sto.',
                    ]);
                }

                return Reservation::create($data);
            });
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 409);
        }

        // Baza podataka takođe štiti jedinstvenim (table_id, reservation_time) indeksom
        // kao poslednja linija odbrane u slučaju konkurentnih upisa.

        return response()->json($reservation->load(['restaurant', 'table']), 201);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->authorizeOwnerOrAdmin($request, $reservation);

        $data = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
            'guest_count' => 'sometimes|integer|min:1|max:20',
            'note' => 'nullable|string|max:500',
        ]);

        $reservation->update($data);
        return response()->json($reservation);
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        $this->authorizeOwnerOrAdmin($request, $reservation);
        $reservation->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Rezervacija je otkazana.']);
    }

    private function authorizeOwnerOrAdmin(Request $request, Reservation $reservation): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $reservation->user_id === $user->id, 403);
    }
}
