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


class Loader {

    /**
    *   Memuat library
    *   @param $name string
    *   @param $instance bool
    *   @param $silent bool
    */
    function lib($name,$instance=true,$silent=false) {
        if (strpos($name,".")!==false)
            $name=str_replace('.',DS,$name);
        $class=summon(ucfirst($name),'Lib',$silent);
        if ($instance!==false) {
            ($instance!==true)?$name=$instance:'';
            $inst=instance();
            if ($inst!==false)
                (!isset($inst->$name))?$inst->$name=$class:'';
        }
        return $class;
    }

    /**
    *   Memuat plugin
    *   @param $name string
    */
    function plugin($name,$instance=true,$silent=false) {
        if (strpos($name,".")!==false)
            $name=str_replace('.',DS,$name);
        $class=summon(ucfirst($name),'Plugin',$silent);
        if ($instance!==false) {
            ($instance!==true)?$name=$instance:'';
            $inst=instance();
            if ($inst!==false)
                (!isset($inst->$name))?$inst->$name=$class:'';
        }
        return $class;
    }

    /**
    *   Memuat database
    *   @param $name string
    *   @param $instance bool
    *   @param $silent bool
    */
    function db($container,$instance=true,$silent=false) {
        if (!is_array($container)) {
            $object=\Base::instance()->load("connection_".$container);
            if ($object!==null) {
                if ($instance!=false)
                    goto Instance;
                return $object;
            }
            $info=summon('config')->get($container,'db');
            if ($info===null)
                abort("Database credentials for '%s' can't be found on config file",[$container],E_ERROR);
        }
        else {
            $info=$container;
            if (is_bool($instance)||is_numeric($instance)) {
                $instance=false;
                $container='default_connection';
            }
            else $container=$instance;
        }
        $info['driver']=strtolower($info['driver']);
        if ($info['driver']=='jong')
            $namespace='Jong';
        elseif (in_array($info['driver'],['mongodb','mongo']))
            $namespace='Mongo';
        else $namespace='Sql';
        require_once (SYS.'db'.DS.strtolower($namespace).DS.'mapper.php');
        $driver='Sys\\Db\\'.$namespace.'\\Mapper';
        try {
            $object=new $driver($info);
        }
        catch (\Exception $e) {
            $object=false;
        }
        if ($silent==false&&$object==false)
            abort("Can't connect to database '%s' via %s:%s",
                [$info['database'],$info['host'],$info['port']],E_ERROR);
        \Base::instance()->save("connection_".$container,$object);
        Instance:
        {
            if ($instance!=false&&!is_numeric($container)) {
                if ($instance===true)
                    $instance=$container;
                $inst=instance();
                if ($inst!==false)
                    (!isset($inst->$instance))?$inst->$instance=$object:'';
            }
        }
        return $object;
    }

    /**
    *   Memuat model
    *   @param $name string
    *   @param $as string
    */
    function model($name,$as=null) {
        if (strpos($name,'/')!==false)
            $class=ucfirst(end(explode('/',$name)));
        else $class=ucfirst($name);
        require_once (APP.'model'.DS.strtolower($name).'.php');
        $object=new $class();
        if ($as!==null)
            instance()->$as=$object;
        else instance()->$class=$object;
        return $object;
    }

    /**
    *   Memuat view
    *   @param $name string
    *   @param $data array
    *   @param $callback bool
    */
    function view($name,$data=[],$callback=false) {
        if (!is_array($data)) {
            abort("Variable '%s' has a non-array format on '%s' method",[$data,'Loader::view'],E_WARNING);
            $data=[];
        }
        $file=APP.'view'.DS.$name;
        extract($data);
        if (file_exists($file)) {
            ob_start();
            include ($file);
            $content=ob_get_contents();
            ob_end_clean();
            if ($callback==false)
                echo $content;
            return $content;
        }
        else {
            abort("Can't find vew file '%s'",[$name],E_ERROR);
            return false;
        }
    }

    /**
    *   Memuat informasi framework
    *   @param $arg string
    */
    function sysinfo($arg=null) {
        $base=summon('router')->detail()['site_url'];
        $bench=summon('benchmark');
        switch ($arg) {
            case 'base':
                return $base;
                break;
            case 'package':
                return 'Ping Framework';
                break;
            case 'version':
                return '0.1-alpha';
                break;
            case 'memory':
                return $bench->memory();
                break;
            case 'elapsed':
                return $bench->elapsed('system',3);
                break;
            default:
                return [
                    'base'=>$base,
                    'package'=>'Ping Framework',
                    'version'=>'0.1-alpha',
                    'memory'=>$bench->memory(),
                    'elapsed'=>$bench->elapsed('system',4)
                ];
                break;
        }
    }
}
