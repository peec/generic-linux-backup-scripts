<?php
namespace Pkj\LinuxGenericBackup\Extension;

use Pkj\LinuxGenericBackup\Configuration\BackupConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Definition\Builder\ExprBuilder;

class BackupExtension implements ExtensionInterface{


    public function load(array $configs, ContainerBuilder $container) {

        $loader = new YamlFileLoader($container, new FileLocator(APP_ROOT_DIR . '/src/Pkj/LinuxGenericBackup/Resources'));
        $loader->load('services.yml');


        $processor     = new Processor();
        $configuration = new BackupConfiguration();
        $config = $processor->processConfiguration($configuration, $configs);

        print_r($config);

        $container->setParameter(
            'backup',
            $config
        );
    }

    public function getNamespace() {
        return 'backup';
    }

    public function getXsdValidationBasePath() {}

    public function getAlias() {
        return 'backup';
    }
}