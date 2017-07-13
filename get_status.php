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

switch (SHIFTER) {
    case SHIFTER_SHAPESHIFT:
        $shifter = new \Shifters\Shapeshift\Shapeshift();
        logger("get_status: {$options['address']}");
        $response = $shifter->getStatusOfDeposit($options['address']);
        var_dump($response);
        break;
    case SHIFTER_CHANGELLY:
        $shifter = new \Shifters\Changelly\Changelly(CHANGELLY_API_KEY, CHANGELLY_SECRET_KEY);
        $response = $shifter->getTransactions(null, $options['address']);
        var_dump($response);
        $response2 = $shifter->getStatus($response[0]['id']);
        var_dump($response2);
        break;
}

