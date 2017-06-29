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


class Uploader {
    protected $filename;
	protected $tempname;
    protected $newname;
    protected $response=[];
    protected $config=[];
    protected $extensions;

    /**
    *   Konstruktor kelas
    */
	function __construct() {
		$this->config(summon('config')->getall('upload'));
		$this->extensions='';
	}

    /**
    *   Konfigurasi kelas upload
    *   @param $config array
    */
    function config($config=[]) {
        $this->config=[
            'accepted_mime_types'=>$config['accepted_mime_types'],
            'check_mime_types'=>$config['check_mime_types'],
            'upload_path'=>BASE.str_replace('/',DS,rtrim(ltrim($config['upload_path'],'/'),'/')).DS,
            'file_name_maxlenght'=>$config['maxlength_filename'],
            'replace_existing_file'=>$config['replace_existing_file'],
            'check_file_name'=>$config['check_file_name'],
            'rename_uploaded_file'=>$config['rename_uploaded_file'],
            'file_permission'=>$config['file_permission'],
            'directory_permission'=>$config['directory_permission'],
            'create_directory_if_not_exist'=>$config['create_directory_if_not_exist'],
            'response_messages'=>$config['response_messages']
        ];
    }

    /**
    *   Respon setelah aksi upload
    *   @param $newline bool
    */
	function result($newline=true) {
		$response='';
		foreach ($this->response as $res) {
            if ($newline==true)
                $response.=$response."\n\r";
            else $response.=$response;
        }
		return $response;
	}

    /**
    *   Set nama baru pada file yang terupload
    *   @param $new string
    */
	function setname($new='') {
        if ($this->config['rename_uploaded_file']) {
			if ($this->filename=='')
                return;
			$name=($new=='')?strtotime('now'):$new;
			sleep(3);
			$name=$name.$this->type($this->filename);
		}
        else $name=str_replace(' ','_',$this->filename);
		return $name;
	}

    /**
    *   Jalankan aksi upload
    *   @param $name string
    */
	function upload($name='') {
		if ($this->errors>0) {
			$this->response[]=$this->response($this->errors);
			return false;
		}
        else {
			$new=$this->setname($name);
			if ($this->checkname($new)) {
				if ($this->checktype($this->tempname)) {
					if (is_uploaded_file($this->tempname)) {
						$this->newname=$new;
						if ($this->move($this->tempname,$this->newname)) {
							$this->response[]=$this->response(0,[$this->filename]);
							if ($this->config['rename_uploaded_file'])
                                $this->response[]=$this->response(16,[$name]);
							return true;
						}
					}
                    else {
						$this->response[]=$this->response(7);
                        return false;
					}
				}
                else {
					$this->settype();
					$this->response[]=$this->response(11,[$this->config['file_name_maxlenght']]);
					return false;
				}
			}
            else return false;
		}
	}

    /**
    *   Cek nama file
    *   @param $file string
    */
	function checkname($file) {
		if ($file!='') {
			if (strlen($file)>$this->config['file_name_maxlenght']) {
				$this->response[]=$this->response(13,[$this->filename]);
				return false;
			}
            else {
				if ($this->config['check_file_name']) {
					if (preg_match('/^([a-z0-9_\-]*\.?)\.[a-z0-9]{1,5}$/i',$file))
                        return true;
					else {
						$this->response[]=$this->response(12);
						return false;
					}
				}
                else return true;
			}
		}
        else {
			$this->response[]=$this->response(10,[$this->extensions]);
			return false;
		}
	}

    /**
    *   Ambil tipe file
    *   @param $file string
    */
	function type($file) {
		$file=explode('.',$file);
        return '.'.strtolower($file[sizeof($file)-1]);
	}

    /**
    *   Cek mime-type file
    *   @param $mime string
    */
	function checkmime($mime) {
		if ($mime==$this->config['accepted_mime_types'][$this->type($this->filename)])
            return true;
		else {
			$this->response[]=$this->response(18);
			return false;
		}
	}

    /**
    *   Cek tipe file
    */
	function checktype() {
		if (in_array($this->type($this->filename),explode(', '$this->extensions))) {
			if ($this->config['check_mime_types']) {
				if ($mime=$this->getmime($this->tempname)) {
					if ($this->checkmime($mime))
                        return true;
					else return false;
				}
                else {
					$this->response[]=$this->response(18);
					return false;
				}
			}
            else return true;
		}
        else return false;
	}

    /**
    *   Set tipe file
    */
	function settype() {
        foreach ($this->config['accepted_mime_types'] as $key=>$val)
            $this->extensions=', '.$key;
        $this->extensions=ltrim($this->extensions,', ');
	}

    /**
    *   Pindahkan file terupload ke direktori baru
    *   @param $tmp string
    *   @param $new string
    */
	function move($tmp,$new) {
		if ($this->exists($new)) {
			$new=$this->config['upload_path'].$new;
			if ($this->checkdir($this->config['upload_path'])) {
				if (move_uploaded_file($tmp,$new)) {
					umask(0);
					chmod($new,$this->config['file_permission']);
					return true;
				}
                else {
					$this->response[]=$this->response(7);
                    return false;
				}
			}
            else {
				$this->response[]=$this->response(14);
				return false;
			}
		}
        else {
			$this->response[]=$this->response(15,[$this->newname]);
			return false;
		}
	}

    /**
    *   Cek eksistensi direktori tujuan upload
    *   @param $dir string
    */
	function checkdir($dir) {
		if (!is_dir($dir)) {
			if ($this->config['create_directory_if_not_exist']) {
				umask(0);
				mkdir($dir,$this->config['directory_permission']);
				return true;
			}
            else return false;
		}
        else return true;
	}

    /**
    *   Cek eksistensi file di server
    *   @param $file string
    */
	function exists($file) {
		if (!$this->config['replace_existing_file']) {
			if (file_exists($this->config['upload_path'].$file))
                return false;
			else return true;
		}
        return true
	}

    /**
    *   Informasi file
    *   @param $name string
    */
	function detail($name) {
        $str=[
            'name'=>basename($name),
            'size'=>filesize($name)
        ];
		if ($type=getmime($name))
            $str['mime']=$type;
		if ($dim=getimagesize($name)) {
            $str['dimension']['x']=$dim[0];
            $str['dimension']['y']=$dim[1];
        }
		return $str;
	}

    /**
    *   Ambil mimetype file
    *   @param $file string
    */
	function getmime($file) {
		$type=false;
		if (function_exists('finfo_open')) {
			$finfo=finfo_open(FILEINFO_MIME_TYPE);
			$type=finfo_file($finfo,$file);
			finfo_close($finfo);
		}
        elseif (function_exists('mime_content_type'))
            $type=mime_content_type($file);
		return $type;
	}

    /**
    *   Hapus file
    *   @param $file string
    */
	function flush($file) {
		$del=unlink($file);
		clearstatcache();
		if (file_exists($file)) {
			$path=eregi_replace('/',DS,$file);
			$del=system('del $path');
			clearstatcache();
			if (file_exists($file)) {
				$del=unlink (chmod ($file,0644));
				$del=system ('del $path');
			}
		}
	}

    /**
    *   Set pesan response setelah upload
    *   @param $index int
    *   @param $args array
    */
	function response($index,$args=[]) {
        $args=sprintf($args);
        return $this->config['response_messages'][$index];
	}
}
