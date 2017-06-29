<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


defined('BASE') or exit('Access Denied!');

/**
*   Autoloader kelas
*   @param $class string
*/
function __autoload($class) {
    $part=explode('\\',strtolower($class));
    if (empty($part[0]))
        $part=array_shift($part);
    $file=str_replace('\\',DS,BASE.implode('\\',$part).'.php');
    if (!file_exists($file))
        abort("Autoload failed to load '%s' class",[ucfirst($class)],E_ERROR);
    require_once $file;
}

/**
*   Memuat kelas sistem (file-file di /sys/core)
*   @param $class string
*   @param $type string
*   @param $silent bool
*/
function summon($class,$type='core',$silent=false) {
    $cls=$class;
    $class=ucfirst(strtolower($class));
    $type=ucfirst(strtolower($type));
    if (strpos($class,'\\')===false)
        $class=$type.'\\'.$class;
    $stored=strtolower($class);
    $name=str_replace('\\', '_',$stored);
    $loaded=\Base::instance()->load($name);
    if ($loaded!==null)
        return $loaded;
    $part=explode('\\',$stored);
    $end=count($part)-1;
    $part[$end]=ucfirst($part[$end]);
    $file=implode(DS,$part);
    if ($part[0]!=='sys'&&$part[0]!=='app') {
        if (file_exists(APP.$file.'.php')) {
            $file=APP.$file.'.php';
            $class='\App\\'.$class;
        }
        else {
            $file= SYS.$file.'.php';
            $class='\Sys\\'.$class;
        }
    }
    else $file=BASE.$file.'.php';
    $file=explode(DS,$file);
    $end=count($file)-1;
    $file[$end]=strtolower($file[$end]);
    $file=implode(DS,$file);
    if (!file_exists($file))
        abort("Autoload failed to load '%s' class",[$cls],E_ERROR);
    require($file);
    try {
        $object=new $class();
    }
    catch(\Exception $e) {
        $msg=$e->getMessage();
        $object=false;
    }
    if($object==false&&$silent==false)
        abort("Autoload failed to initialize '%s' class : %s",[$cls,$msg],E_ERROR);
    \Base::instance()->save($name,$object);
    return $object;
}

/**
*   Kerberos, error handler
*   @param $e_type keyword
*   @param $e_str string
*   @param $e_file string
*   @param $e_line string
*/
function kerberos($e_type,$e_str,$e_file,$e_line) {
    if (!$e_type)
        return false;
    summon('logger')->err($e_type,$e_str,$e_file,$e_line,debug_backtrace());
    return true;
}

/**
*   Matikan Ping Framework
*/
function stop() {
    $error=error_get_last();
    $logger=summon('logger');
    $logger->debug();
    if (is_array($error)
    &&summon('config')->get('trap_fatal_error','core')===true)
        if ($error['type']==E_ERROR||$error['type']==E_PARSE)
            $logger->err($error['type'],$error['message'],$error['file'],$error['line']);
}

/**
*   Matikan Ping Framework dengan debugger
*   @param $error string
*   @param $level keyword
*   @param $arg mixed
*/
function abort($error='none',$arg=null,$level=E_ERROR) {
    $trace=debug_backtrace();
    if ($arg!==null)
        $error=vsprintf($error,$arg);
    summon('logger')->err($level,$error,$trace[0]['file'],$trace[0]['line'],$trace);
}

/**
*   Tampilkan halaman error berdasarkan status error code
*   @param $code int
*   @param $msg string
*/
function error($code,$msg=null) {
    if (!empty($msg))
        summon('logger')->abort((int)$code,$msg);
    else summon('logger')->abort((int)$code);
}

/**
*   Log pesan ke file
*   @param $message string
*   @param $file string
*/
function debug($message,$file='debug.log') {
    summon('logger')->log($message,$file);
}

/**
*   Instance kelas kontroler utama
*/
function instance() {
    if (class_exists('\Sys\Core\Controller',false))
        return \Sys\Core\Controller::instance();
    else return false;
}

//! ---------------------------------------------------------------------
//! Daftarkan fungsi shutdown dan error handler ke PHP
//! ---------------------------------------------------------------------
set_error_handler('kerberos',E_ALL|E_STRICT);
register_shutdown_function('stop');
