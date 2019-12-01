<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MMA\CustomApi\Model\PayPal\Transparent;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\Generic;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Paypal\Model\Payflow\Service\Request\SecureToken;
use Magento\Paypal\Model\Payflow\Transparent;
use Magento\Quote\Model\Quote;

use MMA\CustomApi\Api\PayPal\Transparent\SecurityTokenInterface;
use MMA\CustomApi\Api\PayPal\Transparent\SecurityTokenManagerInterface;

/**
 * Class RequestSecureToken
 *
 * @package Magento\Paypal\Controller\Transparent
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SecureTokenManager implements SecurityTokenManagerInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Generic
     */
    private $sessionTransparent;

    /**
     * @var SecureToken
     */
    private $secureTokenService;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var Transparent
     */
    private $transparent;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param Generic $sessionTransparent
     * @param SecureToken $secureTokenService
     * @param CartRepositoryInterface $quoteRepository
     * @param Transparent $transparent
     * @param SecurityTokenInterface $response
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Generic $sessionTransparent,
        SecureToken $secureTokenService,
        CartRepositoryInterface $quoteRepository,
        Transparent $transparent
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->sessionTransparent = $sessionTransparent;
        $this->secureTokenService = $secureTokenService;
        $this->quoteRepository = $quoteRepository;
        $this->transparent = $transparent;
    }

    /**
     * @inheritDoc
     */
    public function getSecurityToken($quoteId)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        if (!$quote or !$quote instanceof Quote) {
            return $this->getErrorResponse();
        }

        $this->sessionTransparent->setQuoteId($quote->getId());
        try {
            $token = $this->secureTokenService->requestToken($quote);
            if (!$token->getData('securetoken')) {
                throw new \LogicException();
            }

            return $token;
        } catch (\Exception $e) {
            return $this->getErrorResponse();
        }
    }

    /**
     * @return Json
     */
    private function getErrorResponse()
    {
        return $this->resultJsonFactory->create()->setData(
            [
                'success' => false,
                'error' => true,
                'error_messages' => __('Your payment has been declined. Please try again.')
            ]
        );
    }
}
