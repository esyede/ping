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


class Utf {

    /**
    *   Ambil panjang string
    *   @param $str string
    */
    function strlen($str) {
        preg_match_all('/./us',$str,$parts);
        return count($parts[0]);
    }

    /**
    *   Balik string
    *   @param $str string
    */
    function strrev($str) {
        preg_match_all('/./us',$str,$parts);
        return implode('',array_reverse($parts[0]));
    }

    /**
    *   Ambil posisi pertama string yang cocok (case-insensitive)
    *   @param $stack string
    *   @param $needle string
    *   @param $offset int
    */
    function stripos($stack,$needle,$offset=0) {
        return $this->strpos($stack,$needle,$offset,true);
    }

    /**
    *   Ambil posisi pertama string yang cocok
    *   @param $stack string
    *   @param $needle string
    *   @param $offset int
    *   @param $case bool
    */
    function strpos($stack,$needle,$offset=0,$case=false) {
        return preg_match('/^(.{'.$offset.'}.*?)'.
            preg_quote($needle,'/').'/us'.($case?'i':''),$stack,$match)
            ?$this->strlen($match[1]):false;
    }

    /**
    *   Ambil bagian string dari hasil cocok pertama sampai
    *   akhir dari string (case-insensitive)
    *   @param $stack string
    *   @param $needle string
    *   @param $before bool
    */
    function stristr($stack,$needle,$before=false) {
        return $this->strstr($stack,$needle,$before,true);
    }

    /**
    *   Ambil bagian string dari hasil cocok pertama sampai
    *   akhir dari string
    *   @param $stack string
    *   @param $needle string
    *   @param $before bool
    *   @param $case bool
    */
    function strstr($stack,$needle,$before=false,$case=false) {
        if (!$needle)
            return false;
        preg_match('/^(.*?)'.preg_quote($needle,'/').'/us'.($case?'i':''),$stack,$match);
        return isset($match[1])?($before?$match[1]:$this->substr($stack,$this->strlen($match[1]))):false;
    }

    /**
    *   Ambil potongan string
    *   @param $str string
    *   @param $start int
    *   @param $len int
    */
    function substr($str,$start,$len=0) {
        if ($start<0)
            $start=$this->strlen($str)+$start;
        if (!$len)
            $len=$this->strlen($str)-$start;
        return preg_match('/^.{'.$start.'}(.{0,'.$len.'})/us',$str,$match)?$match[1]:false;
    }

    /**
    *   Hitung jumlah hasil substring
    *   @param $stack string
    *   @param $needle string
    */
    function substr_count($stack,$needle) {
        preg_match_all('/'.preg_quote($needle,'/').'/us',$stack,$matches,PREG_SET_ORDER);
        return count($matches);
    }

    /**
    *   Buang spasi/tab dari awal string
    *   @param $str string
    */
    function ltrim($str) {
        return preg_replace('/^[\pZ\pC]+/u','',$str);
    }

    /**
    *   Buang spasi/tab dari akhir string
    *   @param $str string
    */
    function rtrim($str) {
        return preg_replace('/[\pZ\pC]+$/u','',$str);
    }

    /**
    *   Buang spasi/tab dari awal dan akhir string
    *   @param $str string
    */
    function trim($str) {
        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u','',$str);
    }

    /**
    *   Ambil byte order mark (UTF-8)
    *   @param $str string
    */
    function bom() {
        return chr(0xef).chr(0xbb).chr(0xbf);
    }

    /**
    *   Ubah kode ke simbol unicode
    *   @param $str string
    */
    function translate($str) {
        return html_entity_decode(preg_replace('/\\\\u([[:xdigit:]]+)/i','&#x\1;',$str));
    }

    /**
    *   Ubah token emoji ke simbol font unicode
    *   @param $str string
    */
    function emojify($str) {
        $map=[
            ':('=>'\u2639',
            ':)'=>'\u263a',
            '<3'=>'\u2665',
            ':D'=>'\u1f603',
            'XD'=>'\u1f606',
            ';)'=>'\u1f609',
            ':P'=>'\u1f60b',
            ':,'=>'\u1f60f',
            ':/'=>'\u1f623',
            '8O'=>'\u1f632'
        ];
        return $this->translate(str_replace(array_keys($map),array_values($map),$str));
    }
}
