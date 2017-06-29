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


class Event {
    protected static $event=[];

    /**
    *   Trigger event
    *   @param $event string
    *   @param $args array
    */
    function trigger($event,$args=[]) {
        if (array_key_exists($event,self::$event)) {
            foreach (self::$event[$event] as $callback) {
                if (is_array($callback)) {
                    list ($controller,$action)=$callback;
                    switch (count($args)) {
                        case 0:
                            $controller->{$action}();
                            break;
                        case 1:
                            $controller->{$action}($args[0]);
                            break;
                        case 2:
                            $controller->{$action}($args[0],$args[1]);
                            break;
                        case 3:
                            $controller->{$action}($args[0],$args[1],$args[2]);
                            break;
                        case 4:
                            $controller->{$action}($args[0],$args[1],$args[2],$args[3]);
                            break;
                        case 5:
                            $controller->{$action}($args[0],$args[1],$args[2],$args[3],$args[4]);
                            break;
                        default:
                            call_user_func_array([$controller,$action],$args);
                            break;
                    }
                }
                else {
                    switch (count($args)) {
                        case 0:
                            $callback();
                            break;
                        case 1:
                            $callback($args[0]);
                            break;
                        case 2:
                            $callback($args[0],$args[1]);
                            break;
                        case 3:
                            $callback($args[0],$args[1],$args[2]);
                            break;
                        case 4:
                            $callback($args[0],$args[1],$args[2],$args[3]);
                            break;
                        case 5:
                            $callback($args[0],$args[1],$args[2],$args[3],$args[4]);
                            break;
                        default:
                            call_user_func_array($callback,$args);
                            break;
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
    *   Register event dengan callback
    *   @param $event string
    *   @param $callback mixed
    */
    function register($event,$callback) {
        self::$event[$event][]=$callback;
    }
}
