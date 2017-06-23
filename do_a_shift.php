<?php
include_once "config.php";
include_once "lib.php";

set_time_limit(0);

// For these coins, we can do swaps
$possibleSwaps = array_keys($wallets);

$input = $argv[1];
$output = $argv[2];

if (!checkAvailability($input, $output)) {
    echo "$input or $output is not available on Shapeshift. Exiting\n";
    exit();
}

echo "Working on shifting: $input to $output\n";
if (strlen($input) < 3 || strlen($output) < 3) {
    echo "Specify input and output currency";
    exit();
}

// pair = input_output
$pair = "{$input}_{$output}";

// Check if we can shift for this pair
if (!(in_array($input, $possibleSwaps) && in_array($output, $possibleSwaps))) {
    echo "Input or output is USD . Please follow up manually\n";
    echo "Pair: $pair\n";
    exit();
}

$marketInfo = getMarketInfo($pair);
$rate = $marketInfo['rate'];
$limit = (float) $marketInfo['limit'];
$min = (float) $marketInfo['min'];
$minerFee = (float) $marketInfo['minerFee'];

if (!$rate) {
    echo "No rate for $pair found. Exiting\n";
    exit();
}

if (!$limit || !$min) {
    echo "No valid limit ($limit) or minimum ($min) found. Exiting\n";
    exit();
}

// Get wallet amount for input
$walletAmount = (float)getWalletAmount($wallets[$input]);

// Make sure we have at least minimum to work with
if ($walletAmount < $min) {
    echo "Not enough in wallet. Min: $min\n";
    exit();
}

// amount_to_shift = min(limit, wallet_amount)
$amountToShift = (float)min($walletAmount-$minerFee, $limit);

// Ask for shift
if ($amountToShift > 0.0) {
    if (!doShift($wallet, $pair, $amountToShift, $minerFee)) {
        echo "Failed to shift: $input -> $output ($amountToShift). Balance of wallet: $walletAmount\n";
        exit();
    }
}


