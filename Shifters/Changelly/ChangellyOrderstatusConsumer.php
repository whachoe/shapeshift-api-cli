<?php
namespace Shifters\Changelly;

include_once "vendor/autoload.php";
include_once "config.php";
include_once "lib.php";

set_time_limit(0);
error_reporting(E_ALL);

use Pheanstalk\Pheanstalk;

class ChangellyOrderstatusConsumer {
    private $client;    // Pheanstalk client
    private $db;        // Postgres db

    public function __construct()
    {
        $this->client = new Pheanstalk('127.0.0.1');
    }

    public function listen()
    {
        $this->client->watch('changelly_orderstatus');

        // Do this forever... so it's always listening.
        while ($job = $this->client->reserve()) {
            $message = json_decode($job->getData(), true);

            if ($this->process($message))
                $this->client->delete($job);
        }
    }

    public function process($msg)
    {
        echo date("c")."\tProcessing message: ".var_export($msg, true);

        // Check orderstatus
        $shifter = new Changelly(CHANGELLY_API_KEY, CHANGELLY_SECRET_KEY);
        $transactions = $shifter->getTransactions(null, $msg['result']['address']);

        echo date("c")."\tTransactions: ". var_export($transactions, true);

        if (!$transactions || count($transactions) <1)
            return false;

        foreach ($transactions as $transaction) {
            transactionToDb($transaction);
        }

        return true;
    }
}

$consumer = new ChangellyOrderstatusConsumer();
$consumer->listen();