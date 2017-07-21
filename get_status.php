<?php

include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

set_time_limit(0);

// Get commandline options
$options = parseArgs($argv);
if (isset($options['help']) || !(isset($options['address']) || isset($options['orderid']))) {
    echo "Syntax: {$argv[0]} --address=<address of shapeshift> | --orderid=<changelly order id>";
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
        if (isset($options['address'])) {
            $response = $shifter->getTransactions(null, $options['address']);
            var_dump($response);
            if ($response && isset($response['result'])) {
                $response2 = $shifter->getStatus($response[0]['id']);
                var_dump($response2);
            }
        } elseif (isset($options['orderid'])) {
            $response2 = $shifter->getStatus($options['orderid']);
            var_dump($response2);
        }
        break;
}

