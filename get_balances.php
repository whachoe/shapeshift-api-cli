<?php
include_once "config.php";
include_once "lib.php";

/**
 * Show the balances of all wallets in a CSV-format
 */

set_time_limit(0);

echo "Date ;";
echo implode("; ",array_keys($wallets));
echo "; \n";

$date = date("c");
echo "$date ;";
foreach ($wallets as $wallet) {
    $balance = getWalletAmount($wallet);
    echo "{$wallet['currency']}: $balance ; ";
}
echo "\n";