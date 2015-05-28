<?php
/**
 * Created by PhpStorm.
 * User: pk
 * Date: 28.05.15
 * Time: 17:42
 */

namespace Pkj\LinuxGenericBackup;


use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('database');


        return $treeBuilder;
    }
}