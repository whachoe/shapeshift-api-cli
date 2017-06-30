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
    abstract public function getWalletAmountFriendly();
    abstract public static function toBase($amount);

    public function __construct($walletConfig)
    {
        $this->walletConfig = $walletConfig;
        $this->currency = $walletConfig['currency'];
        $this->fromAddress = $walletConfig['address'];
        $this->transferCommand = $walletConfig['walletTransferCommand'];
    }

    protected function executeSend($command)
    {
        logger("Payment Command: $command");

        // Uncomment when testing is done:
        // $response = `$command`;
        // logger("Payment response: $response");

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

    public function getWalletAmount()
    {
        $command = str_replace([':user', ':password', ':fromAddress'], [$this->walletConfig['user'], $this->walletConfig['password'], $this->walletConfig['address']], $this->walletConfig['walletBalanceCommand']);
        return `$command`;
    }
}