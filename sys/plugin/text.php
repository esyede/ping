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


class Text implements \Serializable {
    private $var;


    function __construct($var='') {
        $this->var=(string)$var;
    }


    function append($var) {
        $this->var.=(string)$var;
        return $this;
    }


    function prepend($var) {
        $this->var=(string)$var.$this->var;
        return $this;
    }


    function wrap($start,$end=null) {
        $this->var=$start.$this->var.(is_null($end)?$start:$end);
        return $this;
    }


    function lower() {
        $this->var=strtolower($this->var);
        return $this;
    }


    function upper() {
        $this->var=strtoupper($this->var);
        return $this;
    }


    function trim($chars=null) {
        if (is_null($chars))
            $this->var=trim($this->var);
        else $this->var=trim($this->var,$chars);
        return $this;
    }


    function rtrim($chars=null) {
        if (is_null($chars))
            $this->var=rtrim($this->var);
        else $this->var=rtrim($this->var,$chars);
        return $this;
    }


    function ltrim($chars=null) {
        if (is_null($chars))
            $this->var=ltrim($this->var);
        else $this->var=ltrim($this->var,$chars);
        return $this;
    }


    function htmlescape($option=ENT_QUOTES) {
        $this->var=htmlspecialchars($this->var,$option,'UTF-8',false);
        return $this;
    }


    function replace($regex,$rep) {
        if (is_callable($rep))
            $this->var=preg_replace_callback($regex,function ($res) use ($rep) {
                $args=array_map(function ($item) {
                    return new Text($item);
                },$res);
                return call_user_func_array($rep,$args);
            },$this->var);
        else $this->var=preg_replace($regex,$rep,$this->var);
        return $this;
    }


    function strreplace($from,$to) {
        $this->var=str_replace($from,$to,$this->var);
        return $this;
    }


    function indent($space=4) {
        $this->replace('/^/m',str_repeat(' ',$space));
        return $this;
    }


    function outdent($space=4) {
        $this->replace('/^(\t|[ ]{1,'.$space.'})/m','');
        return $this;
    }


    function detab($space=4) {
        $this->replace('/(.*?)\t/',function (Text $w,Text $str) use ($space) {
            return $str.str_repeat(' ',$space-$str->length()%$space);
        });
        return $this;
    }


    function dry() {
        return empty($this->var);
    }


    function match($regex,&$res=null) {
        return preg_match($regex,$this->var,$res)>0;
    }


    function split($regex,$flag=PREG_SPLIT_DELIM_CAPTURE) {
        return array_map(function ($item) {
            return new static($item);
        },preg_split($regex,$this->var,-1,$flag));
    }


    function lines($regex='/(\r?\n)/') {
        $res=[];
        foreach (array_chunk(preg_split($regex,$this->var,-1,PREG_SPLIT_DELIM_CAPTURE),2) as $v)
            $res[]=new Text(implode('',$v));
        return $res;
    }


    function chars() {
        if (strlen($this->var)==$this->length())
            return str_split($this->var);
        return preg_split('//u',$this->var,-1,PREG_SPLIT_NO_EMPTY);
    }


    function perline(callable $callback) {
        $ln=$this->lines();
        foreach ($ln as $k=>$v) {
            $v=new static($v);
            $ln[$k]=(string)call_user_func_array($callback,[$v,$k]);
        }
        $this->var=implode('',$ln);
        return $this;
    }


    function length() {
        if (function_exists('mb_strlen'))
            return mb_strlen($this->var,'UTF-8');
        return preg_match_all("/[\\\\x00-\\\\xBF]|[\\\\xC0-\\\\xFF][\\\\x80-\\\\xBF]*/",$this->var);
    }


    function linecount() {
        return count($this->lines());
    }


    function set($var) {
        $this->var=(string)$var;
        return $this;
    }


    function pos($str,$pos=0) {
        return strpos($this->var,$str,$pos);
    }


    function get() {
        return $this->var;
    }


    function save($path) {
        return file_put_contents($path,$this->var);
    }


    function __toString() {
        return $this->get();
    }


    function serialize() {
        return serialize($this->var);
    }


    function unserialize($str) {
        $this->var=unserialize($str);
    }
}
