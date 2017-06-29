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


class Model {

    /**
    * Konstruktor kelas
    * Ini adalah core model milik Ping Framework
    */
    function __construct() {
        $this->load=summon('loader');
    }
}
