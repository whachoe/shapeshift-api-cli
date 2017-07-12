<?php
include_once "vendor/autoload.php";
include_once "config.php";
include_once "lib.php";

use Pheanstalk\Pheanstalk;

class TwitterLogConsumer {
    var $pairs = ['BTCUSD', 'ETHBTC', 'XMRBTC', 'ZECUSD', 'LTCBTC', 'ETHUSD'];

    public function __construct()
    {
        $this->client = new Pheanstalk('127.0.0.1');
    }

    public function listen()
    {
        $this->client->watch('vickiqueue');

        while ($job = $this->client->reserve()) { // Do this forever... so it's always listening.
            $message = json_decode($job->getData(), true); // Decode the message

            $this->process($message);

            $this->client->delete($job); // Delete anyway. You could burry it, meaning it gets re-tried later.
        }
    }

    public function process($msg)
    {
        if ($msg['text']) {
            $text = urldecode($msg['text']);
            $input = ''; $output = '';

            if (strpos($text, "long") > 0) {
                $matches = [];
                if (preg_match('/([A-Z]{6})/', $text, $matches)) {
                    if (in_array($matches[1], $this->pairs)) {
                        $output = substr($matches[1], 0, 3);
                        $input = substr($matches[1], 3, 3);
                    }
                }
            } elseif (strpos($text, 'short') > 0) {
                $matches = [];
                if (preg_match('/([A-Z]{6})/', $text, $matches)) {
                    if (in_array($matches[1], $this->pairs)) {
                        $input = substr($matches[1], 0, 3);
                        $output = substr($matches[1], 3, 3);
                    }
                }
            }

            if ($input && $output) {
                $command = "php do_a_shift.php --input={$input} --output=$output";
                echo "Twitter consumer running: ".$command."\n";
                `$command`;
            }
        }
    }
}

$consumer = new TwitterLogConsumer();
$consumer->listen();