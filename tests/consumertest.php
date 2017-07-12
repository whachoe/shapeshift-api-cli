<?php
require '../vendor/autoload.php';

$client = new \Pheanstalk\Pheanstalk('127.0.0.1');

while ($job = $client->reserve()) {
    var_dump($job);
    $client->delete($job);
}