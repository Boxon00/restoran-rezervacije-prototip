<?php
/**
 * Isti test kao tests/test_double_booking_prevention.php, ali pokrenut nad
 * PRAVIM MySQL 8.0 / InnoDB serverom (umesto SQLite), koristeći SELECT ... FOR UPDATE
 * unutar transakcije — što je tačan MySQL ekvivalent Eloquent metode
 * lockForUpdate() korišćene u ReservationController::store().
 *
 * Ovim se potvrđuje da mehanizam opisan u poglavlju 5.10 rada radi ispravno
 * i nad ciljnim produkcionim sistemom za upravljanje bazom podataka (MySQL),
 * a ne samo nad SQLite zamenom korišćenom za brzu proveru logike.
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=restoran_rezervacije;charset=utf8mb4', 'root', 'rootpass');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Čist test start
$pdo->exec("DELETE FROM reservations WHERE table_id = 1");

/** @return array{success: bool, reason: ?string} */
function attemptReservation(PDO $pdo, int $userId, int $restaurantId, int $tableId, string $time): array
{
    try {
        $pdo->beginTransaction();

        // Ekvivalent Eloquent: Reservation::where(...)->lockForUpdate()->exists()
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM reservations
             WHERE table_id = ? AND reservation_time = ? AND status IN ('pending','confirmed')
             FOR UPDATE"
        );
        $stmt->execute([$tableId, $time]);
        $conflict = (int) $stmt->fetchColumn() > 0;

        if ($conflict) {
            $pdo->rollBack();
            return ['success' => false, 'reason' => 'Aplikacioni nivo (SELECT ... FOR UPDATE unutar transakcije): konflikt pronađen.'];
        }

        $insert = $pdo->prepare(
            "INSERT INTO reservations (user_id, restaurant_id, table_id, reservation_time, guest_count, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 2, 'confirmed', NOW(), NOW())"
        );
        $insert->execute([$userId, $restaurantId, $tableId, $time]);

        $pdo->commit();
        return ['success' => true, 'reason' => null];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'reason' => 'Nivo baze (UNIQUE uq_table_time): '.$e->getMessage()];
    }
}

function check(bool $cond, string $label, array &$results): void
{
    $results[] = $cond;
    echo ($cond ? "[PROLAZI] " : "[NE PROLAZI] ").$label."\n";
}

echo "=== Test nad pravim MySQL 8.0 / InnoDB serverom ===\n";
$results = [];

$r1 = attemptReservation($pdo, 2, 1, 1, '2026-09-10 20:00:00');
check($r1['success'] === true, 'Prva rezervacija slobodnog termina (sto 1, 20:00) uspeva', $results);

$r2 = attemptReservation($pdo, 2, 1, 1, '2026-09-10 20:00:00');
check($r2['success'] === false, 'Druga rezervacija ISTOG stola/termina biva odbijena', $results);
echo "   Razlog: {$r2['reason']}\n";

$r3 = attemptReservation($pdo, 2, 1, 1, '2026-09-10 21:00:00');
check($r3['success'] === true, 'Isti sto, drugi termin (21:00) — uspeva', $results);

$count = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE table_id = 1")->fetchColumn();
check($count === 2, "Ukupno upisano tačno 2 rezervacije (dobijeno: {$count})", $results);

// Direktan insert bez provere u aplikaciji - i dalje mora biti odbijen od strane baze
try {
    $pdo->beginTransaction();
    $insert = $pdo->prepare(
        "INSERT INTO reservations (user_id, restaurant_id, table_id, reservation_time, guest_count, status, created_at, updated_at)
         VALUES (2, 1, 1, '2026-09-10 20:00:00', 2, 'confirmed', NOW(), NOW())"
    );
    $insert->execute();
    $pdo->commit();
    check(false, 'Baza je NEOČEKIVANO dozvolila duplikat bez provere u kodu', $results);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    check(true, 'MySQL UNIQUE(table_id, reservation_time) sprečava duplikat i bez provere u aplikaciji', $results);
}

$passed = count(array_filter($results));
$total = count($results);
echo "\nProšlo: {$passed}/{$total}\n";
exit($passed === $total ? 0 : 1);
