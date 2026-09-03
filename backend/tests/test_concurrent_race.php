<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=restoran_rezervacije;charset=utf8mb4', 'root', 'Jasambojan123');
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

// očisti warninge iz izlaza
function cleanOutput($out) {
    $lines = explode("\n", $out);
    $filtered = array_filter($lines, fn($line) => stripos($line, 'Warning') === false);
    return trim(implode("\n", $filtered));
}

$out1Clean = cleanOutput($out1);
$out2Clean = cleanOutput($out2);

echo "Proces A: ".$out1Clean."\n";
echo "Proces B: ".$out2Clean."\n";

// ✅ sada proveravamo da li je izlaz TAČNO "USPEH"
$successCount = (int) ($out1Clean === 'USPEH')
              + (int) ($out2Clean === 'USPEH');

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
