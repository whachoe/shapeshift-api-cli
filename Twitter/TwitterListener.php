<?php
include_once "vendor/autoload.php";
include_once "config.php";
include_once "lib.php";

use Pheanstalk\Pheanstalk;

/**
 * Listen to my twitter-stream for certain posts
 */
class TwitterListener extends OauthPhirehose
{
    var $client;

    public function __construct($username, $password, $method = Phirehose::METHOD_SAMPLE, $format = self::FORMAT_JSON, $lang = FALSE)
    {
        parent::__construct($username, $password, $method, $format, $lang);
        $this->client = new Pheanstalk('127.0.0.1');
    }

    /**
     * Enqueue each status
     *
     * @param string $status
     */
    public function enqueueStatus($status)
    {
        $data = json_decode($status, true);
        if (is_array($data) && isset($data['user']['screen_name'])) {
            if (preg_match('/(vickiethbot|vickibtcbot|vickibotbtcusd)/i', $data['user']['screen_name'])) {
                // Put it on the queue
                $this->client->putInTube('vickiqueue', $status);

                // Make a log
                echo date("c")."\t".$data['user']['screen_name'] . ': ' . urldecode($data['text']) . "\n";
            }
        }
    }
}

// Start streaming
$sc = new TwitterListener(TWITTER_OAUTH_TOKEN, TWITTER_OAUTH_SECRET, Phirehose::METHOD_FILTER);
$sc->setTrack(array('long', 'short'));
$sc->consume();