<?php

namespace Shifters\Changelly;

use Payment\Payment;
use Datto\JsonRpc\Http\Client as JsonRpcClient;

class Changelly
{
    private $baseUrl = 'https://api.changelly.com';
    private $jsonrpcClient;

    public function __construct($apiKey, $secret)
    {
        $options = array(
            'http' => array(
                'timeout' => 5
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );
        $this->jsonrpcClient = new JsonRpcClient($this->baseUrl, null, $options, $secret);
        $this->jsonrpcClient->setHeader('api-key', $apiKey);
    }

    /**
     * Checks with Changelly if both input and output are available
     *
     * @param $input
     * @param $output
     * @return bool
     */
    public function checkAvailability($input, $output)
    {
        $this->jsonrpcClient->query($this->getId(), 'getCurrencies');

        $response = $this->jsonrpcClient->send();
        var_dump($response);
        die;

        $data = $this->catchError($response);
        if ($data)
            return ($data[strtoupper($input)]['status'] == 'available' && $data[strtoupper($output)]['status'] == 'available');
    }

    private function getId()
    {
        $string = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
        $newstring = "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx";
        for ($i = 0; $i < strlen($string); $i++) {
            $c = $string[$i];
            if ($c == 'x' || $c == 'y') {
                $r = (mt_rand() / mt_getrandmax()) * 16 | 0;
                $v = dechex(($c == 'x' ? $r : ($r & 0x3 | 0x8)));
            } else {
                $v = $c;
            }
            $newstring[$i] = $v;
        }

        return $newstring;
    }
}
