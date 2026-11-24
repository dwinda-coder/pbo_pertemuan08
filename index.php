<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/src/StudentStorage.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function rupiah(float $n): string { return 'Rp' . number_format($n, 0, ',', '.'); }

$storage = new StudentStorage(__DIR__ . '/data/mahasiswa.json');
$repo = $storage->load();
$errors = [];
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$jurusan = ['Teknik Informatika', 'Teknik Sipil', 'kimia', 'Manajemen', 'Akuntansi', 'Pendidikan Guru', 'Lainnya'];
$form = ['id' => '', 'name' => '', 'major' => '', 'baseFee' => '5000000', 'beasiswa' => 'tidak', 'discount' => '25', 'mode' => 'add'];

// Edit: isi form dari data yang ada
if (isset($_GET['edit']) && ($s = $repo->find((string)$_GET['edit']))) {
    $d = $s instanceof ScholarshipStudent ? $s->getDiscountPercent() : 0;
    $form = ['id' => $s->getId(), 'name' => $s->getName(), 'major' => $s->getMajor(),
        'baseFee' => (string)(int)$s->getBaseFee(),
        'beasiswa' => $d >= 100 ? 'full' : ($d > 0 ? 'potongan' : 'tidak'),
        'discount' => $d > 0 && $d < 100 ? (string)$d : '25', 'mode' => 'edit'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    try {
        if ($act === 'delete') {
            $repo->delete((string)($_POST['id'] ?? ''));
            $storage->save($repo);
            $_SESSION['flash'] = 'Data mahasiswa dihapus.';
            header('Location: index.php'); exit;
        }
        if ($act === 'reset') {
            $storage->save(new StudentRepository());
            $_SESSION['flash'] = 'Semua data dihapus.';
            header('Location: index.php'); exit;
        }
        if ($act === 'save') {
            $form = [
                'id' => trim((string)($_POST['id'] ?? '')), 'name' => trim((string)($_POST['name'] ?? '')),
                'major' => trim((string)($_POST['major'] ?? '')), 'baseFee' => trim((string)($_POST['baseFee'] ?? '')),
                'beasiswa' => (string)($_POST['beasiswa'] ?? 'tidak'), 'discount' => trim((string)($_POST['discount'] ?? '')),
                'mode' => ($_POST['mode'] ?? 'add') === 'edit' ? 'edit' : 'add',
            ];
            if ($form['id'] === '' || !preg_match('/^[A-Za-z0-9._-]{1,20}$/', $form['id'])) $errors[] = 'NIM wajib diisi (huruf/angka, maksimal 20 karakter).';
            if ($form['name'] === '') $errors[] = 'Nama wajib diisi.';
            if ($form['major'] === '') $errors[] = 'Jurusan wajib diisi.';
            if (!is_numeric($form['baseFee']) || (float)$form['baseFee'] < 0) $errors[] = 'Biaya kuliah harus berupa angka (tidak negatif).';
            $disc = 0.0;
            if ($form['beasiswa'] === 'full') $disc = 100.0;
            elseif ($form['beasiswa'] === 'potongan') {
                if (!is_numeric($form['discount']) || (float)$form['discount'] <= 0 || (float)$form['discount'] >= 100)
                    $errors[] = 'Persentase potongan harus antara 1 dan 99.';
                else $disc = (float)$form['discount'];
            }
            if (!$errors) {
                $student = $storage->build(['id' => $form['id'], 'name' => $form['name'], 'major' => $form['major'],
                    'baseFee' => (float)$form['baseFee'], 'discount' => $disc]);
                $form['mode'] === 'edit' ? $repo->update($student) : $repo->create($student);
                $storage->save($repo);
                $_SESSION['flash'] = $form['mode'] === 'edit' ? 'Data berhasil diperbarui.' : 'Mahasiswa berhasil ditambahkan.';
                header('Location: index.php'); exit;
            }
        }
    } catch (Throwable $t) {
        $errors[] = $t->getMessage();
    }
}
$all = $repo->all();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Mahasiswa — Praktikum PBO 8</title>
<style>
:root{--bg:#f6f7f9;--card:#fff;--ink:#1d2330;--mut:#5d6677;--line:#e2e6ee;--acc:#3b5bdb;--accink:#fff;--ok:#2b8a3e;--bad:#c92a2a;--badbg:#fff0f0;--okbg:#ebfbee}
@media(prefers-color-scheme:dark){:root{--bg:#12151c;--card:#1b2029;--ink:#e8ebf2;--mut:#9aa4b8;--line:#2b3240;--acc:#7c93ff;--accink:#10131a;--ok:#51cf66;--bad:#ff8787;--badbg:#2a1a1a;--okbg:#14261a}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.6 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
.wrap{max-width:980px;margin:0 auto;padding:28px 16px 48px}
h1{font-size:1.6rem;margin:0}
.sub{color:var(--mut);margin:4px 0 20px}
.sub a{color:var(--acc)}
section{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;margin-bottom:20px}
h2{margin:0 0 12px;font-size:1.15rem}
form.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.full{grid-column:1/-1}
label{display:block;font-size:.88rem;color:var(--mut);margin-bottom:4px}
input[type=text],input[type=number],select{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:8px;background:var(--bg);color:var(--ink);font:inherit}
input:focus,select:focus{outline:2px solid var(--acc);outline-offset:1px}
.radios{display:flex;flex-wrap:wrap;gap:16px}
.radios label{display:flex;align-items:center;gap:6px;color:var(--ink);font-size:1rem;margin:0}
button,.btn{font:inherit;padding:9px 16px;border-radius:8px;border:1px solid var(--line);background:var(--card);color:var(--ink);cursor:pointer;text-decoration:none;display:inline-block}
button.primary{background:var(--acc);color:var(--accink);border-color:var(--acc)}
button.danger{color:var(--bad)}
.actions{display:flex;gap:10px;flex-wrap:wrap}
.msg{padding:10px 14px;border-radius:8px;margin-bottom:16px}
.err{background:var(--badbg);color:var(--bad)}.ok{background:var(--okbg);color:var(--ok)}
.err ul{margin:0;padding-left:18px}
table{border-collapse:collapse;width:100%;font-size:.93rem;display:block;overflow-x:auto}
th,td{border-bottom:1px solid var(--line);padding:9px 10px;text-align:left;white-space:nowrap}
th{color:var(--mut);font-weight:600}
td.num{text-align:right}
.badge{font-size:.78rem;padding:2px 8px;border-radius:999px;border:1px solid var(--line);color:var(--mut)}
.badge.b{color:var(--ok);border-color:var(--ok)}
.empty{color:var(--mut);text-align:center;padding:24px 0}
.row-actions{display:flex;gap:6px}
.row-actions form{margin:0}
.row-actions button,.row-actions a{padding:4px 10px;font-size:.85rem}
.total{display:flex;justify-content:space-between;margin-top:12px;font-weight:600;flex-wrap:wrap;gap:8px}
#disc-box{display:none}
@media(max-width:620px){form.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <h1>Data Mahasiswa</h1>
  <p class="sub">Praktikum PBO Pertemuan 8 · isi form, data tampil di tabel bawah. <a href="demo.php">Lihat demo overloading &amp; overriding</a></p>

  <?php if ($flash): ?><div class="msg ok"><?= e($flash) ?></div><?php endif; ?>
  <?php if ($errors): ?><div class="msg err"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

  <section>
    <h2><?= $form['mode'] === 'edit' ? 'Edit Mahasiswa' : 'Tambah Mahasiswa' ?></h2>
    <form class="grid" method="post" action="index.php" autocomplete="off">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="mode" value="<?= e($form['mode']) ?>">
      <div>
        <label for="id">NIM</label>
        <input type="text" id="id" name="id" value="<?= e($form['id']) ?>" <?= $form['mode'] === 'edit' ? 'readonly' : '' ?> required maxlength="20">
      </div>
      <div>
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" required>
      </div>
      <div>
        <label for="major">Jurusan</label>
        <input type="text" id="major" name="major" list="jurusan-list" value="<?= e($form['major']) ?>" placeholder="Pilih atau ketik jurusan" required>
        <datalist id="jurusan-list"><?php foreach ($jurusan as $j): ?><option value="<?= e($j) ?>"><?php endforeach; ?></datalist>
      </div>
      <div>
        <label for="baseFee">Biaya kuliah dasar (Rp)</label>
        <input type="number" id="baseFee" name="baseFee" min="0" step="1" value="<?= e($form['baseFee']) ?>" required>
      </div>
      <div class="full">
        <label>Beasiswa (opsional)</label>
        <div class="radios">
          <label><input type="radio" name="beasiswa" value="tidak" <?= $form['beasiswa'] === 'tidak' ? 'checked' : '' ?>> Tidak ada</label>
          <label><input type="radio" name="beasiswa" value="potongan" <?= $form['beasiswa'] === 'potongan' ? 'checked' : '' ?>> Potongan biaya</label>
          <label><input type="radio" name="beasiswa" value="full" <?= $form['beasiswa'] === 'full' ? 'checked' : '' ?>> Beasiswa penuh (gratis)</label>
        </div>
      </div>
      <div class="full" id="disc-box">
        <label for="discount">Besar potongan (%)</label>
        <input type="number" id="discount" name="discount" min="1" max="99" step="0.01" value="<?= e($form['discount']) ?>">
      </div>
      <div class="full actions">
        <button class="primary" type="submit"><?= $form['mode'] === 'edit' ? 'Simpan Perubahan' : 'Tambah' ?></button>
        <?php if ($form['mode'] === 'edit'): ?><a class="btn" href="index.php">Batal</a><?php endif; ?>
      </div>
    </form>
  </section>

  <section>
    <h2>Daftar Mahasiswa (<?= count($all) ?>)</h2>
    <?php if (!$all): ?>
      <p class="empty">Belum ada data. Isi form di atas untuk menambahkan mahasiswa.</p>
    <?php else: ?>
    <table>
      <tr><th>No</th><th>NIM</th><th>Nama</th><th>Jurusan</th><th>Beasiswa</th><th>Biaya Dasar</th><th>Biaya Dibayar</th><th></th></tr>
      <?php foreach ($all as $i => $s):
        $isBea = $s instanceof ScholarshipStudent;
        $label = !$isBea ? '—' : ($s->isFull() ? 'Penuh (100%)' : 'Potongan ' . rtrim(rtrim(number_format($s->getDiscountPercent(), 2, ',', ''), '0'), ',') . '%');
      ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= e($s->getId()) ?></td>
        <td><?= e($s->getName()) ?></td>
        <td><?= e($s->getMajor()) ?></td>
        <td><?= $isBea ? '<span class="badge b">' . e($label) . '</span>' : '—' ?></td>
        <td class="num"><?= e(rupiah($s->getBaseFee())) ?></td>
        <td class="num"><strong><?= e(rupiah($s->calculateFee())) ?></strong></td>
        <td><div class="row-actions">
          <a class="btn" href="index.php?edit=<?= urlencode($s->getId()) ?>">Edit</a>
          <form method="post" action="index.php" onsubmit="return confirm('Hapus data <?= e(addslashes($s->getName())) ?>?')">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($s->getId()) ?>">
            <button class="danger" type="submit">Hapus</button>
          </form>
        </div></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div class="total"><span>Total biaya dibayar: <?= e(rupiah($repo->totalFee())) ?></span>
      <form method="post" action="index.php" onsubmit="return confirm('Hapus SEMUA data?')"><input type="hidden" name="action" value="reset"><button class="danger" type="submit">Hapus semua</button></form>
    </div>
    <?php endif; ?>
  </section>
</div>
<script>
(function () {
  var radios = document.querySelectorAll('input[name=beasiswa]'), box = document.getElementById('disc-box');
  function sync() { var v = document.querySelector('input[name=beasiswa]:checked'); box.style.display = v && v.value === 'potongan' ? 'block' : 'none'; }
  radios.forEach(function (r) { r.addEventListener('change', sync); }); sync();
})();
</script>
</body>
</html>
