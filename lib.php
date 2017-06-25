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
    $command = str_replace([':user', ':password', ':fromAddress'], [$wallet['user'], $wallet['password'], $wallet['address']], $wallet['walletBalanceCommand']);

    switch ($wallet['currency']) {
        case 'xmr':
            $output = `$command`;
            try {
                $data = json_decode($output, true);
                if (isset($data['result']) && isset($data['result']['balance'])) {
                    $balance = (float) $data['result']['balance']/MONERO_BASE_CONVERSION;
                }
            } catch (\Exception $e) {
                echo "XMR: Error getting wallet amount";
                exit();
            }
            break;
        case 'eth':
            $output = `$command`;
            try {
                $data = json_decode($output, true);
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
                $data = json_decode($output, true);
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
        case 'ltc':
            $output = `$command`;
            try {
                $data = json_decode($output, true);
                if ($data['confirmed']) {
                    $balance = $data['confirmed'];
                }
            } catch (\Exception $e) {
                echo "LTC: Error getting wallet amount";
                exit();
            }
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
function doShift($from, $to, $pair, $amountToShift, $minerFee=0)
{
    if ($amountToShift > 0.0) {
        // First talk to shapeshift
        $command = "curl -X POST -H \"Content-Type: application/json\" -d '{\"withdrawal\":\"{$to['address']}\", \"pair\":\"$pair\", \"returnAddress\":\"{$from['address']}\"}' https://shapeshift.io/shift";
        $output = `$command`;
        try {
            echo "Shapeshift answer: $output\n";
            $data = json_decode($output);
        } catch (\Exception $e) {
            echo "Something went wrong while calling Shapeshift: {$e->getMessage()}\n";
            exit();
        }

        if (isset($data['error'])) {
            echo "Shapeshift error: {$data['error']}. Exiting\n";
            exit();
        }

        // Double check deposit type: Should match our from-wallet
        if (strtolower($data['depositType']) != $from['currency']) {
            echo "Wrong deposittype: Shapeshift: {$data['depositType']}. We: {$from['currency']}";
            exit();
        }

        $shapeshiftAddress = $data['deposit'];

        // Now send the money
        sendToAddress($from, $shapeshiftAddress, $amountToShift, $minerFee);
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
    if (!$coins || isset($coins['error'])) {
        echo "Error getting availability.\n";
        if (isset($coins['error'])) {
            echo "Shapeshift error: {$coins['error']}\n";
        }
        echo "Exiting.\n";
        exit();
    }

    return ($coins[strtoupper($input)]['status'] == 'available' && $coins[strtoupper($output)]['status'] == 'available');
}

function getMarketInfo($pair)
{
    $string = file_get_contents("https://shapeshift.io/marketinfo/$pair");
    try {
        $data = json_decode($string, true);
    } catch (\Exception $e) {
        echo "Error getting MarketInfo: {$e->getMessage()}";
        exit();
    }

    if (isset($data['error'])) {
        echo "Shapeshift error: {$data['error']}. Exiting\n";
        exit();
    }

    return $data;
}

function sendToAddress($fromWallet, $toAddress, $amount, $minerFee=0)
{
    // Do something special for monero
    if ($fromWallet['currency'] == 'xmr') {
        $amount = intval($amount * MONERO_BASE_CONVERSION);
        $minerFee = intval($minerFee * MONERO_BASE_CONVERSION);
    }

    $command = str_replace([':address', ':amount', ':minerFee', ':password', ':fromAddress'], [$toAddress, $amount, $minerFee, $fromWallet['password'], $fromWallet['address']], $fromWallet['walletTransferCommand']);
    echo $command . "\n";

    // Uncomment when testing is done:
    // echo `$command`; echo "\n";
}

function getExchangeRate($from, $to)
{
    $from = strtoupper($from);
    $to = strtoupper($to);
    $data = file_get_contents("https://min-api.cryptocompare.com/data/price?fsym=$from&tsyms=$to");
    $result = json_decode($data, true);

    return $result[$to];
}

function getMultipleExchangeRates($fromArray, $toArray)
{
    $from = implode(",", array_map("strtoupper", $fromArray));
    $to = implode(",", array_map("strtoupper", $toArray));
    $data = file_get_contents("https://min-api.cryptocompare.com/data/pricemulti?fsyms=$from&tsyms=$to");
    $result = json_decode($data, true);

    return $result;
}

/**
 * Parse commandline options
 *
 * @param $argv
 * @return array
 */
function parseArgs($argv)
{
    array_shift($argv);
    $o = array();
    foreach ($argv as $a) {
        if (substr($a, 0, 2) == '--') {
            $eq = strpos($a, '=');
            if ($eq !== false) {
                $o[substr($a, 2, $eq - 2)] = substr($a, $eq + 1);
            } else {
                $k = substr($a, 2);
                if (!isset($o[$k])) {
                    $o[$k] = true;
                }
            }
        } else if (substr($a, 0, 1) == '-') {
            if (substr($a, 2, 1) == '=') {
                $o[substr($a, 1, 1)] = substr($a, 3);
            } else {
                foreach (str_split(substr($a, 1)) as $k) {
                    if (!isset($o[$k])) {
                        $o[$k] = true;
                    }
                }
            }
        } else {
            $o[] = $a;
        }
    }
    return $o;
}