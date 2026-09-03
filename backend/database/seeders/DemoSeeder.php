<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Menu;
use App\Models\Rating;
use App\Models\Reservation;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Bojan Mitić', 'email' => 'admin@rezervacije.rs',
            'password' => Hash::make('password'), 'role' => 'admin',
        ]);

        $customers = User::factory(20)->create(['role' => 'customer']);

        $names = ['Stara Srpska Kuća', 'Sonic Restaurant', 'Zlatna Ribica', 'Trpeza Balkana', 'Rustika', 'Villa Nova'];
        $cuisines = ['srpska', 'italijanska', 'riblja', 'balkanska', 'mediteranska', 'internacionalna'];

        foreach ($names as $i => $name) {
            $restaurant = Restaurant::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::random(4),
                'description' => 'Ukusna hrana i prijatan ambijent u srcu grada.',
                'address' => 'Ulica '.($i + 1).', Niš',
                'city' => 'Niš',
                'cuisine_type' => $cuisines[$i],
                'phone' => '018/'.rand(200000, 599999),
                'opening_time' => '10:00', 'closing_time' => '23:30',
            ]);

            $menu = Menu::create(['restaurant_id' => $restaurant->id, 'name' => 'Glavni jelovnik']);
            foreach (['Predjelo', 'Glavno jelo', 'Dezert', 'Piće'] as $cat) {
                for ($d = 0; $d < 3; $d++) {
                    Dish::create([
                        'menu_id' => $menu->id,
                        'name' => $cat.' '.($d + 1),
                        'category' => $cat,
                        'price' => rand(250, 2200),
                        'order_count' => rand(0, 300),
                    ]);
                }
            }

            for ($t = 1; $t <= 10; $t++) {
                RestaurantTable::create([
                    'restaurant_id' => $restaurant->id,
                    'label' => 'Sto '.$t,
                    'capacity' => [2, 2, 4, 4, 6][array_rand([2, 2, 4, 4, 6])],
                ]);
            }
        }

        // Generisanje istorijskih rezervacija i ocena za analitički modul.
        // Kako je nad kolonama (table_id, reservation_time) definisano UNIQUE
        // ograničenje (poglavlje 4.5 / 5.10), pratimo već zauzete kombinacije
        // da bismo izbegli pokušaj upisa duplikata tokom generisanja demo podataka.
        $restaurants = Restaurant::all();
        foreach ($restaurants as $restaurant) {
            $tables = $restaurant->tables;
            $usedSlots = [];
            $created = 0;
            $attempts = 0;

            while ($created < 60 && $attempts < 400) {
                $attempts++;

                $table = $tables->random();
                $daysAgo = rand(0, 90);
                $hour = collect([12, 13, 19, 20, 20, 21])->random();
                $minute = collect([0, 30])->random();
                $time = now()->subDays($daysAgo)->setTime($hour, $minute, 0);

                $slotKey = $table->id.'|'.$time->toDateTimeString();
                if (isset($usedSlots[$slotKey])) {
                    continue; // izbegavamo koliziju sa UNIQUE(table_id, reservation_time)
                }
                $usedSlots[$slotKey] = true;

                $reservation = Reservation::create([
                    'user_id' => $customers->random()->id,
                    'restaurant_id' => $restaurant->id,
                    'table_id' => $table->id,
                    'reservation_time' => $time,
                    'guest_count' => min($table->capacity, rand(1, $table->capacity)),
                    'status' => collect(['confirmed', 'confirmed', 'confirmed', 'cancelled', 'completed'])->random(),
                ]);
                $created++;

                if (rand(0, 100) < 40) {
                    Rating::create([
                        'user_id' => $reservation->user_id,
                        'restaurant_id' => $restaurant->id,
                        'reservation_id' => $reservation->id,
                        'stars' => rand(3, 5),
                        'comment' => 'Odlično iskustvo, preporučujem!',
                    ]);
                }
            }
            $restaurant->recalculateRating();
        }
    }
}
