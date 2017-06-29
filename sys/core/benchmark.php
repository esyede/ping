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


class Benchmark {
    protected $start=[];
    protected $stop=[];

    /**
    *   Memulai benchmark
    *   @param $key string
    */
    function start($key) {
        $this->start[$key]=microtime(true);
    }

    /**
    *   Waktu terpakai
    *   @param $key string
    *   @param $round int
    *   @param $stop string
    */
    function elapsed($key,$round=3,$stop=false) {
        if (!isset($this->start[$key])) {
            abort("Benchmark key '%s' not found",[$key],E_WARNING);
            return false;
        }
        else {
            if (!isset($this->stop[$key])&&$stop==true)
                $this->stop[$key]=microtime(true);
            return round((microtime(true)-$this->start[$key]),$round);
        }
    }

    /**
    *   Memor terpakai
    */
    function memory() {
        $mem=memory_get_usage(true);
        for ($i=0;$mem>=1024&&$i<4;$i++)
            $mem/=1024;
        return round($mem,2).[' B',' KB',' MB'][$i];
    }

    /**
    *   Hentikan benchmark
    *   @param $key string
    */
    function stop($key) {
        $this->stop[$key]=microtime(true);
    }
}
