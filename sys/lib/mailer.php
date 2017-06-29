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


class Mailer {
    protected $wrap=78;
    protected $to=[];
    protected $subject;
    protected $message;
    protected $headers=[];
    protected $args;
    protected $files=[];
    protected $uid;

    /**
    *   Konstruktor kelas
    */
    function __construct() {
        $this->reset();
    }

    /**
    *   Set penerima email
    *   @param $email string
    *   @param $name string
    */
    function to($email,$name) {
        $this->to[]=$this->build((string)$email,(string)$name);
        return $this;
    }

    /**
    *   Ambil penerima email
    */
    function _to() {
        return $this->to;
    }

    /**
    *   Set subject email
    *   @param $subject string
    */
    function subject($str) {
        $this->subject=$this->utf8(filter_var((string)$subject,FILTER_UNSAFE_RAW,FILTER_FLAG_STRIP_LOW));
        return $this;
    }

    /**
    *   Get subject email
    */
    function _subject() {
        return $this->subject;
    }

    /**
    *   Set isi pesan email
    *   @param $msg string
    */
    function message($msg) {
        $this->message=str_replace("\n.","\n..",(string)$msg);
        return $this;
    }

    /**
    *   Ambil isi pesan email
    */
    function _message() {
        return $this->message;
    }

    /**
    *   Lampirkan file pada email
    *   @param $path string
    *   @param $fname string|null
    */
    function attach($path,$fname=null) {
        $this->files[]=[
            'path'=>$path,
            'file'=>(empty($fname)?basename($path):$fname),
            'data'=>$this->_attach($path)
        ];
        return $this;
    }

    /**
    *   Ambil lampiran file
    */
    function _attach($path) {
        $handle=fopen($path,'r');
        $attach=fread($handle,filesize($path));
        fclose($handle);
        return chunk_split(base64_encode($attach));
    }

    /**
    *   Set pengirim email
    *   @param $email string
    *   @param $name string
    */
    function from($email,$name) {
        $this->header('From',(string)$email,(string)$name);
        return $this;
    }

    /**
    *   Bangun header email
    *   @param $header string
    *   @param $email string|null
    *   @param $name string|null
    */
    function header($header,$email=null,$name=null) {
        $this->headers[]=sprintf('%s: %s',(string)$header,$this->build((string)$email,(string)$name));
        return $this;
    }

    /**
    *   Bangun generic header
    *   @param $header string
    *   @param $val string|null
    */
    function generic($header,$val) {
        $this->headers[]=sprintf('%s: %s',(string)$header,(string)$val);
        return $this;
    }

    /**
    *   Ambil header email
    */
    function _header() {
        return $this->headers;
    }

    /**
    *   Set argumen tambahan pada email
    *   @param $args string
    */
    function args($args) {
        $this->args=(string)$args;
        return $this;
    }

    /**
    *   Ambil argumen tambahan
    */
    function _args() {
        return $this->args;
    }

    /**
    *   Set panjang wrapper
    *   @param $wrap int
    */
    function wrap($wrap=78) {
        $wrap=(int)$wrap;
        if ($wrap<1)
            $wrap=78;
        $this->wrap=$wrap;
        return $this;
    }

    /**
    *   Ambil panjang wrapper
    */
    function _wrap() {
        return $this->wrap;
    }

    /**
    *   Cek apakah ada lampiran file pada email
    */
    function hasfile() {
        return !empty($this->files);
    }

    /**
    *   Susun array header email
    */
    function heads() {
        $head=[];
        $head[]="MIME-Version: 1.0";
        $head[]="Content-Type: multipart/mixed; boundary=\"{$this->uid}\"";
        return join(PHP_EOL,$head);
    }

    /**
    *   Susun array tubuh email
    */
    function bodies() {
        $body=[];
        $body[]="This is a multi-part message in MIME format.";
        $body[]="--{$this->uid}";
        $body[]="Content-type:text/html; charset=\"utf-8\"";
        $body[]="Content-Transfer-Encoding: 7bit";
        $body[]="";
        $body[]=$this->message;
        $body[]="";
        $body[]="--{$this->uid}";
        foreach ($this->files as $attach) $body[]=$this->_mime($attach);
        return implode(PHP_EOL,$body);
    }

    /**
    *   Susun array info mimetype pada header
    */
    function _mime($attach) {
        $file=$attach['file'];
        $data=$attach['data'];
        $head=[];
        $head[]="Content-Type: application/octet-stream; name=\"{$file}\"";
        $head[]="Content-Transfer-Encoding: base64";
        $head[]="Content-Disposition: attachment; filename=\"{$file}\"";
        $head[]="";
        $head[]=$data;
        $head[]="";
        $head[]="--{$this->uid}";
        return implode(PHP_EOL,$head);
    }

    /**
    *   Kirim email
    */
    function send() {
        $to=$this->_tosend();
        $headers=$this->_join();
        if (empty($to))
            abort('Unable to send, no recipient address has been set.');
        if ($this->hasfile()) {
            $msg=$this->bodies();
            $headers.=PHP_EOL.$this->heads();
        }
        else $msg=wordwrap($this->message,$this->wrap);
        return mail($to,$this->subject,$msg,$headers,$this->args);
    }

    /**
    *   Aktifkan fungsi debugger
    */
    function debug() {
        return '<pre>'.print_r($this,true).'</pre>';
    }

    /**
    *   Bangun header dan tubuh email
    */
    function build($email,$name=null) {
        $email=filter_var(
            strtr($email,["\r"=>'',"\n"=>'',"\t"=>'','"'=>'',','=>'','<'=>'','>'=>'']),
            FILTER_SANITIZE_EMAIL
        );
        if (empty($name))
            return $email;
        $name=$this->utf8(trim(strtr(
            filter_var($name,FILTER_SANITIZE_STRING,FILTER_FLAG_NO_ENCODE_QUOTES),
            ["\r"=>'',"\n"=>'',"\t"=>'','"'=>"'",'<'=>'[','>'=>']']
        )));
        return sprintf('"%s" <%s>',$name,$email);
    }

    /**
    *   Ubah karakter non-UTF8 ke UTF8
    *   @param $val string
    */
    function utf8($val) {
        $val=trim($val);
        if (preg_match('/(\s)/',$val))
            return $this->scrub($val);
        return $this->utf8word($val);
    }

    /**
    *   Ubah kata non-UTF8 ke UTF8
    *   @param $val string
    */
    function utf8word($val) {
        return sprintf('=?UTF-8?B?%s?=',base64_encode($val));
    }

    /**
    *   Bersihkan karakter non-UTF8
    *   @param $val string
    */
    function scrub($val) {
        $enc=[];
        foreach (explode(' ',$val) as $word)
            $enc[]=$this->utf8word($word);
        return join($this->utf8word(' '),$enc);
    }

    /**
    *   @param $data string
    *   Filter variabel dari karakter berbahaya
    */
    function _eval($data) {
        return filter_var($data,FILTER_UNSAFE_RAW,FILTER_FLAG_STRIP_LOW);
    }

    /**
    *   Gabungkan array header
    */
    function _join() {
        if (empty($this->headers))
            return;
        return join(PHP_EOL,$this->headers);
    }

    /**
    *   Ambil alamat tujuan email
    */
    function _tosend() {
        if (empty($this->to))
            return;
        return join(', ',$this->to);
    }

    /**
    *   Reset property kelas
    */
    function reset() {
        $this->to=[];
        $this->headers=[];
        $this->subject=null;
        $this->message=null;
        $this->wrap=78;
        $this->args=null;
        $this->files=[];
        $this->uid=md5(uniqid(time()));
        return $this;
    }

    /**
    *   Magic method untuk fungsi debug()
    */
    function __toString() {
        return print_r($this,true);
    }
}
