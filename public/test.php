<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
try {
    echo json_encode(Illuminate\Support\Facades\DB::select("SHOW TABLES LIKE '%recording%'"));
} catch (Exception $e) {
    echo $e->getMessage();
}
