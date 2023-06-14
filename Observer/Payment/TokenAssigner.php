<?php

declare(strict_types=1);

namespace Rightpoint\SubscriptionsExternalVault\Observer\Payment;

use Brsw\CardConnect\Model\Ui\CardConnectCcConfigProvider;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class TokenAssigner extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $paymentMethodToken = $additionalData['payment_method_token'] ?? null;
        if (empty($paymentMethodToken)) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote\Payment $paymentModel */
        $paymentModel = $this->readPaymentModelArgument($observer);
        if (!$paymentModel instanceof QuotePayment) {
            return;
        }

        $quote = $paymentModel->getQuote();
        $customerId = $quote->getCustomer()->getId();
        if ($customerId === null) {
            return;
        }

        $paymentToken = $this->getPaymentToken($paymentMethodToken, (int)$customerId);
        if ($paymentToken === null) {
            return;
        }

        $paymentModel->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $customerId);
        $paymentModel->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $paymentToken->getPublicHash());

        if (!empty($additionalData[TransactionInterface::UNIQUE_ID])) {
            $paymentModel->setAdditionalInformation(
                TransactionInterface::UNIQUE_ID,
                $additionalData[TransactionInterface::UNIQUE_ID]
            );
        }

        if (!empty($additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN])) {
            $paymentModel->setAdditionalInformation(
                TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN,
                $additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN]
            );
        }
    }

    /**
     * @param string $paymentMethodToken
     * @param int $customerId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    protected function getPaymentToken(string $paymentMethodToken, int $customerId): ?PaymentTokenInterface
    {
        return $this->paymentTokenManagement->getByGatewayToken(
            $paymentMethodToken,
            CardConnectCcConfigProvider::CODE,
            $customerId
        );
    }
}
