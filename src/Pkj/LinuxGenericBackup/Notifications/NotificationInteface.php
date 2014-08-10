<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 09.08.14
 * Time: 23:59
 */

namespace Pkj\LinuxGenericBackup\Notifications;


interface NotificationInteface {

    public function error($msg);

    public function info($msg);

} 