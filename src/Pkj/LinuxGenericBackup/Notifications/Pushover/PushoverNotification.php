<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 09.08.14
 * Time: 23:33
 */

namespace Pkj\LinuxGenericBackup\Notifications\Pushover;

use Pkj\LinuxGenericBackup\Notifications\NotificationInteface;
use Pkj\LinuxGenericBackup\Notifications\NotificationManager;

class PushoverNotification implements NotificationInteface{

    private $config;

    public function __construct ( $config) {
        $this->config = $config;
    }

    public function error($msg) {
        $this->send($msg, array('priority' => 2, 'expire' => 3600, 'retry' => 60, 'sound' => 'siren'));
    }

    public function info($msg) {
        $this->send($msg, array('priority' => -2));
    }

    public function send ($msg, $args = array()) {
        $values = array_merge(array("message" => $msg), $args);
        if (!function_exists('curl_setopt_array')) {
            throw new \Exception("pushover: CURL extension must be installed (e.g. php5-curl ).");
        }
        $postfields  =array_merge($this->config, $values);
        @curl_setopt_array($ch = curl_init(), array(
            CURLOPT_URL => "https://api.pushover.net/1/messages.json",
            CURLOPT_POSTFIELDS => http_build_query($postfields),
            CURLOPT_RETURNTRANSFER => 1
        ));

        $json = curl_exec($ch);

        if ($result = json_decode($json, true)) {
            if ($result['status']==0) {
                throw new \Exception("Got error from pushover service:\n" . var_export($result, true));
            }
        } else {
            throw new \Exception("pushover: unable to get data from pushover api.");
        }


        curl_close($ch);
    }
}