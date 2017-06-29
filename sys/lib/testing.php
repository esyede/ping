<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Lib;
defined('BASE') or exit('Access Denied!');


class Testing {
    const C_False=0;
    const C_True=1;
    const C_Both=2;
    protected $data=[];
    protected $level=0;
    protected $passed=true;

    /**
    *   Get hasil unit testing
    */
    function result() {
        return $this->data;
    }

    /**
    *   Lolos test?
    */
    function passed() {
        return $this->passed;
    }

    /**
    *   Evaluasi kondisi testing
    *   @param $condition mixed
    *   @param $text string
    */
    function expect($condition,$text=null) {
        $result=(bool)$condition;
        if ($this->level==$result||$this->level==self::C_Both) {
            $data=[
                'status'=>$result,
                'text'=>$text,
                'source'=>null
            ];
            foreach (debug_backtrace() as $data) {
                if (isset($data['file'])) {
                    $data['source']=($data['file']?strtr($data['file'],DS,'/'):$data['file']).':'.$data['line'];
                    break;
                }
            }
            $this->data[]=$data;
        }
        if (!$result&&$this->passed)
            $this->passed=false;
        return $this;
    }

    /**
    *   Tambahkan psean ke testing
    *   @param $condition mixed
    *   @param $text string
    */
    function message($text) {
        $this->expect(true,$text);
    }

    /**
    *   Konstruktor kelas
    */
    function __construct($level=self::C_Both) {
        $this->level=$level;
    }
}
