<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


define('DS',DIRECTORY_SEPARATOR);
define('BASE',realpath(dirname(__FILE__)).DS);
define('APP',BASE.'app'.DS);
define('SYS',BASE.'sys'.DS);
define('RES',BASE.'res'.DS);
define('CONFIG',APP.'config'.DS);

require (SYS.'core'.DS.'core.php');
require (SYS.'core'.DS.'base.php');

summon('benchmark')->start('system');
summon('ping')->run();
