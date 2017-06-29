<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Core;
defined('BASE') or exit('Access Denied!');


class Controller implements \ArrayAccess {
    public $controller;
    public $action;
    public $query_string;
    public $hive;
    private static $instance;

    /**
    *   Konstruktor
    */
    function __construct($hive=[]) {
        $this->arrset($hive);
        self::$instance=$this;
        $this->controller=$GLOBALS['controller'];
        $this->action=$GLOBALS['action'];
        $this->query_string=$GLOBALS['query_string'];
        $this->load=summon('loader');
        $this->config=summon('config');
        $classes=$this->config->get('plugins','autoload');
        if (count($classes)>0)
            foreach ($classes as $class)
                $this->load->plugin($class);
        $classes=$this->config->get('libraries','autoload');
        if (count($classes)>0)
            foreach ($classes as $class)
                $this->load->lib($class);
    }

    /**
    *   Instance kelas
    */
    public static function instance() {
        return self::$instance;
    }

    /**
    *   Jalankan aksi sebelum routing
    */
    function _beforeroute() {}

    /**
    *   Jalankan aksi setelah routing
    */
    function _afterroute() {}

    /**
    *   Redirect url
    *   @param $url string
    *   @param $wait int
    */
    function redirect($url=null,$wait=0) {
        $base=summon('loader')->sysinfo('base');
        if($url==null)
            $url=$base;
        elseif (summon('web')->isurl($url))
            $url=$url;
        else $url=$base.$url;
        try {
            ob_start();
            if ($wait<0)
                header('Refresh:'.$wait.';url='.$url);
            else header('Location: '.$url);
            ob_end_flush();
            exit;
        }
        catch (\Exception $ex) {
            abort("Can't redirect to specified url: %s",[$url],E_ERROR);
        }
    }

    /**
    *   Set value ke hive
    *   @param $key string
    *   @param $value mixed
    */
    function set($key,$value=null) {
        if (is_string($key)) {
            if (is_array($value)&&!empty($value)) {
                foreach ($value as $k=>$v)
                    $this->set("$key.$k",$v);
            }
            else {
                $keys=explode('.',$key);
                $hive=&$this->hive;
                foreach ($keys as $key) {
                    if (!isset($hive[$key])||!is_array($hive[$key]))
                        $hive[$key]=[];
                    $hive=&$hive[$key];
                }
                $hive=$value;
            }
        }
        elseif (is_array($key))
            foreach ($key as $k=>$v)
                $this->set($k,$v);
    }

    /**
    *   Menambahkan array value ke hive-path
    *   @param $key string
    *   @param $value mixed
    *   @param $pop bool
    */
    function add($key,$value=null,$pop=false) {
        if (is_string($key)) {
            if (is_array($value)) {
                foreach ($value as $k=>$v)
                    $this->add("$key.$k",$v,true);
            }
            else {
                $keys=explode('.',$key);
                $hive=&$this->hive;
                if ($pop===true)
                    array_pop($keys);
                foreach ($keys as $key) {
                    if (!isset($hive[$key])||!is_array($hive[$key]))
                        $hive[$key]=[];
                    $hive=&$hive[$key];
                }
                $hive[]=$value;
            }
        } elseif (is_array($key))
            foreach ($key as $k=>$v)
                $this->add($k,$v);
    }

    /**
    *   Mengambil nilai hive
    *   @param $key string
    *   @param $default mixed
    */
    function get($key,$default=null) {
        $keys=explode('.',(string)$key);
        $hive=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($hive,$key))
                return $default;
            $hive=&$hive[$key];
        }
        return $hive;
    }

    /**
    *   Ambil value dari sebuah path atau semua value tersimpan lalu hapus
    *   @param $key string
    *   @param $default mixed
    */
    function pull($key=null,$default=null) {
        if (is_string($key)) {
            $value=$this->get($key,$default);
            $this->del($key);
            return $value;
        }
        if (is_null($key)) {
            $value=$this->hive;
            $this->clear();
            return $value;
        }
    }

    /**
    *   Cek apakah path-hive ada
    *   @param $key string
    */
    function has($key) {
        $keys=explode('.',(string)$key);
        $hive=&$this->hive;
        foreach ($keys as $key) {
            if (!$this->exists($hive,$key))
                return false;
            $hive=&$hive[$key];
        }
        return true;
    }

    /**
    *   Cek apakah key ada dalam hive
    *   @param $hive array
    *   @param $key string
    */
    function exists($hive,$key) {
        if ($hive instanceof \ArrayAccess)
            return isset($hive[$key]);
        return array_key_exists($key,$hive);
    }

    /**
    *   Hapus path dalam hive
    *   @param $key mixed
    */
    function del($key) {
        if (is_string($key)) {
            $keys=explode('.',$key);
            $hive=&$this->hive;
            $last=array_pop($keys);
            foreach ($keys as $key) {
                if (!$this->exists($hive,$key))
                    return;
                $hive=&$hive[$key];
            }
            unset($hive[$last]);
        }
        elseif (is_array($key))
            foreach ($key as $k)
                $this->del($k);
    }


    /**
    *   Kosongkan hive
    *   @param $key mixed
    */
    function clear($key=null) {
        if (is_string($key))
            $this->set($key,[]);
        elseif (is_array($key))
            foreach ($key as $k)
                $this->clear($k);
        elseif (is_null($key))
            $this->hive=[];
    }

    /**
    *   Urutkan semua value hive
    *   @param $key string
    */
    function sort($key=null) {
        if (is_string($key)) {
            $values=$this->get($key);
            return $this->arrsort((array)$values);
        }
        elseif (is_null($key))
            return $this->arrsort($this->hive);
    }

    /**
    *   Urutkan semua value hive secara rekursif
    *   @param $key string
    *   @param $hive array
    */
    function recsort($key=null,$hive=null) {
        if (is_array($hive)) {
            foreach ($hive as &$value)
                if (is_array($value))
                    $value=$this->recsort(null,$value);
            return $this->arrsort($hive);
        }
        elseif (is_string($key)) {
            $values=$this->get($key);
            return $this->recsort(null,(array)$values);
        }
        elseif (is_null($key))
            return $this->recsort(null,$this->hive);
    }

    /**
    *   Urutkan array hive
    *   @param $hive array
    */
    function arrsort($hive) {
        $this->isassoc($hive)?ksort($hive):sort($hive);
        return $hive;
    }

    /**
    *   Cek apakah value hive bisa diakses
    *   @param $value array
    */
    function accessible($value) {
        return is_array($value)||$value instanceof ArrayAccess;
    }

    /**
    *   Cek apakah dia array asosiatif
    *   @param $hive array
    */
    function isassoc($hive=null) {
        $keys=is_array($hive)?array_keys($hive):array_keys($this->hive);
        return array_keys($keys) !== $keys;
    }

    /**
    *   Simpan array ke hive
    *   @param $hive array
    */
    function arrset($hive) {
        if ($this->accessible($hive))
            $this->hive=$hive;
    }

    /**
    *   Simpan array sebagai reference
    *   @param $hive array
    */
    function ref(&$hive) {
        if ($this->accessible($hive))
            $this->hive=&$hive;
    }

    /**
    *   Ambil semua data di variabel hive
    */
    function hive() {
        return $this->hive;
    }

    //--------------------------------------------------------------------------
    // Abstract Method untuk ArrayAccess
    //--------------------------------------------------------------------------
    function offsetSet($offset,$value) {
        $this->set($offset,$value);
    }
    function offsetExists($offset) {
        return $this->has($offset);
    }
    function offsetGet($offset) {
        return $this->get($offset);
    }
    function offsetUnset($offset) {
        $this->del($offset);
    }

    //--------------------------------------------------------------------------
    // Magic Methods
    //--------------------------------------------------------------------------


    function __set($key,$value=null) {
        $this->set($key,$value);
    }

    function __get($key) {
        return $this->get($key);
    }

    function __isset($key) {
        return $this->has($key);
    }

    function __unset($key) {
        $this->del($key);
    }
}
