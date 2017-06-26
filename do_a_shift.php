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
    echo "Specify input and output currency";
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
    echo "$input or $output is not available on Shapeshift. Exiting\n";
    exit();
}

if ($input == $output) {
    echo "Can't switch between the same currencies ($input). Exiting\n";
    exit();
}

// Check if we can shift for this pair
if (!(in_array($input, $possibleSwaps) && in_array($output, $possibleSwaps))) {
    echo "You're trying to shift an unsupported currency. Please follow up manually\n";
    echo "Possible currencies: ".implode(",", $possibleSwaps);
    exit();
}

echo "Working on shifting: $input to $output\n";

$paymentProcessor = \Payment\Payment::factory($wallets[$input]);

// pair = input_output
$pair = "{$input}_{$output}";

// First getting some info from shapeshift
$marketInfo = $shifter->getMarketInfo($pair);

if (!$marketInfo) {
    echo "No marketinfo found. Exiting";
    exit(1);
}

$rate = $marketInfo['rate'];
$limit = $paymentProcessor->toBase($marketInfo['limit']);
$min = $paymentProcessor->toBase($marketInfo['minimum']);
$minerFee = $paymentProcessor->toBase($marketInfo['minerFee']);

if (!$rate) {
    echo "No rate for $pair found. Exiting\n";
    exit(1);
}

if (!$limit || !$min) {
    echo "No valid limit ($limit) or minimum ($min) found. Exiting\n";
    exit(1);
}

// Get wallet amount for input
$walletAmount = $paymentProcessor->getWalletAmount();

// Make sure we have at least minimum to work with
if ($walletAmount < $min) {
    echo "Not enough in wallet. Min: $min\n";
    exit();
}

// amount_to_shift = min(limit, wallet_amount)
$amountToShift = min($walletAmount*90/100, $limit);

// Ask for shift
if ($amountToShift > 0) {
    if (!$shifter->doShift($wallets[$input], $wallets[$output], $pair, $amountToShift, $minerFee)) {
        echo "Failed to shift: $input -> $output (".strval($amountToShift). "). Balance of wallet: $walletAmount\n";
        exit();
    }
} else {
    echo "Error: Amount was 0 or negative:".strval($amountToShift)."\n";
    exit();
}