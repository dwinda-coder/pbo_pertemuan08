<?php
declare(strict_types=1);
require __DIR__ . '/src/StudentRepository.php';
require __DIR__ . '/src/StudentPresenter.php';

$repo = new StudentRepository();
$repo->create(new RegularStudent('M01', 'Dwinda', 5000000));
$repo->create(new ScholarshipStudent('M02', 'Bima', 5000000, 40));
$repo->create(new InternationalStudent('M03', 'Chen', 5000000, 'China'));

foreach ($repo->all() as $s) {
    printf('%s | Rp%s%s', $s->summary(), number_format($s->calculateFee(), 0, ',', '.'), PHP_EOL);
}
printf('Total: Rp%s%s', number_format($repo->totalFee(), 0, ',', '.'), PHP_EOL);

$view = new StudentPresenter();
echo $view->showData('M01') . PHP_EOL;
echo $view->showData('M01', 'Dwinda') . PHP_EOL;
echo $view->showData('M01', 'Dwinda', 'Teknik Informatika') . PHP_EOL;
