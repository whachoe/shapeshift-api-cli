<?php
require_once('Payment/Payment.php');

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

function logger($message)
{
    $logline = date("c")."\t".$message."\n";
    file_put_contents(LOGFILE, $logline, FILE_APPEND);

    // Also print on screen
    echo $logline."\n";
}

function write_transaction_log($inputWallet, $outputWallet, $amountToShift)
{
    $amountToShiftOutput = $amountToShift*getExchangeRate($inputWallet['currency'], $outputWallet['currency']);
    $data = [date("c"), $inputWallet['currency'], $amountToShift, $outputWallet['currency'], $amountToShiftOutput];
    $line = implode(';', $data)."\n";
    file_put_contents(TRANSACTION_CSV_FILE, $line, FILE_APPEND);
}