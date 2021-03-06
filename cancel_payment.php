<?php
include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

set_time_limit(0);

// Get commandline options
$options = parseArgs($argv);
if (!$options[0] || $options['--help']) {
    echo "Syntax: {$argv[0]} address";
    exit();
}

$shifter = new \Shapeshift\Shapeshift();

if ($shifter->cancelPending($address)) {
    logger("Order cancelled");
} else {
    logger("Order not cancelled");
}
