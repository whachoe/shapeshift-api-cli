<?php
namespace Payment;

class LTCPayment extends Payment
{
    private $minerFee;

    public function parseShapeshiftResponse($response)
    {
        if (is_string($response)) {
            try {
                $data = json_decode($response, true);

            } catch (\Exception $e) {
                logger("LTCPayment: Error parsing shapeshift message: ".$e->getMessage());
                return false;
            }
        } else {
            $data = $response;
        }

        $this->toAddress = $data['deposit'];

        return true;
    }

    public function parseChangellyResponse($response)
    {
        $this->toAddress = $response['result']['address'];
        return  true;
    }

    public function send()
    {
        if (!$this->toAddress || !$this->amount) {
            logger("LTCPayment: Missing parameters. We need 'toAddress' and 'amount");
            return false;
        }

        $str_replace_from = [':address', ':amount', ':password'];
        $str_replace_to = [$this->toAddress, $this->amount, $this->walletConfig['password']];
        $command = str_replace($str_replace_from, $str_replace_to, $this->transferCommand);

        return $this->executeSend($command);
    }

    public function getWalletAmount()
    {
        $balance = 0;
        $output = parent::getWalletAmount();
        try {
            $data = json_decode($output, true);
            if ($data['confirmed']) {
                $balance = $data['confirmed'];
            }
        } catch (\Exception $e) {
            logger("LTCPayment: Error getting wallet amount");
            exit();
        }

        return $balance;
    }

    public function getWalletAmountFriendly()
    {
        return $this->getWalletAmount();
    }

    /**
     * Used to convert Shifter/Exchange output values into the value our Wallet understands
     * @param $amount
     * @return mixed
     */
    public static function toBase($amount)
    {
        return $amount;
    }
}