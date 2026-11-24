<?php
declare(strict_types=1);
require __DIR__ . '/src/Calculator.php';
require __DIR__ . '/src/StudentPresenter.php';
require __DIR__ . '/src/StudentRepository.php';
require __DIR__ . '/src/Hewan.php';
require __DIR__ . '/src/BaseReport.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function rupiah(float $n): string { return 'Rp' . number_format($n, 0, ',', '.'); }
function run(callable $fn): string {
    try { return (string)$fn(); } catch (Throwable $t) { return get_class($t); }
}
function runFile(string $file): string {
    $fn = 'shell_exec';
    if (!function_exists($fn) || in_array($fn, array_map('trim', explode(',', (string)ini_get('disable_functions'))), true)) {
        return '(shell_exec dinonaktifkan di server ini; jalankan lewat terminal: php eksperimen/' . basename($file) . ')';
    }
    $php = (PHP_SAPI === 'cli' || !PHP_BINARY || stripos(PHP_BINARY, 'php-cgi') !== false || stripos(PHP_BINARY, 'httpd') !== false) ? 'php' : PHP_BINARY;
    $out = @shell_exec(escapeshellarg($php) . ' -d log_errors=0 -d display_errors=1 -d html_errors=0 ' . escapeshellarg($file) . ' 2>&1');
    return trim((string)$out) !== '' ? trim(str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', (string)$out)) : '(tidak ada output / php CLI tidak ditemukan)';
}

// ---- Tugas 1
$calc = new Calculator();
$t1 = [
    'add(10, 20)' => $calc->add(10, 20),
    'add(10, 20, 30)' => $calc->add(10, 20, 30),
    'add()' => $calc->add(),
    'add(1, 2, 3, 4, 5, 6)' => $calc->add(1, 2, 3, 4, 5, 6),
];
$jmlAdd = count(array_filter((new ReflectionClass(Calculator::class))->getMethods(), fn($m) => $m->name === 'add'));
$typeErr = run(function () use ($calc) { $calc->add(1, 'dua'); });

// ---- Tugas 2
$p = new StudentPresenter();
$t2 = [
    ["showData('Dida')", fn() => $p->showData('Dida')],
    ["showData('2530521036', 'Dida')", fn() => $p->showData('2530521036', 'Dida')],
    ["showData('2530521036', 'Dida', 'Tejnik Informatika')", fn() => $p->showData('2530521036', 'Dida', 'Teknik Informatika')],
    ["showData()", fn() => $p->showData()],
    ["showData(123)", fn() => $p->showData(123)],
    ["showData('a', 'b', 5)", fn() => $p->showData('a', 'b', 5)],
    ["showData('a', 'b', 'c', 'd')", fn() => $p->showData('a', 'b', 'c', 'd')],
    ["hapus('x')", fn() => $p->hapus('x')],
];

// ---- Tugas 3 / CRUD
$repo = new StudentRepository();
$reg = new RegularStudent('M01', 'Dwinda', 5000000);
$sch = new ScholarshipStudent('M02', 'Bima', 5000000, 40);
$intl = new InternationalStudent('M03', 'Chen', 5000000, 'China');
foreach ([$reg, $sch, $intl] as $s) $repo->create($s);

// ---- Overriding hewan
$hewan = [new Hewan(), new Kucing(), new Anjing(), new Ayam(), new KucingTanpaOverride()];

// ---- Pengujian
$tests = [];
$add = function (string $id, string $sk, callable $fn, string $exp) use (&$tests) {
    $act = run($fn);
    $tests[] = [$id, $sk, $exp, $act, $act === $exp];
};
$add('T01a', 'add(10,20)', fn() => $calc->add(10, 20), '30');
$add('T01b', 'add(10,20,30)', fn() => $calc->add(10, 20, 30), '60');
$add('T02', 'showData("Dida")', fn() => $p->showData('Dida'), 'Name: Dida');
$add('T03', 'showData("2530521036","Dida")', fn() => $p->showData('2530521036', 'Dida'), 'ID: 2530521036, Name: Dida');
$add('T04', 'showData() tanpa argumen', fn() => $p->showData(), 'InvalidArgumentException');
$add('T05', 'Method tidak dikenal', fn() => $p->hapus('x'), 'BadMethodCallException');
$add('T06', 'RegularStudent fee 5 juta', fn() => $reg->calculateFee(), '5000000');
$add('T07', 'ScholarshipStudent diskon 40%', fn() => $sch->calculateFee(), '3000000');
$add('T08a', 'Diskon -1', fn() => new ScholarshipStudent('X', 'X', 1.0, -1), 'InvalidArgumentException');
$add('T08b', 'Diskon 101', fn() => new ScholarshipStudent('X', 'X', 1.0, 101), 'InvalidArgumentException');
$add('T09a', 'summary() Regular', fn() => $reg->summary(), 'M01 - Dwinda');
$add('T09b', 'summary() Scholarship', fn() => $sch->summary(), 'M02 - Bima [Scholarship]');
$add('TG3', 'InternationalStudent fee', fn() => $intl->calculateFee(), '7250000');
$add('L1', 'tambah(1,2,3,4)', fn() => (new Kalkulator())->tambah(1, 2, 3, 4), '10');
$add('L2', 'tambah(10)', fn() => (new Kalkulator())->tambah(10), 'BadMethodCallException');
$add('TG4', 'private bukan override', fn() => (new CustomReport())->describe(), 'secret milik BaseReport');
$lulus = count(array_filter($tests, fn($t) => $t[4]));

$eksp = [
    '01_override_final.php' => 'Override method final',
    '02_visibility_lebih_ketat.php' => 'Visibility lebih ketat',
    '03_signature_tidak_kompatibel.php' => 'Tipe parameter tidak kompatibel',
    '04_return_type_tidak_kompatibel.php' => 'Return type tidak kompatibel',
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Praktikum PBO 8 — Overloading &amp; Overriding</title>
<style>
:root{--bg:#f6f7f9;--card:#fff;--ink:#1d2330;--mut:#5d6677;--line:#e2e6ee;--acc:#3b5bdb;--ok:#2b8a3e;--bad:#c92a2a;--code:#f1f3f9}
@media(prefers-color-scheme:dark){:root{--bg:#12151c;--card:#1b2029;--ink:#e8ebf2;--mut:#9aa4b8;--line:#2b3240;--acc:#7c93ff;--ok:#51cf66;--bad:#ff8787;--code:#10131a}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.6 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
header,main{max-width:980px;margin:0 auto;padding:0 16px}
header{padding-top:32px}
h1{font-size:1.7rem;margin:0 0 4px}
.sub{color:var(--mut);margin:0 0 16px}
nav{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 24px}
nav a{font-size:.85rem;padding:4px 10px;border:1px solid var(--line);border-radius:999px;color:var(--acc);text-decoration:none;background:var(--card)}
section{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;margin-bottom:20px}
h2{margin:0 0 8px;font-size:1.2rem}
p{margin:.4rem 0 .8rem}
pre,code{font-family:ui-monospace,Consolas,monospace;font-size:.88rem}
pre{background:var(--code);border:1px solid var(--line);border-radius:8px;padding:12px;overflow-x:auto;margin:8px 0}
table{border-collapse:collapse;width:100%;font-size:.92rem;display:block;overflow-x:auto}
th,td{border-bottom:1px solid var(--line);padding:8px 10px;text-align:left;vertical-align:top}
th{color:var(--mut);font-weight:600}
.ok{color:var(--ok);font-weight:600}.bad{color:var(--bad);font-weight:600}
.exc{color:var(--bad);font-family:ui-monospace,Consolas,monospace}
.summary{font-weight:600}
footer{max-width:980px;margin:0 auto;padding:0 16px 40px;color:var(--mut);font-size:.85rem}
</style>
</head>
<body>
<header>
  <h1>Praktikum PBO Pertemuan 8 — Overloading &amp; Overriding</h1>
  <p class="sub">Pemrograman Berorientasi Objek · PHP <?= e(PHP_VERSION) ?> · semua output di halaman ini dihasilkan langsung oleh kode PHP.</p>
  <nav>
    <a href="#t1">Tugas 1</a><a href="#t2">Tugas 2</a><a href="#t3">Tugas 3</a><a href="#t4">Tugas 4</a><a href="#t5">Tugas 5</a><a href="#hewan">Latihan Hewan</a><a href="#uji">Hasil Uji</a>
  </nav>
</header>
<main>

<section id="t1">
  <h2>Tugas 1 — Variadic ≠ method overloading tradisional</h2>
  <p>Di Java/C++ <code>add(int,int)</code> dan <code>add(int,int,int)</code> adalah dua deklarasi. Di PHP hanya ada <strong>satu</strong> <code>add(int ...$numbers)</code>.</p>
  <pre>public function add(int ...$numbers): int { return array_sum($numbers); }</pre>
  <table><tr><th>Pemanggilan</th><th>Hasil</th></tr>
  <?php foreach ($t1 as $k => $v): ?><tr><td><code><?= e($k) ?></code></td><td><?= e((string)$v) ?></td></tr><?php endforeach; ?>
  </table>
  <p>Jumlah deklarasi <code>add()</code> menurut Reflection: <strong><?= $jmlAdd ?></strong>. Argumen bertipe berbeda: <code>add(1, 'dua')</code> → <span class="exc"><?= e($typeErr) ?></span>.</p>
</section>

<section id="t2">
  <h2>Tugas 2 — <code>showData()</code> via <code>__call()</code></h2>
  <p>Menerima 1, 2, atau 3 string. Nama method salah → <code>BadMethodCallException</code>; jumlah/tipe argumen salah → <code>InvalidArgumentException</code>.</p>
  <table><tr><th>Pemanggilan</th><th>Hasil</th></tr>
  <?php foreach ($t2 as [$label, $fn]): $r = run($fn); $isExc = str_ends_with($r, 'Exception'); ?>
    <tr><td><code><?= e($label) ?></code></td><td class="<?= $isExc ? 'exc' : '' ?>"><?= e($r) ?></td></tr>
  <?php endforeach; ?>
  </table>
</section>

<section id="t3">
  <h2>Tugas 3 — Polymorphism &amp; Repository CRUD</h2>
  <p><code>StudentRepository</code> hanya mengenal tipe <code>Student</code>; <code>InternationalStudent</code> ditambahkan tanpa mengubah repository.</p>
  <table><tr><th>Ringkasan (<code>summary()</code>)</th><th>Kelas</th><th>Biaya (<code>calculateFee()</code>)</th></tr>
  <?php foreach ($repo->all() as $s): ?>
    <tr><td><?= e($s->summary()) ?></td><td><code><?= e($s::class) ?></code></td><td><?= e(rupiah($s->calculateFee())) ?></td></tr>
  <?php endforeach; ?>
    <tr><th colspan="2">Total</th><th><?= e(rupiah($repo->totalFee())) ?></th></tr>
  </table>
  <p>Biaya mahasiswa internasional = biaya dasar + 25% + administrasi Rp1.000.000.</p>
</section>

<section id="t4">
  <h2>Tugas 4 — Dampak <code>final</code>, <code>private</code>, dan signature</h2>
  <p>Setiap kasus dijalankan dari file terpisah di folder <code>eksperimen/</code>:</p>
  <table><tr><th>Kasus</th><th>Output PHP</th></tr>
  <?php foreach ($eksp as $f => $label): ?>
    <tr><td><?= e($label) ?></td><td><pre><?= e(runFile(__DIR__ . '/eksperimen/' . $f)) ?></pre></td></tr>
  <?php endforeach; ?>
    <tr><td>Method <code>private</code> parent dideklarasi ulang</td><td>Valid, bukan override: <code>describe()</code> → <strong><?= e((new CustomReport())->describe()) ?></strong></td></tr>
  </table>
</section>

<section id="t5">
  <h2>Tugas 5 — Kapan memakai apa?</h2>
  <table><tr><th>Mekanisme</th><th>Dipakai ketika</th></tr>
    <tr><td>Method biasa</td><td>Perilaku tunggal tanpa variasi antar tipe (default).</td></tr>
    <tr><td>Overriding</td><td>Beberapa tipe objek berperilaku berbeda untuk kontrak yang sama, dipakai lewat tipe parent (<code>calculateFee()</code>).</td></tr>
    <tr><td><code>__call()</code></td><td>Komponen pendukung yang butuh antarmuka dinamis dengan validasi ketat (<code>StudentPresenter</code>). Hindari untuk logika bisnis inti: tidak ada autocomplete IDE, lolos analisis statis, error baru muncul saat runtime.</td></tr>
  </table>
</section>

<section id="hewan">
  <h2>Latihan — Overriding pada Hewan</h2>
  <table><tr><th>Objek</th><th><code>suara()</code></th></tr>
  <?php foreach ($hewan as $h): ?><tr><td><code><?= e($h::class) ?></code></td><td><?= e($h->suara()) ?></td></tr><?php endforeach; ?>
  </table>
  <p><code>KucingTanpaOverride</code> tidak mendefinisikan ulang <code>suara()</code>, sehingga memakai implementasi <code>Hewan</code>.</p>
</section>

<section id="uji">
  <h2>Hasil Pengujian</h2>
  <p class="summary"><?= $lulus ?> dari <?= count($tests) ?> pengujian lulus.</p>
  <table><tr><th>ID</th><th>Skenario</th><th>Diharapkan</th><th>Hasil</th><th>Status</th></tr>
  <?php foreach ($tests as [$id, $sk, $exp, $act, $ok]): ?>
    <tr><td><?= e($id) ?></td><td><?= e($sk) ?></td><td><?= e($exp) ?></td><td><?= e($act) ?></td><td class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'Lulus' : 'Gagal' ?></td></tr>
  <?php endforeach; ?>
  </table>
</section>

</main>
<footer>Jalankan dengan <code>php -S localhost:8000</code> di folder ini, atau taruh folder di <code>htdocs</code> XAMPP lalu buka <code>http://localhost/pertemuan08/</code>.</footer>
</body>
</html>
