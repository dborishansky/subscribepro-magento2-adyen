<?php

declare(strict_types=1);

namespace Rightpoint\SubscriptionsExternalVault\Service;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Service\Payment\PaymentProfileDataBuilderInterface;

class PaymentProfileDataBuilder implements PaymentProfileDataBuilderInterface
{
    /**
     * Some cc types in CardConnect should be translated into their Subscribe Pro analogues
     *
     * @var array
     */
    private $translationTable = [
        'mc' => 'master',
        'amex' => 'american_express',
        'diners' => 'diners_club'
    ];

    /**
     * @param int $platformCustomerId
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @return array
     */
    public function build(int $platformCustomerId, PaymentTokenInterface $paymentToken): array
    {
        $tokenDetails = $this->getTokenDetailsData($paymentToken);
        $expirationDate = $tokenDetails['expirationDate'] ?? '';

        return [
            PaymentProfileInterface::CUSTOMER_ID => $platformCustomerId,
            PaymentProfileInterface::PAYMENT_TOKEN => $paymentToken->getGatewayToken(),
            PaymentProfileInterface::CREDITCARD_TYPE =>
                isset($tokenDetails['type']) ? $this->translateCCType($tokenDetails['type']) : null,
            PaymentProfileInterface::CREDITCARD_LAST_DIGITS => ($tokenDetails['maskedCC'] ?? null),
            PaymentProfileInterface::CREDITCARD_MONTH => (explode('/', $expirationDate)[0] ?? null),
            PaymentProfileInterface::CREDITCARD_YEAR => (explode('/', $expirationDate)[1] ?? null),
        ];
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @return array
     */
    private function getTokenDetailsData(PaymentTokenInterface $paymentToken): array
    {
        return (array)json_decode($paymentToken->getTokenDetails() ?: '{}', true);
    }

    /**
     * @param string $cardConnectValue
     * @return string
     */
    private function translateCCType(string $cardConnectValue): string
    {
        return $this->translationTable[$cardConnectValue] ?? $cardConnectValue;
    }
}
