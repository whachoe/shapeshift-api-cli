<?php
include_once "vendor/autoload.php";
include_once "config.php";
include_once "lib.php";

/**
 * Listen to my twitter-stream for certain posts
 */
class TwitterListener extends OauthPhirehose
{
    /**
     * Enqueue each status
     *
     * @param string $status
     */
    public function enqueueStatus($status)
    {
        /*
         * In this simple example, we will just display to STDOUT rather than enqueue.
         * NOTE: You should NOT be processing tweets at this point in a real application, instead they should be being
         *       enqueued and processed asyncronously from the collection process.
         */
        $data = json_decode($status, true);
        if (is_array($data) && isset($data['user']['screen_name'])) {
            if (preg_match('/vicki.*bot/i', $data['user']['screen_name'])) {
                print $data['user']['screen_name'] . ': ' . urldecode($data['text']) . "\n";
            }
        }
    }
}

// Start streaming
$sc = new TwitterListener(TWITTER_OAUTH_TOKEN, TWITTER_OAUTH_SECRET, Phirehose::METHOD_FILTER);
$sc->setTrack(array('long', 'short'));
$sc->consume();