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


class Arrays {

    /**
    *   Ambil elemen pertama dari sebuah array
    *   @param $arr array
    */
    function first(array $arr) {
        if (is_array($arr))
            return array_values($arr)[0];
        return false;
    }

    /**
    *   Ambil elemen terakhir dari sebuah array
    *   @param $arr array
    */
    function last(array $arr) {
        if (is_array($arr))
            return $this->first(array_reverse($arr));
        return false;
    }

    /**
    *   Cek apakah key ada dalam array
    *   @param $key string
    *   @param $arr array
    */
    function kexist($key,array $arr) {
        if (is_string($key)&&is_array($arr))
            return array_key_exists($key,$arr);
        return false;
    }

    /**
    *   Cek apakah value ada dalam array
    *   @param $val mixed
    *   @param $arr array
    */
    function vexist($val,array $arr) {
        if (is_array($arr))
            foreach ($arr as $el)
                if ($el==$val)
                    return true;
        return false;
    }

    /**
    *   Tambahkan pasangan key/value ke array
    *   @param $arr array
    *   @param $val mixed
    *   @param $key string|null
    */
    function add(&$arr,$val,$key=null) {
        if (is_array($arr)) {
            if (!empty($key)&&is_string($key))
                $arr[$key]=$val;
            else $arr[]=$val;
            return $arr;
        }
    }

    /**
    *   Hapus elemen array
    *   @param $arr array
    *   @param $key string
    */
    function remove(&$arr,$key) {
        if (is_array($arr)) {
            unset($arr[$key]);
            return $arr;
        }
    }

    /**
    *   Ambil elemen array
    *   @param $arr array
    *   @param $key string
    */
    function get(array $arr,$key) {
        if (is_array($arr)&&array_key_exists($key,$arr))
            return $arr[$key];
    }

    /**
    *   Ambil elemen array secara acak
    *   @param $arr array
    */
    function random(array $arr) {
        if (is_array($arr))
            return $arr[array_rand($arr)];
        return false;
    }

    /**
    *   Gabungkan 2 buah array
    *   @param $arr1 array
    *   @param $arr2 array
    *   @param $matrix bool
    */
    function merge(array $arr1,array $arr2,$matrix=false) {
        if (is_array($arr1)&&is_array($arr2)) {
            if ($matrix)
                return array_combine($arr1,$arr2);
            else return array_merge($arr1,$arr2);
        }
        return false;
    }

    /**
    *   Ubah multi-array menjadi sebuah array
    *   @param $arr array
    *   @param $deep bool
    */
    function collapse($arr,$deep=false) {
        $res=[];
        if (is_array($arr)) {
            foreach ($arr as $child) {
                if (is_array($child)) {
                    foreach ($child as $val) {
                        if (is_array($val)&&$deep)
                            $res=array_merge($res,$this->collapse($val,true));
                        else $res[]=$val;
                    }
                }
                else $res[]=$child;
            }
            return $res;
        }
        return $arr;
    }

    /**
    *   Pisahkan array menjadi 2 buah array,
    *   array 1 adalah key-nya, array 2 adalah value-nya
    *   @param $arr array
    */
    function split($arr) {
        return [array_keys($arr),array_values($arr)];
    }

    /**
    *   Akses matrix dengan notasi dot (seperti di javascript)
    *   @param $arr array
    */
    function dotify(array $arr,$prep='') {
        $res=[];
        foreach ($arr as $key=>$val) {
            if (is_array($val)&&!empty($val))
                $res=array_merge($res,$this->dotify($val,$prep.$key.'.'));
            else $res[$prep.$key]=$val;
        }
        return $res;
    }
}
