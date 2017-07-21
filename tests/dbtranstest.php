<?php

include_once '../config.php';
include_once "../vendor/autoload.php";
include_once '../lib.php';

$transactions = array (
    0 =>
        array (
            'id' => '587ab00b3418',
            'createdAt' => 1500671666,
            'payinConfirmations' => '5',
            'status' => 'finished',
            'currencyFrom' => 'zec',
            'currencyTo' => 'ltc',
            'payinAddress' => 't1N8YM2bhuPoDE7NPN3sBQnx85VpjnLNBk2',
            'payinExtraId' => NULL,
            'payinHash' => 'a10fb3f25fd7d53db335b45c83a99cb571ae9abb05a416289ddaada9d582f95b',
            'payoutAddress' => 'LLRt74uu1NWhjNfutYvdadwac4CHt8yyXd',
            'payoutExtraId' => NULL,
            'payoutHash' => '172b619dba89f3fd0dea09aeeb3e465e54d8890309fe57c9f61a9230051b3033',
            'amountFrom' => '0.08',
            'amountTo' => '0.36408592',
            'networkFee' => '0.003',
        ),
);

foreach ($transactions as $transaction) {
    transactionToDb($transaction);
}
