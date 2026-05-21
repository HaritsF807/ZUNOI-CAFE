<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$collection = collect();
echo "Empty collect: " . json_encode($collection) . "\n";
$unserialized = unserialize(serialize($collection));
echo "Unserialized empty: " . json_encode($unserialized) . "\n";

$collection2 = collect([
    (object)['id' => 1, 'name' => 'Promo 1']
]);
echo "1 item collect: " . json_encode($collection2) . "\n";
$unserialized2 = unserialize(serialize($collection2));
echo "Unserialized 1 item: " . json_encode($unserialized2) . "\n";

