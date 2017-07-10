<?php
include '../vendor/autoload.php';

$client = new \Pheanstalk\Pheanstalk('127.0.0.1');
$client->useTube('vickiqueue')->put(json_encode(['text' => 'Vickicryptobot: I am going long ETHUSD #ethereum']));
