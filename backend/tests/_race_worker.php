<?php
// Worker proces koji pokušava jedan pokušaj rezervacije. Namerno koristimo
// mali nasumični mikro-delay (0-5ms) da simuliramo realno mrežno okruženje
// u kom dva zahteva stižu u praktično istom trenutku, ali ne u apsolutno
// identičnom nanosekundnom trenutku — što odgovara stvarnim uslovima.

usleep(random_int(0, 5000));

$pdo = new PDO('mysql:host=127.0.0.1;dbname=restoran_rezervacije;charset=utf8mb4', 'root', 'rootpass');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tableId = 1;
$time = '2026-09-15 19:00:00';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM reservations
         WHERE table_id = ? AND reservation_time = ? AND status IN ('pending','confirmed')
         FOR UPDATE"
    );
    $stmt->execute([$tableId, $time]);
    $conflict = (int) $stmt->fetchColumn() > 0;

    if ($conflict) {
        $pdo->rollBack();
        echo "NEUSPEH (konflikt)";
        exit(0);
    }

    $insert = $pdo->prepare(
        "INSERT INTO reservations (user_id, restaurant_id, table_id, reservation_time, guest_count, status, created_at, updated_at)
         VALUES (2, 1, ?, ?, 2, 'confirmed', NOW(), NOW())"
    );
    $insert->execute([$tableId, $time]);
    $pdo->commit();
    echo "USPEH";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "NEUSPEH (baza: ".$e->getMessage().")";
}
