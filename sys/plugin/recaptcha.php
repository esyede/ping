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


class Recaptcha {
    const SERVER='//www.google.com/recaptcha/api';
    const VERIFY_SERVER='www.google.com';
    protected $ip;
    protected $privkey;
    protected $pubkey;
    protected $error;
    protected $theme='light';
    protected $type='image';
    protected $size='normal';
    protected $tabindex=0;


    function pubkey($key) {
        $this->pubkey=$key;
        return $this;
    }


    function getpubkey() {
        return $this->pubkey;
    }


    function privkey($key) {
        $this->privkey=$key;
        return $this;
    }


    function getprivkey() {
        return $this->privkey;
    }


    function ip($ip) {
        $this->ip=$ip;
        return $this;
    }


    function getip() {
        $ip=summon('web')->ip();
        if ($this->ip)
            return $this->ip;
        if ($ip)
            return $ip;
        return null;
    }

    function error($error) {
        $this->error=(string)$error;
        return $this;
    }

    function geterror() {
        return $this->error;
    }

    function render() {
        $web=summon('web');
        if (!$this->getpubkey())
            abort('You must set public key provided by reCaptcha');
        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>'.
            '<div class="g-recaptcha" data-sitekey="'.$this->getpubkey().
            '" data-theme="'.$this->theme.'" data-type="'.$this->type.'" data-size="'.$this->size.
            '" data-tabindex="'.$this->tabindex.'"></div>';
    }

    function check($response=false) {
        if (!$this->getprivkey())
            abort('You must set private key provided by reCaptcha');
        if (!$response)
            if ($web->post('g-recaptcha-response'))
                $response=$web->post('g-recaptcha-response');
        $res=new Response();
        if (strlen($response)==0) {
            $res->validity(false);
            $res->error('Incorrect-captcha-sol');
            return $res;
        }
        $ans=@json_decode($this->process([
            'secret'=>$this->getprivkey(),
            'ip'=>$this->getip(),
            'response'=>$response
        ]),true);
        if (is_array($ans)
        &&isset($ans['success'])
        &&$ans['success'])
            $res->validity(true);
        else {
            $res->validity(false);
            $res->error(serialize($ans));
        }
        return $res;
    }


    protected function process($param) {
        $param=http_build_query($param);
        $res=@file_get_contents('https://'.self::VERIFY_SERVER.'/recaptcha/api/siteverify?'.$param);
        if (!$res)
            abort("Unable to communicate with reCaptcha servers. Response: %s",[serialize($res)]);
        return $res;
    }


    protected static function checktheme($theme) {
        return (bool)in_array($theme,['light','dark']);
    }


    protected static function checksize($size) {
        return (bool)in_array($size,['normal','compact']);
    }


    protected static function checktype($type) {
        return (bool)in_array($type,['image','audio']);
    }


    function theme($theme) {
        if (!self::checktheme($theme))
            abort("Theme '%s' is not valid. Please use one of [%s]",[$theme,join(', ',['light','dark'])]);
        $this->theme=(string)$theme;
        return $this;
    }


    function size($size) {
        if (!self::checksize($size))
            abort("Size '%s' is not valid. Please use one of [%s]",[$size,join(', ',['normal','compact'])]);
        $this->size=(string)$size;
        return $this;
    }


    function type($type) {
        if (!self::checksize($type))
            abort("Type '%s' is not valid. Please use one of [%s]",[$type,join(', ',['image','audio'])]);
        $this->type=(string)$type;
        return $this;
    }


    function tabindex($tabindex) {
        if (!is_numeric($tabindex))
            abort("Tab index of '%s' is not valid.",[$tabindex]);
        $this->tabindex=(int)$tabindex;
        return $this;
    }
}



class Response {
    protected $isvalid;
    protected $error;


    function validity($flag) {
        $this->isvalid=(bool)$flag;
        return $this;
    }


    function isvalid() {
        return (bool)$this->isvalid;
    }


    function seterror($error) {
        $this->error=(string)$error;
        return $this;
    }


    function geterror() {
        return $this->error;
    }
}
