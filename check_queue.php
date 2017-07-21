<?php
include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

use Pheanstalk\Pheanstalk;

// Get commandline options
$options = parseArgs($argv);
if (isset($options['help']) || !isset($options['queue'])) {
    echo "Syntax: {$argv[0]} --queue=<name of queue>";
    exit();
}

$q = new Pheanstalk('127.0.0.1');
//$q->useTube($options['queue']);
var_dump($q->statsTube($options['queue']));