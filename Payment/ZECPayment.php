<?php
namespace Payment;

class ZECPayment extends Payment
{
    public function parseShapeshiftResponse($response)
    {
        if (is_string($response)) {
            try {
                $data = json_decode($response, true);
            } catch (\Exception $e) {
                logger("ZECPayment: Error parsing shapeshift message: ".$e->getMessage());
                return false;
            }
        }  else {
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
            logger("ZECPayment: Missing parameters. We need 'toAddress' and 'amount");
            return false;

        }

        $str_replace_from = [':address', ':amount', ':fromAddress'];
        $str_replace_to = [$this->toAddress, number_format($this->amount, 2), $this->fromAddress];
        $command = str_replace($str_replace_from, $str_replace_to, $this->transferCommand);

        return $this->executeSend($command);
    }

    public function getWalletAmount()
    {
        return trim(parent::getWalletAmount());
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