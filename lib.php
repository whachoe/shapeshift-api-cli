<?php
require_once('Payment/Payment.php');

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
                    $balance = (float) $data['result']['balance']/\Payment\XMRPayment::MONERO_BASE_CONVERSION;
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
                    $balance = hexdec($data['result']/1000000000000000000);
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