<?php

declare(strict_types=1);

namespace Rightpoint\SubscriptionsExternalVault\Observer\Payment;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Brsw\CardConnect\Model\Ui\ConfigProvider;

class CardConnectHppTokenAssigner extends TokenAssigner
{
    /**
     * @param string $paymentMethodToken
     * @param int $customerId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    protected function getPaymentToken(
        string $paymentMethodToken,
        int $customerId
    ): ?PaymentTokenInterface {
        return $this->paymentTokenManagement->getByGatewayToken(
            $paymentMethodToken,
            ConfigProvider::CODE,
            $customerId
        );
    }
}
