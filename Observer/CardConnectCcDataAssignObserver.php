<?php

declare(strict_types=1);

namespace Rightpoint\SubscriptionsExternalVault\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Brsw\CardConnect\Observer\DataAssignObserver as CardConnectAssignObserver;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class CardConnectCcDataAssignObserver extends AbstractDataAssignObserver
{
    protected const STORE_CC = "cardconnect_cc_vault";

    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @var \Brsw\CardConnect\Helper\Data
     */
    protected $stateData;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Helper\Quote         $quoteHelper
     * @param \Brsw\CardConnect\Helper\Data             $stateData
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper,
        \Brsw\CardConnect\Helper\Data $stateData
    ) {
        $this->generalConfig = $generalConfig;
        $this->quoteHelper = $quoteHelper;
        $this->stateData = $stateData;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $paymentInfo = $this->readPaymentModelArgument($observer);
        $quote = $paymentInfo->getQuote();

        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->generalConfig->isEnabled($websiteCode) || !$this->quoteHelper->hasSubscription($quote)) {
            return;
        }

        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData) || !empty($additionalData[PaymentTokenInterface::PUBLIC_HASH])) {
            return;
        }

        // This area may need work
        $stateData = $this->stateData->getStateData((int)$paymentInfo->getData('quote_id'));
        $stateData['storePaymentMethod'] = true;
        $this->stateData->setStateData($stateData, (int)$paymentInfo->getData('quote_id'));

        $paymentInfo->setAdditionalInformation(self::STORE_CC, true);
        $paymentInfo->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, true);

        $additionalData[VaultConfigProvider::IS_ACTIVE_CODE] = true;
        $data->setData(PaymentInterface::KEY_ADDITIONAL_DATA, $additionalData);
    }
}
