<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Vraća agregirane podatke koje frontend admin dashboard prikazuje,
 * i koje detaljnije obrađuje Python/Pandas analitički modul (export u CSV).
 */
class AdminAnalyticsController extends Controller
{
    public function summary(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json([
            'total_reservations' => Reservation::count(),
            'confirmed' => Reservation::where('status', 'confirmed')->count(),
            'cancelled' => Reservation::where('status', 'cancelled')->count(),
            'cancellation_rate' => $this->cancellationRate(),
            'reservations_by_hour' => Reservation::selectRaw('HOUR(reservation_time) as hour, COUNT(*) as total')
                ->groupBy('hour')->orderBy('hour')->get(),
            'reservations_by_weekday' => Reservation::selectRaw('DAYNAME(reservation_time) as day, COUNT(*) as total')
                ->groupBy('day')->get(),
            'top_restaurants' => Reservation::selectRaw('restaurant_id, COUNT(*) as total')
                ->groupBy('restaurant_id')->orderByDesc('total')->limit(5)
                ->with('restaurant:id,name')->get(),
        ]);
    }

    public function exportCsv(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $rows = Reservation::with(['restaurant:id,name', 'table:id,label,capacity', 'user:id,name'])
            ->get(['id', 'user_id', 'restaurant_id', 'table_id', 'reservation_time', 'guest_count', 'status']);

        $filename = 'rezervacije_export_'.now()->format('Y_m_d_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'korisnik', 'restoran', 'sto', 'termin', 'broj_gostiju', 'status']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, $r->user->name ?? '-', $r->restaurant->name ?? '-',
                    $r->table->label ?? '-', $r->reservation_time, $r->guest_count, $r->status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function cancellationRate(): float
    {
        $total = Reservation::count();
        if ($total === 0) return 0;
        return round(Reservation::where('status', 'cancelled')->count() / $total * 100, 2);
    }
}
