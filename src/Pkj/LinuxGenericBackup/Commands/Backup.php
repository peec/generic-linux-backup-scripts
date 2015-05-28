<?php
namespace Pkj\LinuxGenericBackup\Commands;


use Pkj\LinuxGenericBackup\BackupHandler;
use Pkj\LinuxGenericBackup\ServiceContainer;
use Pkj\LinuxGenericBackup\Notifications\NotificationManagerAddNotifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 08.08.14
 * Time: 23:32
 */

class Backup extends BaseCommand{

    protected $container;

    public function __construct ($name) {
        parent::__construct($name);

        $container = new ContainerBuilder();

        $notifiers = array (
            'notification.pushover' => 'Pkj\LinuxGenericBackup\Notifications\Pushover\PushoverExtension',
            'notification.mailer' => 'Pkj\LinuxGenericBackup\Notifications\Mailer\MailerExtension'
        );

        foreach($notifiers as $service => $class) {
            $container->registerExtension(new $class());
            $container->addCompilerPass(new NotificationManagerAddNotifier($service));
        }

        $loader = new YamlFileLoader($container, new FileLocator(APP_ROOT_DIR . '/config'));
        $loader->load('config.yml');
        $loader = new YamlFileLoader($container, new FileLocator(APP_ROOT_DIR . '/src/Pkj/LinuxGenericBackup/Resources'));
        $loader->load('services.yml');
        $container->compile();

    }


    protected function configure() {
        $this->setName("backup")
            ->addArgument('routine', InputArgument::OPTIONAL, 'Chose what routine to run, default is "all".', 'all')
            ->addArgument('namespace', InputArgument::OPTIONAL, 'Backup package namespace, e.g. hourly, daily etc. Default is: default.', 'default')
            ->setDescription("Runs a backup routine.")
            ->setHelp(<<<EOT
Usage:

<info>./run backup all</info>

EOT
            );

    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }


} 