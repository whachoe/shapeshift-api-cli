<?php
include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

set_time_limit(0);

// For these coins, we can do swaps
$possibleSwaps = array_keys($wallets);

$shifter = new \Shapeshift\Shapeshift();

// Get commandline options
$options = parseArgs($argv);
if (isset($options['help']) || !isset($options['input']) || !isset($options['output'])) {
    echo "Syntax: {$argv[0]} --input=btc --output=eth";
    echo "    This will try to shift as much coins as possible from the 'input' to the 'output' wallet.";
    exit();
}

$input = strtolower($options['input']);
$output = strtolower($options['output']);

if (strlen($input) < 3 || strlen($output) < 3) {
    logger("Specify input and output currency");
    exit();
}

// Switch USD for LTC
if ($input == 'usd') {
    $input = 'ltc';
}

if ($output == 'usd') {
    $output = 'ltc';
}

if (!$shifter->checkAvailability($input, $output)) {
    logger("$input or $output is not available on Shapeshift. Exiting.");
    exit();
}

if ($input == $output) {
    logger("Can't switch between the same currencies ($input). Exiting");
    exit();
}

// Check if we can shift for this pair
if (!(in_array($input, $possibleSwaps) && in_array($output, $possibleSwaps))) {
    logger("You're trying to shift an unsupported currency. Please follow up manually\nPossible currencies: ".implode(",", $possibleSwaps));
    exit();
}

logger("Working on shifting: $input to $output");

$paymentProcessor = \Payment\Payment::factory($wallets[$input]);

// pair = input_output
$pair = "{$input}_{$output}";

// First getting some info from shapeshift
$marketInfo = $shifter->getMarketInfo($pair);

if (!$marketInfo) {
    logger("No marketinfo found. Exiting.");
    exit(1);
}

$rate = $marketInfo['rate'];
$limit = $paymentProcessor->toBase($marketInfo['limit']);
$min = $paymentProcessor->toBase($marketInfo['minimum']);
$minerFee = $paymentProcessor->toBase($marketInfo['minerFee']);

if (!$rate) {
    logger("No rate for $pair found. Exiting");
    exit(1);
}

if (! "$limit" || ! "$min") {
    logger("No valid limit ($limit) or minimum ($min) found. Exiting.");
    exit(1);
}

// Get wallet amount for input
$walletAmount = $paymentProcessor->getWalletAmount();

// Make sure we have at least minimum to work with
if ($walletAmount < $min) {
    logger("Not enough in wallet. Min: $min");
    exit();
}

// amount_to_shift = min(limit, wallet_amount)
$amountToShift = min($walletAmount*90/100, $limit);

// Ask for shift
if ($amountToShift > 0) {
    if (!$shifter->doShift($wallets[$input], $wallets[$output], $pair, $amountToShift, $minerFee)) {
        logger("Failed to shift: $input -> $output (".strval($amountToShift). "). Balance of wallet: $walletAmount");
        exit();
    } else {
        write_transaction_log($wallets[$input], $wallets[$output], $amountToShift);
    }
} else {
    logger("Error: Amount was 0 or negative:".strval($amountToShift));
    exit();
}