<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 11.08.14
 * Time: 01:08
 */

namespace Pkj\LinuxGenericBackup\Notifications\Mailer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class MailerExtension implements ExtensionInterface{


    public function load(array $config, ContainerBuilder $container) {

        $container->setParameter(
            'notification.mailer.config',
            $config[0]
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load('services.yml');

    }

    public function getNamespace() {
        return 'mailer';
    }

    public function getXsdValidationBasePath() {}

    public function getAlias() {
        return 'mailer';
    }
}