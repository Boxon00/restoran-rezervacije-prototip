<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Administratorsko upravljanje korisničkim nalozima (FR-06).
 * Sve rute u ovom kontroleru zaštićene su auth:sanctum middleware-om
 * i dodatnom proverom uloge (isAdmin()) unutar svake metode.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $query = User::query()->withCount('reservations');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        return response()->json($query->orderBy('name')->paginate(15));
    }

    public function show(Request $request, User $user)
    {
        $this->authorizeAdmin($request);
        $user->load(['reservations.restaurant', 'ratings']);
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'role' => 'sometimes|in:guest,customer,admin',
            'password' => 'nullable|string|min:8',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeAdmin($request);

        abort_if($user->id === $request->user()->id, 422, 'Ne možete obrisati sopstveni nalog.');

        $user->delete();

        return response()->json(['message' => 'Korisnik je obrisan.']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Nemate dozvolu za ovu akciju.');
    }
}
