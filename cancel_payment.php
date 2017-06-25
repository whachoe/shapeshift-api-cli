<?php
include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

set_time_limit(0);

// Get commandline options
$options = parseArgs($argv);
if (!$options[1]) {
    echo "Syntax: {$argv[0]} address";
    exit();
}

$shifter = new \Shapeshift\Shapeshift();

if ($shifter->cancelPending($address)) {
    echo "Order cancelled";
} else {
    echo "Order not cancelled";
}
