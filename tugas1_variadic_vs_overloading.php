<?php
declare(strict_types=1);
require __DIR__ . '/src/Calculator.php';

/* Tugas 1: variadic != method overloading tradisional.
 * Java/C++: add(int,int) dan add(int,int,int) = DUA deklarasi, kompiler memilih.
 * PHP     : add(int ...$numbers)              = SATU deklarasi.            */
$calc = new Calculator();
echo "add(10,20)    = " . $calc->add(10, 20) . PHP_EOL;
echo "add(10,20,30) = " . $calc->add(10, 20, 30) . PHP_EOL;
echo "add()         = " . $calc->add() . PHP_EOL;                // 0: tetap sah, tidak ada "overload" kosong
echo "add(1..6)     = " . $calc->add(1, 2, 3, 4, 5, 6) . PHP_EOL;

// Bukti: PHP menolak dua deklarasi method sama dalam satu class
$rm = new ReflectionClass(Calculator::class);
echo "Jumlah deklarasi add() pada Calculator: " . count(array_filter($rm->getMethods(), fn($m) => $m->name === 'add')) . PHP_EOL;

// Bukti: variadic memakai tipe tunggal; tipe berbeda tidak 'dipilihkan' overload-nya
try { $calc->add(1, "dua"); } catch (TypeError $e) { echo "TypeError: " . $e->getMessage() . PHP_EOL; }
