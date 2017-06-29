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


class Router {
    protected $protocol;
    protected $http_host;
    protected $site_url;
    protected $uri;
    protected $site_dir;
    protected $controller;
    protected $action;
    protected $query_string;

    /**
    *   Konstruktor kelas
    */
    function __construct() {
        $this->config=summon('config');
        $this->web=summon('web');
    }

    /**
    *   Compile parameter url ke kelas dan method kontroler
    */
    function compile() {
        $this->http_host=rtrim($_SERVER['HTTP_HOST'],'/');
        $this->site_dir=dirname($_SERVER['PHP_SELF']);
        if (isset($_SERVER['HTTPS'])) {
            if (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')
                $this->protocol='https';
            else $this->protocol='http';
        }
        else $this->protocol='http';
        $site_url=$this->http_host.'/'.$this->site_dir;
        if (strpos($site_url,'//')!==false)
            $site_url=str_replace('//','/',$site_url);
        $this->site_url=$this->protocol.'://'.$site_url;
        if (!$this->config->get('enable_query_string','core'))
            $this->uri=rtrim((isset($_GET['url'])?$this->web->get('url',true):''),'/');
        else {
            $ctrl_args=$this->config->get('controller_query_string','core');
            $act_args=$this->config->get('action_query_string','core');
            $ctrl_qs=$this->web->get($ctrl_args,true);
            if (!$ctrl_qs)
                $this->uri='';
            else {
                $act_qs=$this->web->get($act_args,true);
                if (!$act_qs)
                    $act_qs=$this->config->get('default_action','core');
                $this->uri=$ctrl_qs.'/'.$act_qs;
                $all_ctrl_qs=$this->web->escape($_SERVER['QUERY_STRING']);
                $all_ctrl_qs=explode('&',$all_ctrl_qs);
                foreach ($all_ctrl_qs as $str) {
                    $str=explode('=',$str);
                    if ($str[0]==$ctrl_args||$str[0]==$act_args)
                        continue;
                    $this->uri.='/'.$str[1];
                }
            }
        }
        if (empty($this->uri)) {
            $controller=$this->config->get('default_controller','core');
            $action=$this->config->get('default_action','core');
            $query_string=[];
        }
        else {
            $this->uri=ltrim(str_replace('//','/',$this->uri),'/');
            $segment=[];
            $segment=explode('/',$this->uri);
            $controller=$segment[0];
            array_shift($segment);
            if (isset($segment[0])&&!empty($segment[0])) {
                $action=$segment[0];
                array_shift($segment);
            }
            else $action=$this->config->get('default_action','core');
            $query_string=$segment;
        }
        if (strncmp($controller,'_',1)==0||strncmp($action,'_',1)==0)
            error(404);
        $this->controller=$controller;
        $this->action=$action;
        $this->query_string=$query_string;
    }

    /**
    *   Info detail route
    */
    function detail() {
        return [
            'protocol'=>$this->protocol,
            'http_host'=>$this->http_host,
            'site_url'=>$this->site_url,
            'site_dir'=>$this->site_dir,
            'uri'=>$this->uri,
            'controller'=>$this->controller,
            'action'=>$this->action,
            'query_string'=>$this->query_string
        ];
    }

    /**
    *   Mengmbil sebuah segmen url (basis nol)
    *   @param $index int
    */
    function segment($index) {
        $uri=explode('/',$this->detail()['uri']);
        if (isset($uri[$index]))
            return $uri[$index];
        return false;
    }
}
