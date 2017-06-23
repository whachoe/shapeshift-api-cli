<?php
/**
 * Check the balance of a wallet
 *
 * @param $wallet
 * @return float
 */
function getWalletAmount($wallet)
{
    $balance = 0.0;
    $command = str_replace([':password', ':fromAddress'], [$wallet['password'], $wallet['address']], $wallet['walletBalanceCommand']);

    switch ($wallet['currency']) {
        case 'xmr':
            $output = `$command`;
            $matches = [];
            preg_match('/Balance: (.*),/', $output, $matches);
            $balance = $matches[1];
            break;
        case 'eth':
            $output = `$command`;
            try {
                $data = json_decode($output);
                if ($data['result']) {
                    $balance = $data['result'];
                }
            } catch (\Exception $e) {
                echo "ETH: Error getting wallet amount";
                exit();
            }
            break;
        case 'btc':
            $output = `$command`;
            try {
                $data = json_decode($output);
                if ($data['confirmed']) {
                    $balance = $data['confirmed'];
                }
            } catch (\Exception $e) {
                echo "BTC: Error getting wallet amount";
                exit();
            }
            break;
        case 'zec':
            $balance = trim(`$command`);
            break;
    }

    return (float)$balance;
}

/**
 * Call shapeshift and send funds
 *
 * @param $from
 * @param $to
 * @param $pair
 * @param $amountToShift
 * @return bool
 */
function doShift($from, $to, $pair, $amountToShift, $minerFee)
{
    if ($amountToShift > 0.0) {
        // First talk to shapeshift
        $command = "curl -X POST -H \"Content-Type: application/json\" -d '{\"withdrawal\":\"{$to['address']}\", \"pair\":\"$pair\", \"returnAddress\":\"{$from['address']}\"}' https://shapeshift.io/shift";
        $output = `$command`;
        try {

            $data = json_decode($output);
        } catch (\Exception $e) {
            echo "Something went wrong while calling Shapeshift: {$e->getMessage()}\n";
            exit();
        }

        // Double check deposit type: Should match our from-wallet
        if (strtolower($data['depositType']) != $from['currency']) {
            echo "Wrong deposittype: Shapeshift: {$data['depositType']}. We: {$from['currency']}";
            exit();
        }

        $shapeshiftAddress = $data['deposit'];

        // Now send the money
        $command = str_replace([':address', ':amount', ':minerFee', ':password', ':fromAddress'], [$shapeshiftAddress, $amountToShift, $minerFee, $from['password'], $from['address']], $from['walletTransferCommand']);
        echo $command."\n";
    } else {
        echo "Amount to shift is too low: $amountToShift";
        return false;
    }

    return false;
}

/**
 * Checks with Shapeshift if both input and output are available
 *
 * @param $input
 * @param $output
 * @return bool
 */
function checkAvailability($input, $output)
{
    $data = file_get_contents("https://shapeshift.io/getcoins");
    $coins = json_decode($data, true);
    if (!$coins) {
        echo "Error getting availability. Exiting";
        exit();
    }

    return ($coins[strtoupper($input)]['status'] == 'available' && $coins[strtoupper($output)]['status'] == 'available');
}

function getMarketInfo($pair) {
    $string = file_get_contents("https://shapeshift.io/marketinfo/$pair");
    try {
        $data = json_decode($string, true);
    } catch (\Exception $e) {
        echo "Error getting MarketInfo: {$e->getMessage()}";
        exit();
    }

    return $data;
}