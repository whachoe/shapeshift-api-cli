<?php
include_once "config.php";
include_once "lib.php";

set_time_limit(0);

$date = date("c");
echo "$date ;";
foreach ($wallets as $wallet) {
    $balance = getWalletAmount($wallet);
    echo "{$wallet['currency']}: $balance ; ";
}
echo "\n";