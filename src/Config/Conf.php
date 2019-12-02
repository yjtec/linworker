<?php

namespace Yjtec\Linworker\Config;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Conf
 *
 * @author Administrator
 */
class Conf {

    public static $Config;

    public static function setConfig($config) {
        self::$Config = array_merge(self::$Config, $config);
    }

    public static function getConfig() {
        return self::$Config;
    }

    public static function getSystemPlatform() {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'win';
        } else {
            return 'linux';
        }
    }

}
