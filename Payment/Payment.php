<?php
namespace Payment;


abstract class Payment
{
    public $currency;
    public $fromAddress;
    public $toAddress;
    public $amount;
    protected $transferCommand;
    protected $walletConfig;

    abstract public function parseShapeshiftResponse($response);

    abstract public function send();

    public function __construct($walletConfig)
    {
        $this->walletConfig = $walletConfig;
        $this->currency = $walletConfig['currency'];
        $this->fromAddress = $walletConfig['address'];
        $this->transferCommand = $walletConfig['walletTransferCommand'];
    }

    protected function executeSend($command)
    {
        echo $command . "\n";

        // Uncomment when testing is done:
        // $response = `$command`;
        // echo $response."\n";

        return true;
    }

    public static function factory($walletConfig)
    {
        switch(strtolower($walletConfig['currency'])) {
            case 'btc': return new BTCPayment($walletConfig);
            case 'eth': return new ETHPayment($walletConfig);
            case 'xmr': return new XMRPayment($walletConfig);
            case 'ltc': return new LTCPayment($walletConfig);
            case 'zec': return new ZECPayment($walletConfig);
        }
    }
}