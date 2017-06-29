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


class Matrix {

    /**
    *   Ambil value dari array multi-dimensi
    *   @param $arr array
    *   @param $col mixed
    */
    function pick(array $var,$col) {
        return array_map(function ($row) use ($col) {
            return $row[$col];
        },$var);
    }

    /**
    *   Putar sebuah variabel array dua-dimensi
    *   @param $var array
    */
    function transpose(array &$var) {
        $out=[];
        foreach ($var as $keyx=>$cols)
            foreach ($cols as $keyy=>$valy)
                $out[$keyy][$keyx]=$valy;
        $var=$out;
    }

    /**
    *   Sortir array multi-dimensi berdasarkan kolom
    *   @param $var array
    *   @param $col mixed
    *   @param $order int
    */
    function sort(array &$var,$col,$order=SORT_ASC) {
        uasort($var,function ($val1,$val2) use ($col,$order) {
            list ($v1,$v2)=[$val1[$col],$val2[$col]];
            $out=is_numeric($v1)&&is_numeric($v2)?$this->sign($v1-$v2):strcmp($v1,$v2);
            if ($order==SORT_DESC)
                $out=-$out;
            return $out;
        });
        $var=array_values($var);
    }

    /**
    *   Ubah key dari elemen array 2 dimensi
    *   @param $var array
    *   @param $old string
    *   @param $new string
    */
    function changekey(array &$var,$old,$new) {
        $keys=array_keys($var);
        $vals=array_values($var);
        $keys[array_search($old,$keys)]=$new;
        $var=array_combine($keys,$vals);
    }

    /**
    *   Buat kalender berdasarkan tanggal,
    *   dengan opsi pengaturan permulaan hari (0 untuk minggu)
    *   @param $date string
    *   @param $first int
    */
    function calendar($date='now',$first=0) {
        $out=false;
        if (extension_loaded('calendar')) {
            $parts=getdate(strtotime($date));
            $days=cal_days_in_month(CAL_GREGORIAN,$parts['mon'],$parts['year']);
            $ref=date('w',strtotime(date('Y-m',$parts[0]).'-01'))+(7-$first)%7;
            $out=[];
            for ($i=0;$i<$days;$i++)
                $out[floor(($ref+$i)/7)][($ref+$i)%7]=$i+1;
        }
        return $out;
    }

    /**
    *   Cek apakah angka adalah negatif (-1), nol (0), atau positif (1)
    *   @param $num mixed
    */
    function sign($num) {
        return $num?($num/abs($num)):0;
    }
}
