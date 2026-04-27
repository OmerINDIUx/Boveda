<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$disciplines = App\Models\Discipline::all();
$unique = [];
foreach($disciplines as $d) {
    if(in_array($d->name, $unique)) {
        $d->delete();
    } else {
        $unique[] = $d->name;
    }
}
echo "Deduplicated disciplines.\n";
