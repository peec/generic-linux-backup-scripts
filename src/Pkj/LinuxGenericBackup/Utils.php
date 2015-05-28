<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 09.08.14
 * Time: 02:19
 */

namespace Pkj\LinuxGenericBackup;


class Utils {

    static public function cmp($a, $b)
    {
        return strlen($b) - strlen($a);
    }



    static public function arrayToLinear ($data, &$returnArray, $pathSeparator = '.', $path = '') {
        if (is_array($data)) {
            foreach($data as $k => $v) {
                $newPath = $path . ($path ? $pathSeparator : '') . $k;
                if (!is_array($v)) {
                    $returnArray[$newPath] = $v;
                }
                self::arrayToLinear($v, $returnArray, $pathSeparator, $newPath);
            }
            return $data;
        } else {
            return $data;
        }
    }
    static public function linearToArray($map, $pathSeparator =  '.') {
        $multidimensionalArray = array();
        foreach($map as $linKey => $linValue) {
            $keys = explode($pathSeparator, $linKey);
            $i = 0;
            $path = '';
            foreach($keys as $mlKey) {
                if ($i == 0) {
                    $path = $mlKey;
                } else {
                    $path .= '[' . $mlKey . ']';
                }
                $i++;
            }
            $path .= "=$linValue";
            parse_str($path, $obj);
            $multidimensionalArray = array_merge_recursive($multidimensionalArray, $obj);
        }
        return $multidimensionalArray;
    }



    static public function sprintf2($str='', $vars=array(), $char='%')
    {
        if (!$str) return '';
        if (count($vars) > 0)
        {
            foreach ($vars as $k => $v)
            {
                $str = str_replace($char . $k, $v, $str);
            }
        }

        return $str;
    }
} 