<?php

include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

set_time_limit(0);

// Get commandline options
$options = parseArgs($argv);
if (isset($options['help']) || !$options['address']) {
    echo "Syntax: {$argv[0]} --address=<address of shapeshift>";
    exit();
}

$shifter = new \Shapeshift\Shapeshift();
logger("get_status: {$options['address']}");
$response = $shifter->getStatusOfDeposit($options['address']);
var_dump($response);
