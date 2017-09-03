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


class Cache {
    protected $path;

    /**
    *   Konstruktor kelas
    */
    function __construct() {
        $this->path=RES.'cache';
    }

    /**
    *   Simpan cache ke file
    *   @param $id string
    *   @param $content int|string
    *   @param $expiry int
    */
    function write($id,$content,$expiry=86400) {
        $id=$this->path.DS.$id.'.cache';
        $data=['expiry'=>(time()+$expiry),'data'=>$content];
        if (file_put_contents($id,serialize($data))) {
            @chmod($id,0666);
            return true;
        }
        return false;
    }

    /**
    *   Baca value cache
    *   @param $id string
    */
    function read($id) {
        $id=$this->path.DS.$id.'.cache';
        if (file_exists($id)) {
            $data=unserialize(file_get_contents($id));
            if ($data['expiry']<time()) {
                unlink($id);
                return false;
            }
            return $data['data'];
        }
        return false;
    }

    /**
    *   Hapus cache
    *   @param $id string
    */
    function delete($id) {
        $id=$this->path.DS.$id.'.cache';
        if (file_exists($id)) {
            unlink($id);
            return true;
        }
        return false;
    }

    /**
    *   Lihat waktu kadaluwarsa cache
    *   @param $id string
    */
    function expiry($id) {
        $file=$this->path.DS.$id.'.cache';
        if (file_exists($file)) {
            $data=unserialize(file_get_contents($file));
            return $data['expiry'];
        }
        return false;
    }

    /**
    *   Kosongkan direktori cache
    */
    function cleanup() {
        foreach (scandir($this->path) as $file) {
            $file=$this->path.DS.$file;
            if (!in_array($file,['.','..','index.html']))
                @unlink($file);
        }
        return true;
    }
}
