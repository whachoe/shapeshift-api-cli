<?php
include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

set_time_limit(0);

// Get commandline options
$options = parseArgs($argv);
if (isset($options['help']) || !$options['from'] || !$options['to'] || !$options['amount']) {
    echo "Syntax: {$argv[0]} --from=btc --to=the_destination_btc_address --amount=3.054 --fee=0.05 --paymentID=payment_ID_for_XMR_transaction";
    exit();
}

if (!isset($options['fee'])) {
    $options['fee'] = 0;
}

$wallet = $wallets[strtolower($options['from'])];
if (!$wallet) {
    echo "Wallet not found for {$options['from']}. Exiting.\n";
    exit();
}

$paymentProcessor = Payment\Payment::factory($wallet);
$paymentProcessor->toAddress = $options['to'];
$paymentProcessor->amount = $options['amount'];
if ($paymentProcessor instanceof \Payment\XMRPayment) {
    $paymentProcessor->paymentID = $options['paymentID'];
}
$paymentProcessor->send();