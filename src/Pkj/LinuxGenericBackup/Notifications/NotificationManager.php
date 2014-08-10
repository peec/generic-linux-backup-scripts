<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 10.08.14
 * Time: 02:34
 */

namespace Pkj\LinuxGenericBackup\Notifications;


class NotificationManager implements  NotificationInteface{

    private $notifiers = array();


    public function __construct (array $notifiers) {
        $this->notifiers = (array)$notifiers;
    }

    public function add(NotificationInteface $notifier) {
        $this->notifiers[] = $notifier;
    }

    public function error($msg) {
        foreach($this->notifiers as $notifier) {
            $notifier->error($msg);
        }
    }
    public function info($msg) {
        foreach($this->notifiers as $notifier) {
            $notifier->info($msg);
        }
    }

}