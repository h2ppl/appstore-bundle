<?php

namespace DreamCommerce\ShopAppstoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DreamCommerceShopAppstoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter($this->getAlias().'.applications', $config['applications']);

        // todo: checking if not already defined

        foreach($config['applications'] as $app=>$data){

            $definition = new Definition('DreamCommerce\\ShopAppstoreBundle\\Handler\\Application');
            $definition->addArgument(new Reference('logger'));
            $definition->addArgument($data['app_id']);
            $definition->addArgument($data['app_secret']);
            $definition->addArgument($data['appstore_secret']);

            $container->setDefinition($this->getAlias().'.'.$app, $definition);
        }

    }

    public function getAlias(){
        return 'dream_commerce_shop_appstore';
    }
}
