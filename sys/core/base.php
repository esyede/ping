<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


defined('BASE') or exit('Access Denied!');


class Base {
    private static $hive=[];
    private static $instance;

    /**
    *   Cegah duplikasi
    */
    function __clone() {}

    /**
    *   Instance kelas
    */
    public static function instance() {
        if (!isset(self::$instance))
            self::$instance=new self();
        return self::$instance;
    }

    /**
    *   Simpan objek kelas
    *   @param $key string
    *   @param $val string
    */
    protected function set($key,$val) {
        self::$hive[$key]=$val;
    }

    /**
    *   Ambil objek kelas tersimpan
    *   @param $key string
    */
    protected function get($key) {
        if (isset(self::$hive[$key]))
            return self::$hive[$key];
        return null;
    }

    /**
    *   Muat objek tersimpan
    *   @param $key string
    */
    static function load($key) {
        return self::instance()->get($key);
    }

    /**
    *   Simpan objek kelas secara lokal
    *   @param $key string
    *   @param $instance string
    */
    static function save($key,$instance) {
        return self::instance()->set($key,$instance);
    }
}
