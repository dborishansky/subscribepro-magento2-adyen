<?php

declare(strict_types=1);

namespace Rightpoint\SubscriptionsExternalVault\Model\Ui\Adminhtml;

use Brsw\CardConnect\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

class TokenUiComponentProviderCardConnectHpp implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var Data
     */
    private $cardConnectHelper;

    /**
     * TokenUiComponentProvider constructor.
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param Data $cardConnectHelper
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        Data $cardConnectHelper
    ) {
        $this->componentFactory = $componentFactory;
        $this->cardConnectHelper = $cardConnectHelper;
    }

    /**
     * @inheritdoc
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        $details['icon'] = $this->cardConnectHelper->getVariantIcon($details['type']);
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => 'cardConnect_hpp_vault',
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $details,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    'template' => 'CardConnect_Payment::form/vault.phtml'
                ],
                'name' => Template::class
            ]
        );
        return $component;
    }
}
