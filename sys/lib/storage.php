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


class Storage {
    protected $error='';

    /**
    *   Konstruktor kelas
    */
    function __construct() {}

    /**
    *   Cek apakah path writable
    *   @param $path string
    */
    function writable($path) {
        $path=str_replace(['/','\\'],DS,$path);
        if (is_dir($path)) {
            if ($path[strlen($path)-1]!=DS)
                $path=$path.DS;
            $file=$path.uniqid(mt_rand()).'.tmp';
            $handle=@fopen($file,'a');
            if ($handle===false)
                return false;
            fclose($handle);
            unlink($file);
            return true;
        }
        else {
            if (!file_exists($path))
                return false;
            $handle=@fopen($path,'w');
            if ($handle===false)
                return false;
            fclose($handle);
            return true;
        }
    }

    /**
    *   Cek apakah path readable
    *   @param $path string
    */
    function readable($path) {
        $path=str_replace(['/','\\'],DS,$path);
        if (is_dir($path)) {
            $handle=@opendir($path);
            if ($handle===false)
                return false;
            closedir($handle);
            return true;
        }
        else {
            if (!file_exists($path))
                return false;
            $handle=@fopen($path,'r');
            if ($handle===false)
                return false;
            fclose($handle);
            return true;
        }
    }

    /**
    *   Baca isi file
    *   @param $file string
    *   @param $lf bool
    */
    function read($file,$lf=false) {
        $out=@file_get_contents($file);
        return $lf?preg_replace('/\r\n|\r/',"\n",$out):$out;
    }

    /**
    *   Tulis data ke file
    *   @param $file string
    *   @param $data string
    *   @param $append bool
    */
    function write($file,$data,$append=false) {
        return file_put_contents($file,$data,LOCK_EX|($append?FILE_APPEND:0));
    }

    /**
    *   Buat direktori
    *   @param $path string
    *   @param $chmod int
    */
    function makedir($path,$chmod=0777) {
        $path=str_replace(['/','\\'],DS,$path);
        $old=umask(0);
        if (!mkdir($path,$chmod,true))
            return false;
        umask($old);
        return true;
    }

    /**
    *   Hapus direktori
    *   @param $path string
    */
    function deldir($path) {
        $path=str_replace(['/','\\'],DS,$path);
        if (is_dir($path)) {
            if ($path[strlen($path)-1]!=DS)
                $path=$path.DS;
            $handle=@opendir($path);
            if ($handle===false)
                return false;
            while (false!==($f=readdir($handle))) {
                if ($f=="."||$f=="..")
                    continue;
                $file=$path.$f;
                if (is_dir($file))
                    $this->deldir($file);
                else $this->delfile($file);
            }
            closedir($handle);
            $result=rmdir($path);
            clearstatcache();
            return $result;
        }
        return false;
    }

    /**
    *   Baca isi direktori
    *   @param $path string
    *   @param $detail bool
    *   @param $recursive bool
    */
    function catdir($path,$detail=false,$recursive=false) {
        $path=str_replace(['/','\\'],DS,$path);
        if (is_dir($path)) {
            if ($path[strlen($path)-1]!=DS)
                $path=$path.DS;
            $handle=@opendir($path);
            if ($handle===false)
                return false;
            $files=[];
            while (false!==($f=readdir($handle))) {
                if ($f=="."||$f=="..")
                    continue;
                if (!$detail&&!$recursive) {
                    $files[]=$f;
                    continue;
                }
                $file=$path.$f;
                if (is_dir($file)) {
                    $info=[
                        'type'=>'dir',
                        'type'=>$f,
                        'path'=>$path
                    ];
                    if ($recursive)
                        $info['file_list']=$this->catdir($file,$detail,$recursive-1);
                }
                else $info=[
                    'type'=>'file',
                    'type'=>$f,
                    'path'=>$path
                ];
                if ($detail) {
                    $stat=@stat($file);
                    if (is_array($stat)) {
                        if ($info['type']=='file')
                            $info['size']=$stat['size'];
                        $info['modified']=$stat['mtime'];
                        $info['accessed']=$stat['atime'];
                    }
                    else {
                        if ($info['type']=='file')
                            $info['size']=@filesize($path.$f);
                        $info['modified']=filemtime($path.$f);
                        $info['accessed']=fileatime($path.$f);
                    }
                }
                $files[]=$info;
            }
            closedir($handle);
            return $files;
        }
        return false;
    }

    /**
    *   Buat file
    *   @param $file string
    *   @param $content string
    */
    function makefile($file,$content=null) {
        $file=str_replace(['/','\\'],DS,$file);
        if (touch($file)) {
            if (is_array($content))
                $content=serialize($content);
            if (!empty($content))
                return file_put_contents($file,$content);
            return true;
        }
        return false;
    }

    /**
    *   Hapus file
    *   @param $file string
    */
    function delfile($file) {
        $file=str_replace(['/','\\'],DS,$file);
        if (@unlink($file))
            return true;
        return false;
    }

    /**
    *   List file di direktori
    *   @param $path string
    */
    function listfile($path) {
        $path=str_replace(['/','\\'],DS,$path);
        if (is_dir($path)) {
            if ($path[strlen($path)-1]!=DS)
                $path=$path.DS;
            $handle=@opendir($path);
            if ($handle===false)
                return false;
            $files=[];
            while (false!==($f=readdir($handle))) {
                if ($f=="."||$f=="..")
                    continue;
                $file=$path.$f;
                if (!is_dir($file))
                    $files[]=$f;
            }
            closedir($handle);
            return $files;
        }
        return false;
    }

    /**
    *   List sub-direktori di direktori
    *   @param $path string
    *   @param $recursive bool
    */
    function listdir($path,$recursive=false) {
        $path=str_replace(['/','\\'],DS,$path);
        if (is_dir($path)) {
            if ($path[strlen($path)-1]!=DS)
                $path=$path.DS;
            $handle=@opendir($path);
            if ($handle===false)
                return false;
            $dirs=[];
            while (false!==($f=readdir($handle))) {
                if ($f=="."||$f=="..")
                    continue;
                $file=$path.$f;
                if (is_dir($file)) {
                    if ($recursive)
                        $dirs[]=[
                            'type'=>$f,
                            'subdirs'=>$this->listdir($file,$recursive-1)
                        ];
                    else $dirs[]=$f;
                }
            }
            closedir($handle);
            return $dirs;
        }
        return false;
    }

    /**
    *   Baca ukuran file/direktori
    *   @param $path string
    *   @param $humanize bool
    */
    function size($path,$humanize=false) {
        $path=str_replace(['/','\\'],DS,$path);
        $total=0;
        if (is_dir($path)) {
            if ($path[strlen($path)-1]!=DS)
                $path=$path.DS;
            $handle=@opendir($path);
            if ($handle===false)
                return false;
            $dirs=[];
            while (false!==($f=readdir($handle))) {
                if ($f=="."||$f=="..")
                    continue;
                $file=$path.$f;
                $total+=$this->size($file,false);
            }
            closedir($handle);
            return ($humanize==true)?$this->humanize($total):$total;
        }
        else {
            if (substr(strtolower(PHP_OS),0,3)==='win')
                $total=exec("for %v in (\"".$path."\") do @echo %~zv");
            else $total=exec("perl -e 'printf \"%d\n\",(stat(shift))[7];' ".$path);
            if ($total=='0')
                $total=@filesize($path);
            return ($humanize==true)?$this->humanize($total):$total;
        }
    }

    /**
    *   Salin file/direktori
    *   @param $from string
    *   @param $to string
    */
    function duplicate($from,$to) {
        $from=str_replace(['/','\\'],DS,$from);
        $to=str_replace(['/','\\'],DS,$to);
        if (!file_exists($from))
            return false;
        return copy($from,$to);
    }

    /**
    *   Ganti nama file/direktori
    *   @param $from string
    *   @param $to string
    */
    function renames($from,$to) {
        $from=str_replace(['/','\\'],DS,$from);
        $to=str_replace(['/','\\'],DS,$to);
        if (!file_exists($from))
            return false;
        return rename($from,$to);
    }

    /**
    *   Hapus pintar file/direktori
    *   @param $path string
    *   @param $files array
    */
    function delete($path,$files=[]) {
        $path=str_replace(['/','\\'],DS,$path);
        if (is_dir($path)) {
            if ($path[strlen($path)-1]!=DS)
                $path=$path.DS;
            if (!empty($files)) {
                foreach ($files as $file)
                    if (!$this->delete($path.$file))
                        return false;
                return true;
            }
            else return $this->deldir($path);
        }
        else return $this->delfile($path);
    }

    /**
    *   Get pesan error operasi
    */
    function errstr() {
        return $this->error;
    }

    /**
    *   Ubah ukuran file ke format yang mudah dibaca
    *   @param $size int
    */
    protected function humanize($size) {
        for ($i=0;$size>=1024&&$i<4;$i++)
            $size/=1024;
        return round($size,2).[' B',' KB',' MB',' GB',' TB'][$i];
    }
}
