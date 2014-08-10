<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 10.08.14
 * Time: 05:54
 */

namespace Pkj\LinuxGenericBackup\Notifications;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class NotificationManagerAddNotifier implements CompilerPassInterface
{

    private $service;

    public function __construct($service) {
        $this->service = $service;
    }
    public function process(ContainerBuilder $container)
    {

        if ($container->has($this->service)) {
            $container->setParameter('notification.manager.services', array_merge($container->getParameter('notification.manager.services'), array(
                new Reference($this->service)
            )));

        }
    }

}