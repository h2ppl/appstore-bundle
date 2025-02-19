<?php
namespace DreamCommerce\ShopAppstoreBundle\Controller;


use DreamCommerce\ShopAppstoreLib\Client;
use DreamCommerce\ShopAppstoreLib\ClientInterface;
use DreamCommerce\ShopAppstoreBundle\EventListener\AppFormListener;
use DreamCommerce\ShopAppstoreBundle\Model\ShopInterface;
use DreamCommerce\ShopAppstoreBundle\Utils\Url;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ApplicationController
 *
 * provides shop-protected, generic controller class
 * @package DreamCommerce\ShopAppstoreBundle\Controller
 */
abstract class ApplicationController extends AbstractController implements ApplicationControllerInterface{

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var ShopInterface
     */
    protected $shop;

    /**
     * method used by request filter - injects current client and shop instance
     * @param Client $client
     * @param ShopInterface $shop
     */
    public function injectClient(ClientInterface $client, ShopInterface $shop){
        $this->client = $client;
        $this->shop = $shop;
    }

    /**
     * generates URL injecting iframe params to the query
     * @param string $route #Route
     * @param array $parameters
     * @param bool $referenceType
     * @return string
     */
    public function generateAppUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        /**
         * @var $rv Url
         */
        $rv = $this->get('dream_commerce_shop_appstore.url');
        return $rv->generateUrl($route, $parameters, $referenceType);
    }

    /**
     * creates form builder with iframe params
     * @see generateAppUrl
     * @param null $data
     * @param array $options
     * @return \Symfony\Component\Form\FormBuilder
     */
    public function createFormBuilder($data = null, array $options = array()): FormBuilderInterface
    {
        $builder = parent::createFormBuilder($data, $options);

        /**
         * @var $urlBuilder Url
         */
        $urlBuilder = $this->get('dream_commerce_shop_appstore.url');
        $listener = new AppFormListener($urlBuilder, $builder);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($listener, 'appendValidationTokens'));

        return $builder;
    }


}
