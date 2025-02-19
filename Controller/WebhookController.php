<?php

namespace DreamCommerce\ShopAppstoreBundle\Controller;

use Doctrine\Persistence\ObjectManager;
use DreamCommerce\ShopAppstoreBundle\DependencyInjection\DreamCommerceShopAppstoreExtension;
use DreamCommerce\ShopAppstoreBundle\Model\ShopRepositoryInterface;
use DreamCommerce\ShopAppstoreBundle\Utils\Webhook\Validator\AppValidatorInterface;
use DreamCommerce\ShopAppstoreBundle\Utils\Webhook\Validator\GlobalValidatorInterface;
use DreamCommerce\ShopAppstoreLib\Resource\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends AbstractController
{
    public function globalAction($webhookId, Request $request)
    {

        $definedWebhooks = $this->getParameter('dream_commerce_shop_appstore.webhooks');

        if (!isset($definedWebhooks[$webhookId])) {
            throw new NotFoundException(sprintf('Webhook ID "%s" not found', $webhookId));
        }

        $webhookData = $definedWebhooks[$webhookId];

        /**
         * @var $validator GlobalValidatorInterface
         */
        $validator = $this->get($webhookData['validator']);
        $validator->setConfig($webhookData);

        $processor = $this->get('dream_commerce_shop_appstore.webhook.processor');
        $processor->process($validator, $request);

        return new Response();
    }

    public function applicationAction($appId, $webhookId, Request $request)
    {
        $apps = $this->get('dream_commerce_shop_appstore.apps');

        $app = $apps->get($appId);
        if(!$app){
            throw new NotFoundException(sprintf('Application %s not found', $appId));
        }

        $webhookData = $app->getWebhook($webhookId);
        if(!$webhookData){
            throw new NotFoundException(sprintf('Webhook %d not found', $webhookId));
        }

        /**
         * @var $om ObjectManager
         */
        $om = $this->get(DreamCommerceShopAppstoreExtension::ALIAS . '.object_manager');
        /**
         * @var $repo ShopRepositoryInterface
         */
        $repo = $om->getRepository('DreamCommerce\ShopAppstoreBundle\Model\ShopInterface');

        $shopName = $request->headers->get('X-Shop-License');
        $shop = $repo->findOneByNameAndApplication($shopName, $appId);
        if(!$shop){
            throw new NotFoundException(sprintf('Shop %s not found', $shopName));
        }

        /**
         * @var $validator AppValidatorInterface
         */
        $validator = $this->get($webhookData['validator']);
        $validator->setConfig($app, $webhookId);
        $validator->setShop($shop);

        $processor = $this->get('dream_commerce_shop_appstore.webhook.processor');
        $processor->setShop($shop);
        $processor->setApplication($app);
        $processor->process($validator, $request);

        return new Response();
    }
}
