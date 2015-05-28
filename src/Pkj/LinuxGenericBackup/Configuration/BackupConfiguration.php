<?php
namespace Pkj\LinuxGenericBackup\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class BackupConfiguration implements ConfigurationInterface
{


    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();


        $databaseDrivers = array(
            'driver.database.mysql',
            'driver.database.postgresql'
        );

        $notifyDrivers = array(
            'driver.notifier.mailer',
            'driver.notifier.pushover'
        );


        $builder->root('backup')
            ->children()
                ->scalarNode('store')->defaultValue('/opt/backups')->isRequired()->end()
                ->arrayNode('notify')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('to')
                                ->requiresAtLeastOneElement()
                                ->isRequired()
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('with')->isRequired()->end()
                            ->arrayNode('on')
                                ->prototype('enum')->values(array('ERROR','INFO'))->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('packages')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->integerNode('amount')->min(1)->max(1000)->end()
                            ->arrayNode('databases')
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('db')
                                            ->isRequired()
                                            ->requiresAtLeastOneElement()
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->scalarNode('with')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('files')
                                ->prototype('scalar')
                                    ->validate()
                                        ->ifTrue(function ($value) {
                                            return !file_exists($value);
                                        })
                                        ->thenInvalid('%s is not a existing path or file. It must exist if you want to make backup of it :)')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('databases')
                    ->prototype('array')
                        ->children()
                            ->enumNode('driver')->values($databaseDrivers)->isRequired()->end()
                            ->arrayNode('connection')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('notifiers')
                    ->prototype('array')
                        ->children()
                            ->enumNode('driver')->values($notifyDrivers)->isRequired()->end()
                            ->arrayNode('config')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
        return $builder;
    }
}
