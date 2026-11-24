<?php
declare(strict_types=1);
require __DIR__ . '/src/Calculator.php';
require __DIR__ . '/src/StudentPresenter.php';
require __DIR__ . '/src/StudentRepository.php';
require __DIR__ . '/src/Hewan.php';
require __DIR__ . '/src/BaseReport.php';

$pass = 0; $fail = 0;
function check(string $id, string $skenario, callable $fn, string $expected): void {
    global $pass, $fail;
    try { $actual = (string)$fn(); }
    catch (Throwable $e) { $actual = get_class($e); }
    $ok = $actual === $expected;
    $ok ? $GLOBALS['pass']++ : $GLOBALS['fail']++;
    printf("%-4s %-4s %-52s | harapan: %-28s | hasil: %s\n", $ok ? 'PASS' : 'FAIL', $id, $skenario, $expected, $actual);
}

$calc = new Calculator(); $p = new StudentPresenter();
$reg = new RegularStudent('M01', 'Dwinda', 5000000);
$sch = new ScholarshipStudent('M02', 'Bima', 5000000, 40);
$intl = new InternationalStudent('M03', 'Chen', 5000000, 'China');

check('T01a', 'add(10,20)', fn() => $calc->add(10, 20), '30');
check('T01b', 'add(10,20,30)', fn() => $calc->add(10, 20, 30), '60');
check('T02', 'showData("Dida")', fn() => $p->showData('Dida'), 'Name: Dida');
check('T03', 'showData("2026001","Dida")', fn() => $p->showData('2530521036', 'Dida'), 'ID: 2530521036, Name: Dida');
check('T04a', 'showData() tanpa argumen', fn() => $p->showData(), 'InvalidArgumentException');
check('T04b', 'showData(123) bilangan', fn() => $p->showData(123), 'InvalidArgumentException');
check('T05', 'method tidak dikenal pada presenter', fn() => $p->hapus('x'), 'BadMethodCallException');
check('T06', 'RegularStudent fee 5 juta', fn() => $reg->calculateFee(), '5000000');
check('T07', 'ScholarshipStudent diskon 40%', fn() => $sch->calculateFee(), '3000000');
check('T08a', 'Diskon -1', fn() => new ScholarshipStudent('X', 'X', 1.0, -1), 'InvalidArgumentException');
check('T08b', 'Diskon 101', fn() => new ScholarshipStudent('X', 'X', 1.0, 101), 'InvalidArgumentException');
check('T09a', 'summary() RegularStudent', fn() => $reg->summary(), 'M01 - Alya');
check('T09b', 'summary() ScholarshipStudent', fn() => $sch->summary(), 'M02 - Bima [Scholarship]');
check('T10', 'Override final (eksperimen)', function () {
    $out = shell_exec('php ' . escapeshellarg(__DIR__ . '/eksperimen/01_override_final.php') . ' 2>&1');
    return str_contains((string)$out, 'Cannot override final method') ? 'Fatal error: final' : 'tidak error';
}, 'Fatal error: final');

// Tugas 2 (3 argumen)
check('TG2a', 'showData(id,name,major)', fn() => $p->showData('2530521036', 'Dida', 'Teknik Informatika'), 'ID: 2530521036, Name: Dida, Major: Teknik Informatika');
check('TG2b', 'showData 4 argumen', fn() => $p->showData('a', 'b', 'c', 'd'), 'InvalidArgumentException');
check('TG2c', 'showData("a","b",5) tipe salah', fn() => $p->showData('a', 'b', 5), 'InvalidArgumentException');

// Tugas 3: repository tidak tahu nama class child
$repo = new StudentRepository();
foreach ([$reg, $sch, $intl] as $s) $repo->create($s);
check('TG3a', 'InternationalStudent fee (5jt*1.25+1jt)', fn() => $intl->calculateFee(), '7250000');
check('TG3b', 'Repository total fee 3 jenis mahasiswa', fn() => $repo->totalFee(), '15250000');
check('TG3c', 'ID duplikat ditolak', fn() => $repo->create(new RegularStudent('M01', 'Dup', 1.0)), 'RuntimeException');
check('TG3d', 'delete ID tak ada', fn() => $repo->delete('ZZZ'), 'OutOfBoundsException');

// Latihan docx
$k = new Kalkulator();
check('L1', 'tambah(1,2,3,4)', fn() => $k->tambah(1, 2, 3, 4), '10');
check('L2', 'tambah(10)', fn() => $k->tambah(10), 'BadMethodCallException');
check('L3', 'Ayam->suara()', fn() => (new Ayam())->suara(), 'Kukuruyuk!');
check('L4', 'Kucing tanpa override', fn() => (new KucingTanpaOverride())->suara(), 'Hewan mengeluarkan suara');

// Tugas 4
check('TG4a', 'private bukan override', fn() => (new CustomReport())->describe(), 'secret milik BaseReport');
foreach (['02_visibility_lebih_ketat' => 'Access level', '03_signature_tidak_kompatibel' => 'must be compatible', '04_return_type_tidak_kompatibel' => 'must be compatible'] as $f => $needle) {
    check('TG4-' . substr($f, 0, 2), "eksperimen $f", function () use ($f, $needle) {
        $out = shell_exec('php ' . escapeshellarg(__DIR__ . "/eksperimen/$f.php") . ' 2>&1');
        return str_contains((string)$out, $needle) ? 'Fatal error' : 'tidak error';
    }, 'Fatal error');
}

echo PHP_EOL . "Ringkasan: $pass lulus, $fail gagal" . PHP_EOL;
exit($fail === 0 ? 0 : 1);
