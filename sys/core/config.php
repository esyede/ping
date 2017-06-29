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


class Config {
    protected $hive=[];

    /**
    *   Konstruktor kelas
    */
    function __construct() {
        $this->hive['app']['path']=CONFIG.'app.php';
        $this->hive['core']['path']=CONFIG.'core.php';
        $this->hive['autoload']['path']=CONFIG.'autoload.php';
        $this->hive['db']['path']=CONFIG.'db.php';
        $this->hive['session']['path']=CONFIG.'session.php';
        $this->instance();
    }

    /**
    *   Instance kelas dan muat konfigurasi default
    */
    protected function instance() {
        $this->load($this->hive['app']['path'],'app');
        $this->load($this->hive['core']['path'],'core','core');
        $this->load($this->hive['autoload']['path'],'autoload','autoload');
        $this->load($this->hive['db']['path'],'db','db');
        $this->load($this->hive['session']['path'],'session','session');
    }

    /**
    *   Set data konfigurasi
    *   @param $key string
    *   @param $val string
    *   @param $container string
    */
    function set($key,$val=false,$container='app') {
        $container=strtolower($container);
        if (is_array($key))
            foreach ($key as $k=>$v)
                $this->hive[$container][$k]=$v;
        else $this->hive[$container][$key]=$val;
    }

    /**
    *   Ambil data konfigurasi
    *   @param $key string
    *   @param $container string
    */
    function get($key,$container='app') {
        $container=strtolower($container);
        if (isset($this->hive[$container][$key]))
            return $this->hive[$container][$key];
        return null;
    }

    /**
    *   Ambil semua data konfigurasi
    *   @param $container string
    */
    function getall($container='app') {
        $container=strtolower($container);
        if (isset($this->hive[$container]))
            return $this->hive[$container];
        return null;
    }

    /**
    *   Memuat file konfigurasi
    *   @param $file string
    *   @param $container string
    *   @param $is_array bool
    */
    function load($file,$container,$is_array=false) {
        $container=strtolower($container);
        if (!file_exists($file)) {
            abort("Can't find config file '%s'",[$file],E_ERROR);
            return false;
        }
        else include ($file);

        $this->hive[$container]['path']=$file;
        $this->hive[$container]['container']=$is_array;
        $vars=get_defined_vars();
        if ($is_array!=false)
            $vars=$vars[$is_array];
        unset($vars['_file'],$vars['_name'],$vars['_array']);
        if (count($vars)>0)
            foreach ($vars as $key=>$val)
                if ($key!='this'&&$key!='data')
                    $this->hive[$container][$key]=$val;
        return;
    }

    /**
    *   Tulis set konfigurasi ke file
    *   @param $container string
    */
    function write($container) {
        $container=strtolower($container);
        $contkey=$this->hive[$container]['container'];
        if ($contkey!=false) {
            $old=$this->hive[$container];
            $this->hive[$container]=["$contkey"=>$this->hive[$container]];
        }

        $content="<?php\n";
        $content.="defined('BASE') or exit('Access Denied!');\n\n";
        foreach ($this->hive[$container] as $key=>$val) {
            if (in_array($key,['path','container','array']))
                continue;
            switch (gettype($val)) {
                case "boolean":
                    $val=($val==true)?'true':'false';
                case "integer":
                case "double":
                case "float":
                    $content.="\$$key = ".$val.";\n";
                    break;
                case "array":
                    $val=var_export($val,true);
                    $content.="\$$key = ".$val.";\n";
                    break;
                case "NULL":
                    $content.="\$$key = null;\n";
                    break;
                case "string":
                    $content.=(is_numeric($val))
                    ?"\$$key = ".$val.";\n":"\$$key = '".addslashes($val)."';\n";
                    break;
                default: break;
            }
        }
        $content.="\n?>";
        if ($contkey!=false)
            $this->hive[$container]=$old;
        copy($this->hive[$container]['path'],$this->hive[$container]['path'].'.old.php');
        if (file_put_contents($this->hive[$container]['path'],$content))
            return true;
        else return false;
    }

    /**
    *   Restore konfigurasi terdahulu
    *   @param $container string
    */
    function restore($container) {
        return copy($this->hive[$container]['path'].'.old.php',$this->hive[$container]['path']);
    }
}
