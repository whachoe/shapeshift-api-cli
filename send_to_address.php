<?php
include_once "config.php";
include_once "lib.php";

set_time_limit(0);

// Get commandline options
$options = parseArgs($argv);
if (!$options['from'] || !$options['to'] || !$options['amount']) {
    echo "Syntax: {$argv[0]} --from=btc --to=wallet-address --amount=3.054 --fee=0.05";
    exit();
}

$wallet = $wallets[strtolower($options['from'])];
if (!$wallet) {
    echo "Wallet not found for {$options['from']}. Exiting.\n";
    exit();
}

sendToAddress($wallet, $options['to'], $options['amount'], $options['fee']);