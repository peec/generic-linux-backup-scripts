<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 09.08.14
 * Time: 00:46
 */

namespace Pkj\LinuxGenericBackup;


class JsonFileExpressionParser {

    private $configFile;
    private $config;

    public function __construct ($configFile, $defaults = array()) {
        $this->configFile = $configFile;
        $cnf = json_decode(file_get_contents($configFile), true);
        if (!$cnf) {
            throw new \Exception("Could not parse config file $configFile as JSON. Correct the JSON format.");
        }

        $this->config = array_merge($defaults, $cnf);

    }

    /**
     * Example specification
     *
     * test:array.some_value:string
     * or
     * test.some_value:int
     * or
     * test.test1.test2:array.test:int
     *
     * @param $specification
     * @throws \Exception
     */
    public function requireConfig ($specification) {
        $bits = explode('.', $specification);
        $cfg = $this->config;
        $configFile = $this->configFile;

        $buildBits = function ($build, $msg) use ($configFile) {
            return "Configuration file error in {$configFile}:\n ".implode('.', $build) . ' '. $msg;
        };

        $build = [];
        foreach($bits as $b) {
            $b = explode(':', $b);
            $key = $b[0];
            $type = isset($b[1]) ? $b[1] : false;
            $build[] = $key;
            if (!isset($cfg[$key])) {
                throw new \Exception($buildBits($build, "should be defined as $type."));
            }

            if ($type !== false) {
                if (!call_user_func("is_$type", $cfg[$key])) {
                    throw new \Exception($buildBits($build, "should be of type $type."));
                }
            }

            $cfg = $cfg[$key];
        }
    }

    public function get() {
        return $this->config;
    }
} 