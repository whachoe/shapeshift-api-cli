<?php
include_once '../config.php';
include_once '../vendor/autoload.php';

$shifter = new \Shifters\Changelly\Changelly(CHANGELLY_API_KEY, CHANGELLY_SECRET_KEY);
$shifter->checkAvailability('eth', 'xmr');
