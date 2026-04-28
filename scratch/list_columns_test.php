<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

// Switch to testing database
Config::set('database.connections.mysql.database', 'db_ged_test');
DB::purge('mysql');

try {
    $columns = Schema::getColumnListing('reminders');
    echo "Columns in 'reminders' table in db_ged_test:\n";
    print_r($columns);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
