<?php
/**
 * Created by PhpStorm.
 * User: cjpa
 * Date: 10/07/2017
 * Time: 18:35
 */
include_once '../vendor/autoload.php';

$client = new \Pheanstalk\Pheanstalk('127.0.0.1');
for ($i=0; $i < 10; $i++) {
    $client->put($i);
    sleep(5);
}