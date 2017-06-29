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


class Ping {
    protected $router;
    protected $dispatch;

    /**
    *   Jalankan Ping Framework
    */
    function run() {
        $this->router=summon('router');
        $this->router->compile();
        $routes=$this->router->detail();
        $controller=$GLOBALS['controller']=$routes['controller'];
        $action=$GLOBALS['action']=$routes['action'];
        $query_string=$GLOBALS['query_string']=$routes['query_string'];
        if (file_exists(APP.'controller'.DS.$controller.'.php'))
            include (APP.'controller'.DS.$controller.'.php');
        else error(404);
        $controller=ucfirst($controller);
        $this->dispatch=new $controller();
        if (method_exists($controller,$action)) {
            $this->dispatch($controller,'_beforeroute',$query_string);
            $this->dispatch($controller,$action,$query_string);
            $this->dispatch($controller,'_afterroute',$query_string);
        }
        else error(404);
    }

    /**
    *   Petakan request ke kelas dan fungsi dalam file kontroler
    *   @param $controller string
    *   @param $action string
    *   @param $query_string array
    */
    protected function dispatch($controller,$action,$query_string=null) {
        if (method_exists($controller,$action))
            return call_user_func_array([$this->dispatch,$action],$query_string);
        return false;
    }
}
