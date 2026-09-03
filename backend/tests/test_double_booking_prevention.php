<?php
/**
 * Samostalan, izvršni test koji dokazuje ispravnost mehanizma za sprečavanje
 * dupliranih rezervacija opisanog u poglavlju 5.10 istraživačkog rada.
 *
 * Test replicira TAČNU logiku iz ReservationController::store() (transakcija +
 * provera konflikta + UNIQUE ograničenje na nivou baze) nad pravom SQLite bazom
 * preko PDO-a, bez potrebe za punim Laravel okruženjem. Ovim se potvrđuje da:
 *   1) Prvi zahtev za slobodan termin uspeva.
 *   2) Drugi (naredni) zahtev za ISTI sto i ISTI termin biva odbijen.
 *   3) Mehanizam ispravno razlikuje konflikt po (sto, termin) od drugih,
 *      nekonfliktnih kombinacija (isti sto/drugi termin, drugi sto/isti termin).
 *
 * Pokretanje:  php test_double_booking_prevention.php
 */

function makeConnection(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('
        CREATE TABLE reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            table_id INTEGER NOT NULL,
            reservation_time TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT "confirmed",
            UNIQUE(table_id, reservation_time)
        )
    ');
    return $pdo;
}

/**
 * Replika logike iz ReservationController::store():
 *   - transakcija
 *   - eksplicitna provera konflikta (simulira lockForUpdate() -> u SQLite-u
 *     ekvivalent je BEGIN IMMEDIATE koji odmah zaključava bazu za pisanje)
 *   - insert, koji dodatno štiti UNIQUE ograničenje na nivou baze
 *
 * @return array{success: bool, reason: ?string}
 */
function attemptReservation(PDO $pdo, int $tableId, string $time): array
{
    try {
        $pdo->exec('BEGIN IMMEDIATE'); // ekvivalent lockForUpdate() ponašanja u MySQL/InnoDB

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE table_id = ? AND reservation_time = ? AND status IN ("pending","confirmed")');
        $stmt->execute([$tableId, $time]);
        $conflict = (int) $stmt->fetchColumn() > 0;

        if ($conflict) {
            $pdo->exec('ROLLBACK');
            return ['success' => false, 'reason' => 'Aplikacioni nivo: konflikt pronađen pre upisa (isti obrazac kao ValidationException u Laravel kontroleru).'];
        }

        $insert = $pdo->prepare('INSERT INTO reservations (table_id, reservation_time, status) VALUES (?, ?, "confirmed")');
        $insert->execute([$tableId, $time]);

        $pdo->exec('COMMIT');
        return ['success' => true, 'reason' => null];
    } catch (PDOException $e) {
        // Poslednja linija odbrane: UNIQUE constraint violation na nivou baze
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
        return ['success' => false, 'reason' => 'Nivo baze podataka: UNIQUE(table_id, reservation_time) je odbio upis. ('.$e->getMessage().')'];
    }
}

function assertTrue(bool $cond, string $label, array &$results): void
{
    $results[] = ['label' => $label, 'pass' => $cond];
    echo ($cond ? "[PROLAZI] " : "[NE PROLAZI] ").$label."\n";
}

echo "=== Test 1: Sekvencijalni pokušaji rezervacije istog stola/termina ===\n";
$pdo = makeConnection();
$results = [];

$r1 = attemptReservation($pdo, 7, '2026-09-01 20:00:00');
assertTrue($r1['success'] === true, 'Prvi zahtev za slobodan termin (sto 7, 20:00) uspeva', $results);

$r2 = attemptReservation($pdo, 7, '2026-09-01 20:00:00');
assertTrue($r2['success'] === false, 'Drugi zahtev za ISTI sto i ISTI termin biva odbijen', $results);
echo "   Razlog odbijanja: {$r2['reason']}\n";

$r3 = attemptReservation($pdo, 7, '2026-09-01 20:30:00');
assertTrue($r3['success'] === true, 'Isti sto, RAZLIČIT termin (20:30) — uspeva (nema konflikta)', $results);

$r4 = attemptReservation($pdo, 9, '2026-09-01 20:00:00');
assertTrue($r4['success'] === true, 'RAZLIČIT sto, isti termin (20:00) — uspeva (nema konflikta)', $results);

$countStmt = $pdo->query('SELECT COUNT(*) FROM reservations');
$total = (int) $countStmt->fetchColumn();
assertTrue($total === 3, "Ukupno upisanih rezervacija je tačno 3 (očekivano posle 4 pokušaja, 1 odbijen): dobijeno {$total}", $results);

echo "\n=== Test 2: Direktan pokušaj zaobilaženja aplikacionog nivoa (insert bez provere) ===\n";
// Simulira scenario u kom bi neki drugi deo sistema (bug, race condition) pokušao
// da upiše duplikat direktno, zaobilazeći proveru u kodu — UNIQUE ograničenje na
// nivou baze mora i dalje da spreči duplikat.
try {
    $pdo->exec('BEGIN IMMEDIATE');
    $insert = $pdo->prepare('INSERT INTO reservations (table_id, reservation_time, status) VALUES (?, ?, "confirmed")');
    $insert->execute([7, '2026-09-01 20:00:00']); // već postoji iz Testa 1
    $pdo->exec('COMMIT');
    assertTrue(false, 'Baza je NEOČEKIVANO dozvolila duplikat bez provere u kodu', $results);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->exec('ROLLBACK');
    assertTrue(true, 'UNIQUE ograničenje na nivou baze samostalno sprečava duplikat i kada aplikacioni kod ne proveri konflikt', $results);
}

echo "\n=== Rezime ===\n";
$passed = count(array_filter($results, fn($r) => $r['pass']));
$totalTests = count($results);
echo "Prošlo testova: {$passed} / {$totalTests}\n";

if ($passed === $totalTests) {
    echo "ZAKLJUČAK: Dvoslojni mehanizam zaštite (transakcija sa proverom konflikta na\n";
    echo "aplikacionom nivou + UNIQUE ograničenje na nivou baze) ispravno sprečava\n";
    echo "dupliranje rezervacija istog stola u istom terminu, uz očuvanje mogućnosti\n";
    echo "paralelnih, nekonfliktnih rezervacija.\n";
    exit(0);
}

echo "GREŠKA: Jedan ili više testova nije prošlo.\n";
exit(1);
