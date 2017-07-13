<?php
include_once "config.php";
include_once "vendor/autoload.php";
require_once "lib.php";

set_time_limit(0);

// For these coins, we can do swaps
$possibleSwaps = array_keys($wallets);

switch (SHIFTER) {
    case SHIFTER_SHAPESHIFT:
        $shifter = new \Shifters\Shapeshift\Shapeshift();
        break;
    case SHIFTER_CHANGELLY:
        $shifter = new \Shifters\Changelly\Changelly(CHANGELLY_API_KEY, CHANGELLY_SECRET_KEY);
        break;
}

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

// Let the shifter do some checks on limits, rates...
$amountToShift = $shifter->checkAmount($paymentProcessor, $pair);

// Get wallet amount for input
$walletAmount = $paymentProcessor->getWalletAmount();

// Ask for shift
if ($amountToShift > 0) {
    if (!$shifter->doShift($wallets[$input], $wallets[$output], $pair, $amountToShift)) {
        logger("Failed to shift: $input -> $output (".strval($amountToShift). "). Balance of wallet: $walletAmount");
        exit();
    } else {
        write_transaction_log($wallets[$input], $wallets[$output], $paymentProcessor::fromBase($amountToShift));
    }
} else {
    logger("Error: Amount was 0 or negative:".strval($amountToShift));
    exit();
}