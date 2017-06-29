<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Plugin;
defined('BASE') or exit('Access Denied!');


class Cli {

    /**
    *   Cek apakah request ini dari CLI
    */
    function iscli() {
        return substr(php_sapi_name(),0,3)=='cli';
    }

    /**
    *   Cetak teks ke output CLI (berwarna)
    *   @param $str string
    *   @param $color string
    */
    function output($str,$color='default') {
        $colors=[
            'black'=>30,
            'blue'=>34,
            'green'=>32,
            'cyan'=>36,
            'red'=>31,
            'purple'=>35,
            'brown'=>33,
            'gray'=>37
        ];
        if ($this->iscli()) {
            if (array_key_exists($color,$colors))
                print "\033[".$colors[$color].'m'.$str."\033[0m \n";
            else print $str."\n";
        }
    }

    /**
    *   Jalankan perintah CLI
    *   @param $cmd string
    *   @param $bypass bool
    */
    function execute($cmd,$bypass=false,&$out=null) {
        if ($bypass)
            passthru($cmd,$out);
        else exec($cmd,$out);
    }

    /**
    *   Bersihkan dan amankan argumen
    *   @param $arg string
    */
    function escape($arg) {
        return escapeshellarg($arg);
    }
}
