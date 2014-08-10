<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 10.08.14
 * Time: 02:23
 */

namespace Pkj\LinuxGenericBackup\Notifications\Pushover;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class PushoverExtension implements ExtensionInterface{


    public function load(array $config, ContainerBuilder $container) {

        $container->setParameter(
            'notification.pushover.config',
            $config[0]
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load('services.yml');

    }

    public function getNamespace() {
        return 'pushover';
    }

    public function getXsdValidationBasePath() {}

    public function getAlias() {
        return 'pushover';
    }
}