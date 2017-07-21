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
        $statusObj = $shifter->getTransactions(null, $msg['result']['address']);

        echo date("c")."\tTransactions: ". var_export($statusObj, true);

        if (!$statusObj || isset($statusObj['error']))
            return false;

        $this->db = new \PDO("pgsql:host=localhost;dbname=".DB_NAME.";user=".DB_USER.";password=".DB_PW);

        foreach ($statusObj as $transaction) {
            // Check if in database
            $stmt = $this->db->prepare("SELECT * FROM transaction WHERE txid = ?");
            // If exists: Update record in database
            if ($stmt->execute([$transaction['id']])) {
                while ($row = $stmt->fetch()) {
                    $ins = $this->db->prepare("UPDATE transaction SET data=:json WHERE id=:rowid");
                    $ins->bindParam(":json", $transaction);
                    $ins->bindParam(":rowid", $row['id']);
                    $ins->execute();
                    $ins = null;
                }
            } else { // If not exists: Make record in database
                $ins = $this->db->prepare("INSERT INTO transaction (txid, data) VALUES (?, ?)");
                $ins->bindParam(1, $transaction['id']);
                $ins->bindParam(2, $transaction);
                $ins->execute();
                $ins = null;
            }

            $stmt = null;
        }

        $this->db = null;
        return true;
    }
}

$consumer = new ChangellyOrderstatusConsumer();
$consumer->listen();