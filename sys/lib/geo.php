<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Lib;
defined('BASE') or exit('Access Denied!');



class Geo {
    protected $ip;
    protected $cache=[];


    function __construct() {
        if (!in_array('geoip',get_loaded_extensions()))
            abort('GeoIP extension needs to be loaded to use this library');
    }


    protected function cache($key,$val) {
        $this->cache[$this->getip().'/'.$key]=$val;
    }


    function detail($name) {
        $rec=$this->record();
        if ($rec===false||$rec[$name]==='')
            return false;
        return $rec[$name];
    }


    protected function getip() {
        $ip=summon('web')->ip();
        if (!is_null($this->ip))
            return $this->ip;
        if ($ip)
            return $ip;
        return false;
    }


    protected function record() {
        $rec=$this->lookup('record');
        if ($rec===null) {
            $rec=geoip_record_by_name($this->getip());
            if (empty($rec))
                $rec=false;
            $this->cache('record',$rec);
        }
        return $rec;
    }


    protected function lookup($key) {
        $key=$this->getip().' / '.$key;
        if (isset($this->cache[$key]))
            return $this->cache[$key];
        return null;
    }


    function area() {
        return $this->detail('area_code');
    }


    function city() {
        return $this->detail('city');
    }


    function continent() {
        $code=$this->lookup('continentCode');
        if ($code===null) {
            $code=geoip_continent_code_by_name($this->getip());
            if ($code==='')
                $code=false;
            $this->cache('continentCode',$code);
        }
        return utf8_encode($code);
    }


    function coordinate() {
        return [$this->latitude(),$this->longitude()];
    }


    function country() {
        $code=$this->lookup('country');
        if ($code===null) {
            $code=geoip_country_name_by_name($this->getip());
            if ($code==='')
                $code=false;
            $this->cache('country',$code);
        }
        return utf8_encode($code);
    }


    function countrycode($len=3) {
        $key='countrycode.'.$len;
        $code=$this->lookup($key);
        if ($code===null) {
            if ($len===3)
                $code=geoip_country_code3_by_name($this->getip());
            else $code=geoip_country_code_by_name($this->getip());
            if ($code==='')
                $code=false;
            $this->cache($key,$code);
        }
        return utf8_encode($code);
    }


    function formatted() {
        $res=$this->city().', '.$this->country();
        if ($this->city()===false||$this->city()==='')
            $res=$this->country();
        else if ($this->countrycode(2)==='US'||$this->countrycode(2)==='CA')
            if ($this->region()!==false&&$this->region()!=='')
                $res=$this->city().', '.$this->region();
        return $res;
    }


    function latitude() {
        return utf8_encode($this->detail('latitude'));
    }


    function longitude() {
        return utf8_encode($this->detail('longitude'));
    }


    function postal() {
        return utf8_encode($this->detail('postal_code'));
    }


    function province() {
        return utf8_encode($this->region());
    }


    function region() {
        $reg=$this->lookup('region');
        if ($reg===null) {
            $reg=geoip_region_name_by_code($this->countrycode(2),$this->regioncode());
            if ($reg==='')
                $reg=false;
            $this->cache('region',$reg);
        }
        return utf8_encode($reg);
    }


    function regioncode() {
        return utf8_encode($this->detail('region'));
    }


    function state() {
        return utf8_encode($this->region());
    }


    function timezone() {
        $tz=$this->lookup('timezone');
        if ($tz===null) {
            $tz=geoip_time_zone_by_country_and_region($this->countrycode(2),$this->regioncode());
            if ($tz==='')
                $tz=false;
            $this->cache('timezone',$tz);
        }
        return utf8_encode($tz);
    }


    function zip() {
        return utf8_encode($this->zipcode());
    }


    function zipcode() {
        return utf8_encode($this->postal());
    }


    function ip($ip) {
        $this->ip=$ip;
    }
}
