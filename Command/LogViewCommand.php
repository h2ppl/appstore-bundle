<?php

namespace DreamCommerce\ShopAppstoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LogViewCommand extends Command
{
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var InputInterface
     */
    protected $input;
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
            ->setName('dream_commerce_shop_appstore:log_view')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify type to show', 'error')
            ->setDescription('Dump desired log file respecting new lines');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $helper = $this->getHelper('question');
        $logs = $this->getLogsList();
        $question = new ChoiceQuestion(
            'Choose file to view',
            $logs,
            0
        );

        $item = $helper->ask($input, $output, $question);

        $this->showLog($item);

    }

    protected function showLog($file){

        $path = sprintf('%s/%s', $this->container->get('kernel')->getLogDir(), $file);

        $log = new \SplFileObject($path);

        $type = $this->input->getOption('type');

        foreach($log as $i){
            if(!preg_match("/\\[[^\\[]+\\] [^\\.]+\\.".preg_quote($type, '/')."/i", $i)){
                continue;
            }

            $i = $this->formatLine($i);
            $this->output->writeln($i);
        }

    }

    protected function getLogsList(){
        $result = [];
        $dir = $this->container->get('kernel')->getLogDir();
        $iterator = new \DirectoryIterator($dir);

        foreach($iterator as $i){
            if($i->isDir() || $i->getBasename()[0]=='.'){
                continue;
            }

            $result[] = $i->getBasename();
        }

        return $result;
    }

    protected function formatLine($i)
    {
        $i = str_replace("\\n", PHP_EOL, $i);
        return $i;
    }
}
