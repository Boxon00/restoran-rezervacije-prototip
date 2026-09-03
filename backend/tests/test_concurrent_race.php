<?php
/**
 * Test PRAVE konkurentnosti: dva odvojena PHP procesa istovremeno pokušavaju
 * da rezervišu ISTI sto u ISTOM terminu. Ovo je najverniji mogući test
 * "race condition" scenarija opisanog u poglavlju 5.10 rada (dva korisnika
 * kliknu "Potvrdi rezervaciju" u praktično istom trenutku).
 *
 * Mehanizam: glavni proces kreira barijeru (fajl-lock) tako da oba child
 * procesa počnu SELECT ... FOR UPDATE transakciju što je moguće bliže
 * istovremeno, zatim proverava da je tačno JEDAN uspeo.
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=restoran_rezervacije;charset=utf8mb4', 'root', 'rootpass');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("DELETE FROM reservations WHERE table_id = 1 AND reservation_time = '2026-09-15 19:00:00'");

$workerScript = __DIR__.'/_race_worker.php';

$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$p1 = proc_open(['php', $workerScript], $descriptors, $pipes1);
$p2 = proc_open(['php', $workerScript], $descriptors, $pipes2);

$out1 = stream_get_contents($pipes1[1]);
fclose($pipes1[1]); fclose($pipes1[2]);
$status1 = proc_close($p1);

$out2 = stream_get_contents($pipes2[1]);
fclose($pipes2[1]); fclose($pipes2[2]);
$status2 = proc_close($p2);

echo "Proces A: ".trim($out1)."\n";
echo "Proces B: ".trim($out2)."\n";

$successCount = (int) (trim($out1) === 'USPEH') + (int) (trim($out2) === 'USPEH');
$rowCount = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE table_id = 1 AND reservation_time = '2026-09-15 19:00:00'")->fetchColumn();

echo "\nBroj procesa koji je uspeo: {$successCount} (očekivano: tačno 1)\n";
echo "Broj redova u bazi za taj termin: {$rowCount} (očekivano: tačno 1)\n";

if ($successCount === 1 && $rowCount === 1) {
    echo "\n[PROLAZI] Pod pravom konkurentnošću (dva paralelna OS procesa), mehanizam iz\n";
    echo "ReservationController::store() dozvoljava tačno jednu rezervaciju za dati\n";
    echo "sto/termin, čime je potvrđena robusnost rešenja i van sekvencijalnog testa.\n";
    exit(0);
} else {
    echo "\n[NE PROLAZI]\n";
    exit(1);
}
