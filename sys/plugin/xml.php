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


class Xml {
    public $tab="\t";
    public $root='result';

    /**
    *   Ubah karakter khusus dengan padanannya pada XML
    *   @param $str string
    */
    function escape($str) {
        static $find=[];
        static $replace=[];
        if (empty($find)) {
            for ($i=0;$i<32;$i++) {
                if ($i==9||$i==10||$i==13) {
                    $find[]=chr($i);
                    $replace[]='&#'.$i.';';
                }
                else {
                    $find[]=chr($i);
                    $replace[]=null;
                }
            }
            $find=array_merge(['&','<','>','"','`'],$find);
            $replace=array_merge(['&amp;','&lt;','&gt;','&quot;','&apos;'],$replace);
        }
        return str_replace($find,$replace,$str);
    }

    /**
    *   Cetak array ke dokumen XML
    *   @param $vars mixed
    *   @param $enc string
    */
    function draw($vars,$enc='UTF-8') {
        $xml='<?xml version="1.0" encoding="'.$enc.'" ?>';
        $xml.=$this->join($vars,$this->root);
        return $xml;
    }

    /**
    *   Ubah array kedalam segmen struktur XML secara rekursif
    *   @param $val mixed
    *   @param $root string
    *   @param $tabs int
    */
    private function join($val,$root='result',$tabs=-1) {
        $xml=null;
        $tab=$this->tab;
        if (is_numeric($root))
            $root=$this->root;
        if (is_object($val))
            $val=(array)$val;
        if (is_array($val)) {
            foreach ($val as $key=>$val2) {
                $key=preg_replace('/[^a-z0-9_:.-]/i','',$key);
                $sub=$this->join($val2,$key,++$tabs);
                $xml.="\n";
                $br=is_array($val2)||is_object($val2);
                if (is_numeric($key))
                    $xml.=str_repeat($tab,$tabs).
                        '<'.$root.'>'.$sub.($br?"\n".str_repeat($tab,$tabs):'').'</'.$root.'>';
                else {
                    if ($sub!='') {
                        if (is_array($val2)
                        &&count(array_diff_key($val2,array_keys(array_keys($val2))))==0)
                            $xml.=str_repeat($tab,$tabs).$sub;
                        else $xml.=str_repeat($tab,$tabs).
                            '<'.$key.'>'.$sub.($br?"\n".str_repeat($tab,$tabs):'').'</'.$key.'>';
                    }
                    else $xml.=str_repeat($tab,$tabs).'<'.$key.' />';
                }
                $tabs--;
            }
        }
        else $xml.=$this->escape($val);
        return $xml;
    }
}
