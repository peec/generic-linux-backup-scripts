<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 10.08.14
 * Time: 05:23
 */

namespace Pkj\LinuxGenericBackup\Commands;


use Pkj\LinuxGenericBackup\Notifications\NotificationManagerAddNotifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

abstract class BaseCommand extends Command{

    protected $container;

    public function __construct ($name) {
        parent::__construct($name);

        $container = new ContainerBuilder();



        $this->loadNotifiers($container);

        $loader = new YamlFileLoader($container, new FileLocator(APP_ROOT_DIR . '/config'));
        $loader->load('config.yml');
        $loader = new YamlFileLoader($container, new FileLocator(APP_ROOT_DIR . '/src/Pkj/LinuxGenericBackup/Resources'));
        $loader->load('services.yml');
        $container->compile();

        $this->container = $container;

    }

    private function loadNotifiers ($container) {

        $notifiers = array (
            'notification.pushover' => 'Pkj\LinuxGenericBackup\Notifications\Pushover\PushoverExtension'
        );

        foreach($notifiers as $service => $class) {
            $container->registerExtension(new $class());
            $container->addCompilerPass(new NotificationManagerAddNotifier($service));
        }
    }

} 