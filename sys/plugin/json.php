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


class Json {
    private $json='';

    /**
    *   Encode array atau objek ke json
    *   @param $object mixed
    */
    function encode($object) {
        if ($this->error()) {
            $this->json=json_encode($object);
            return $this->json;
        }
    }

    /**
    *   Decode string json
    *   @param $json mixed
    */
    function decode($json) {
        if ($this->error())
            return json_decode($json);
    }

    /**
    *   Print hasil encode
    */
    function draw() {
        print $this->json;
        return $this->json;
    }

    /**
    *   Cek error json
    */
    private function error() {
        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                abort('The maximum stack depth has been exceeded');
            case JSON_ERROR_STATE_MISMATCH:
                abort('Invalid JSON string');
            case JSON_ERROR_CTRL_CHAR:
                abort('Control character error, possibly incorrectly encoded');
            case JSON_ERROR_SYNTAX:
                abort('Syntax error or invalid JSON string');
            case JSON_ERROR_UTF8:
                abort('Malformed UTF-8 characters, possibly incorrectly encoded');
            case JSON_ERROR_RECURSION:
                abort('One or more recursive references in the value to be encoded');
            case JSON_ERROR_UNSUPPORTED_TYPE:
                abort('A value of a type that cannot be encoded was given');
        }
        return json_last_error()===JSON_ERROR_NONE;
    }
}
