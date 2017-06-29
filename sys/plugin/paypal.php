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


class Paypal {
    private $evaluate=false;
    private $file;

    /**
    *   Konstruktor kelas
    */
    function __construct() {}

    /**
    *   Set mode request
    *   @param $value bool
    */
    function mode($evaluation=false) {
        $this->evaluate=$evaluation;
    }

    /**
    *   Set url berdasarkan mode()
    */
    function address() {
        if ($this->evaluate==true)
            return 'www.sandbox.paypal.com';
        else return 'www.paypal.com';
    }

    /**
    *   Set file untuk logging
    *   @param $file string
    */
    function logfile($file) {
        $this->file=BASE.str_replace('/',DS,ltrim($file,'/'));
    }

    /**
    *   Catat hasil request ke file
    *   @param $file string
    */
    private function annotate($message) {
        if ($this->file!=null) {
            $file=fopen($this->file,'a');
            fwrite($file,date('d-m-Y H:i:s')." : ".$message."<br>\n");
            fclose($file);
        }
    }

    /**
    *   Cek status pembayaran
    */
    function payment() {
        $req='cmd=_notify-validate';
        foreach ($_POST as $key=>$value) {
            $value=urlencode(stripslashes($value));
            $req.="&$key=$value";
        }
        $header.="POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header.="Content-Type: application/x-www-form-urlencoded\r\n";
        $header.="Content-Length: ".strlen($req)."\r\n\r\n";
        $handle=fsockopen('ssl://'.$this->address(),443,$errno,$errstr,30);
        if (!$handle)
            return false;
        else {
            fputs($handle,$header.$req);
            $loop=false;
            while (!feof($handle)) {
                if (strcmp(@fgets($handle,1024),'VERIFIED')==0)
                    $loop=true;
            }
            if ($loop==true)
                return true;
            else {
                if ($this->file!=null) {
                    $err=[];
                    $err[]='--- BEGIN TRANSACTION ---';
                    foreach ($_POST as $var)
                        $err[]=$var;
                    $err[]='--- END TRANSACTION ---';
                    $err[]='';
                    foreach ($err as $msg)
                        $this->annotate($msg);
                }
                return false;
            }
            fclose($handle);
        }
        return false;
    }

    /**
    *   Cek tipe kartu kredit
    *   @param $number string
    */
    function card($number) {
        $number=preg_replace('/[^\d]/','',$number);
        if ($this->mod10($number)) {
            if (preg_match('/^3[47][0-9]{13}$/',$number))
                return 'American Express';
            if (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
                return 'Diners Club';
            if (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
                return 'Discover';
            if (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
                return 'JCB';
            if (preg_match('/^5[1-5][0-9]{14}$/',$number))
                return 'MasterCard';
            if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
                return 'Visa';
        }
        return false;
    }

    /**
    *   Cek apakah nomor sesuai dengancek digit Mod-10 (Luhn)
    *   @param $number string
    */
    function mod10($number) {
        if (!ctype_digit($number))
            return false;
        $number=strrev($number);
        $sum=0;
        for ($i=0,$l=strlen($number);$i<$l;$i++)
            $sum+=$number[$i]+$i%2*(($number[$i]>4)*-4+$number[$i]%5);
        return !($sum%10);
    }
}
