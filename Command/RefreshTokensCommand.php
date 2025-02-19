<?php

namespace DreamCommerce\ShopAppstoreBundle\Command;

use DreamCommerce\ShopAppstoreBundle\DependencyInjection\DreamCommerceShopAppstoreExtension;
use DreamCommerce\ShopAppstoreBundle\Handler\Application;
use DreamCommerce\ShopAppstoreBundle\Model\ShopRepositoryInterface;
use DreamCommerce\ShopAppstoreBundle\Utils\TokenRefresher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RefreshTokensCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(DreamCommerceShopAppstoreExtension::ALIAS . ':refresh_tokens')
            ->addArgument('application', InputArgument::OPTIONAL, 'Application to perform extension on')
            ->setDescription('Refreshes all access tokens to extend their validity');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // get application name if specified
        $appName = $input->getArgument('application');
        $container = $this->container;

        // get applications list
        try {
            $app = $this->getApplications($appName);
        } catch (\Exception $ex) {
            $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));
            return -1;
        }

        /**
         * @var $om \Doctrine\Persistence\ObjectManager
         */
        $om = $container->get(DreamCommerceShopAppstoreExtension::ALIAS . '.object_manager');
        /**
         * @var $refresher TokenRefresher
         */
        $refresher = $container->get(DreamCommerceShopAppstoreExtension::ALIAS . '.token_refresher');
        /**
         * @var $repo ShopRepositoryInterface
         */
        $repo = $om->getRepository('DreamCommerce\ShopAppstoreBundle\Model\ShopInterface');

        foreach ($app as $id => $obj) {

            $output->writeln(sprintf('Refreshing tokens for %s...', $id));

            $shops = $repo->findByApplication($id);

            foreach ($shops as $s) {
                $client = $obj->getClient($s);
                $refresher->setClient($client);

                try {
                    $output->writeln(sprintf('Refreshing shop: %s # %s', $s->getShopUrl(), $s->getName()));
                    $refresher->refresh($s);
                    $output->writeln('Done');
                } catch (\Exception $ex) {
                    $output->writeln('<error>An error occurred during the token refresh</error>');
                }
            }
        }

        $output->writeln('All shops done');

    }

    /**
     * @param null $appName
     * @return Application[]
     * @throws \Exception
     */
    protected function getApplications($appName = null)
    {
        $container = $this->container;

        $app = [];

        if (!empty($appName)) {
            $id = str_replace('.', '', $appName);
            $id = sprintf(DreamCommerceShopAppstoreExtension::ALIAS . '.apps.%s', $id);

            /**
             * @var Application $app
             */
            if ($container->has($id)) {
                $app[$appName] = $container->get($id);
            } else {
                throw new \Exception('Specified application not found');
            }
        } else {
            $appIds = $container->getParameter(DreamCommerceShopAppstoreExtension::ALIAS . '.apps');
            $appIds = array_keys($appIds);

            $registry = $container->get(DreamCommerceShopAppstoreExtension::ALIAS . '.apps');

            foreach ($appIds as $id) {
                $app[$id] = $registry->get($id);
            }
        }

        return $app;
    }
}
