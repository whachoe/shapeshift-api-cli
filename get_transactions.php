<?php

include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

use Pheanstalk\Pheanstalk;

set_time_limit(0);

// Get commandline options
$options = parseArgs($argv);
if (isset($options['help']) || !isset($options['address'])) {
    echo "Syntax: {$argv[0]} --address=<address of shapeshift>";
    exit();
}

switch (SHIFTER) {
    case SHIFTER_SHAPESHIFT:
        echo "Not implemented for Shapeshift";
        break;
    case SHIFTER_CHANGELLY:
        $client = new Pheanstalk('127.0.0.1');
        $response = [
            'result' => ['address' => $options['address']]
        ];

        $client->putInTube('changelly_orderstatus', json_encode($response), Pheanstalk::DEFAULT_PRIORITY);

        break;
}

