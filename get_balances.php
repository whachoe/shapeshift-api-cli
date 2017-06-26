<?php
include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

/**
 * Show the balances of all wallets in a CSV-format
 */

set_time_limit(0);

$options = parseArgs($argv);

if (isset($options['--help'])) {
    echo "Syntax: {$argv[0]} [--print-header]\n";
    exit();
}

// Get conversion rates
$from = array_keys($wallets);
$to = array_keys($wallets);
$to[] = 'EUR';
$to[] = 'USD';

$rates = getMultipleExchangeRates($from, $to);

// Header
if (isset($options['print-header'])) {
    echo "Date;";
    echo implode(";",array_keys($wallets));
    echo ";EUR Total\n";
}

// Data
$euroTotal = 0.0;
$date = date("c");
echo "$date;";
foreach ($wallets as $wallet) {
    $p = \Payment\Payment::factory($wallet);
    $balance = $p->getWalletAmountFriendly($wallet);
    $euroTotal += $balance * $rates[strtoupper($wallet['currency'])]['EUR'];
    echo "$balance;";
}
echo "$euroTotal;\n";