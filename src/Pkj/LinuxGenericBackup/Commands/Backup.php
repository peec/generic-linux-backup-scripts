<?php
namespace Pkj\LinuxGenericBackup\Commands;


use Pkj\LinuxGenericBackup\Extension\BackupExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 08.08.14
 * Time: 23:32
 */

class Backup extends Command{

    protected $container;


    protected function configure() {
        $this->setName("backup")
            ->addArgument('routine', InputArgument::OPTIONAL, 'Chose what routine to run, default is "all".', 'all')
            ->addArgument('namespace', InputArgument::OPTIONAL, 'Backup package namespace, e.g. hourly, daily etc. Default is: default.', 'default')
            ->setDescription("Runs a backup routine.")
            ->setHelp(<<<EOT
Usage:

<info>./linuxbackups backup --help</info>

EOT
            );

    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $container = new ContainerBuilder();
        $container->registerExtension(new BackupExtension());

        $notifiers = array (
        );

        foreach($notifiers as $service => $class) {
            $container->addCompilerPass(new NotificationManagerAddNotifier($service));
        }
        $loader = new YamlFileLoader($container, new FileLocator(APP_ROOT_DIR . '/config'));
        $loader->load('config.yml');

        $container->compile();


    }


} 