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


class Image {
    const MODE_DEBUG=0;
    const MODE_NORMAL=1;
    const MODE_PERFORMANCE=2;
    public $cachemode;
    public $default;
    public $cachepath;
    private $fname=null;
    private $transparency=100;
    private $type;
    private $origwidth;
    private $origheight;
    private $reswidth;
    private $resheight;
    private $target=null;
    private $iscached=null;
    private $quality=90;
    private $ratio=true;
    private $fit=false;
    private $xfit=0;
    private $yfit=0;
    private $xoffset=0;
    private $yoffset=0;
    private $image;
    private $fname;
    protected $ds;
    protected $maskwidth=null;
    protected $maskheight=null;
    protected $isresized=false;


    function __construct() {
        $this->ds='/';
        $this->default='error.jpg';
        $this->cachepath=RES.'images';
        $this->init();
    }


    function init($file,$quality=null,$ratio=null,$base64=null) {
        $this->quality($quality);
        $this->ratio($ratio);
        $this->image=static::$default;
        if ((strncmp('tmp://',$file,6)!==0)
        &&(file_exists($file)==true)
        &&(is_file($file)==true))
            $this->image=$file;
        elseif ($base64!==null) {
            $this->image=sys_get_temp_dir().DS.$file;
            file_put_contents($this->image,base64_decode($base64));
        }
    }


    public static function create($file,$quality=null,$ratio=null) {
        return new static($file,$quality,$ratio);
    }


    private function cache($fullpath=false) {
        if ($this->target===null) {
            $file=pathinfo($this->image);
            $extend='';
            if ($this->ratio===false)
                $extend='-stretched';
            if ($this->fit===true)
                $extend='-scale';
            if ($this->fname!==null)
                $extend.='-'.pathinfo($this->fname)['filename'].'-'.$this->transparency;
            $this->target=sprintf(
                '%x-%s-%dx%d-%d%s.%s',
                crc32($file['dirname']),
                $file['filename'],
                $this->maskwidth,
                $this->maskheight,
                $this->quality,
                $extend,
                $file['extension']
            );
        }
        if ($fullpath===false)
            return $this->target;
        else return static::$cachepath.DS.$this->target;
    }


    private function iscached() {
        switch ($this->cachemode) {
            case static::MODE_DEBUG:
                $this->iscached=false;
                break;
            case static::MODE_NORMAL:
                if (($this->iscached===null)
                &&(file_exists($this->cache(true))===true)) {
                    if (filemtime($this->image)<filemtime($this->cache(true)))
                        $this->iscached=true;
                    else $this->iscached=false;
                }
                else $this->iscached=false;
                break;
            case static::MODE_PERFORMANCE:
            default:
                if ($this->iscached===null)
                    $this->iscached=file_exists($this->cache(true));
                break;
        }
        return $this->iscached;
    }


    function quality($val) {
        if (($val!==null)&&($val>=0)&&($val<=100))
            $this->quality=$val;
        $this->isresized=false;
        return $this;
    }


    function ratio($val) {
        if (($val===false)||($val===true))
            $this->ratio=$val;
        $this->isresized=false;
        return $this;
    }


    function fit($val) {
        if (($val===false)||($val===true))
            $this->fit=$val;
        $this->isresized=false;
        return $this;
    }


    function xoffset($val) {
        $this->xoffset=intval($val);
        $this->isresized=false;
        return $this;
    }


    function yoffset($val) {
        $this->yoffset=intval($val);
        $this->isresized=false;
        return $this;
    }


    function mask($file,$transp=null,$pos=null) {
        if (file_exists($file)===true)
            $this->fname=$file;
        if (($transp>0)&&($transp<=100))
            $this->transparency=$transp;
        $this->isresized=false;
        return $this;
    }


    function resize($width,$height) {
        $this->target=null;
        $this->iscached=null;
        if ($width===null)
            $this->maskwidth=0;
        else $this->maskwidth=$width;
        if ($height===null)
            $this->maskheight=0;
        else $this->maskheight=$height;
        $this->isresized=true;
        return $this;
    }


    function geturl($fullpath=true) {
        if ($this->isresized===false)
            $this->resize($this->maskwidth,$this->maskheight);
        if ($this->iscached()===false)
            $this->resample();
        return str_replace(DS,static::$ds,$this->cache($fullpath));
    }


    function render($return=false) {
        $data=false;
        if ($this->isresized===false)
            $this->resize($this->maskwidth,$this->maskheight);
        if ($this->iscached()===false)
            $this->resample();
        if (($f=fopen($this->cache(true),'rb'))!==false) {
            if ($return===false)
                $data=fpassthru($f);
            else $data=fread($f,filesize($this->cache(true)));
            fclose($f);
        }
        else abort("Can't open file '%s'",[$this->cache(true)],E_ERROR);
        return $data;
    }


    function ctype() {
        if ($this->type===null)
            list($this->origwidth,$this->origheight,$this->type)=getimagesize($this->image);
        $ctype='application/octet-stream';
        switch ($this->type) {
            case IMAGETYPE_PNG:
                $ctype='image/png';
                break;
            case IMAGETYPE_GIF:
                $ctype='image/gif';
                break;
            case IMAGETYPE_JPEG:
                $ctype='image/jpeg';
                break;
            default:
                abort("Unknown file type '%s'",[$this->type],E_ERROR);
                break;
        }
        return $ctype;
    }


    function liverender() {
        if ($this->isresized===false)
            $this->resize($this->maskwidth,$this->maskheight);
        return $this->resample(true);
    }


    function save($target) {
        if ($this->isresized===false)
            $this->resize($this->maskwidth,$this->maskheight);
        return copy($this->cache(true),$target);
    }


    private function load($file,$type) {
        $gd=null;
        switch ($type) {
            case IMAGETYPE_PNG:
                $gd=imagecreatefrompng($file);
                imagealphablending($gd,false);
                imagesavealpha($gd,true);
                break;
            case IMAGETYPE_GIF:
                $gd=imagecreatefromgif($file);
                break;
            case IMAGETYPE_JPEG:
                $gd=imagecreatefromjpeg($file);
                break;
            default:
                abort("Unknown file type '%s'",[$type],E_ERROR);
                break;
        }
        return $gd;
    }


    private function resample($return=false) {
        $this->size();
        $orig=$this->load($this->image,$this->type);
        if ($this->fit===false)
            $target=imagecreatetruecolor($this->reswidth,$this->resheight);
        else $target=imagecreatetruecolor($this->maskwidth,$this->maskheight);
        if ($this->type===IMAGETYPE_PNG) {
            imagealphablending($target,false);
            imagesavealpha($target,true);
        }
        if ($this->fit===false)
            imagecopyresampled(
                $target,
                $orig,
                0,0,0,0,
                $this->reswidth,
                $this->resheight,
                $this->origwidth,
                $this->origheight
            );
        else imagecopyresampled(
                $target,
                $orig,
                ($this->maskwidth-$this->reswidth)/2+$this->xoffset,
                ($this->maskheight-$this->resheight)/2+$this->yoffset,
                0,0,
                $this->reswidth,
                $this->resheight,
                $this->origwidth,
                $this->origheight
            );
        if ($this->fname!==null) {
            list($mwidth,$mheight,$mtype)=getimagesize($this->fname);
            $mask=$this->load($this->fname,$mtype);
            if ($this->fit===false) {
                $destx=$this->reswidth-$mwidth;
                $desty=$this->resheight-$mheight;
            }
            else {
                $destx=$this->maskwidth-$mwidth;
                $desty=$this->maskheight-$mheight;
            }
            if ($mtype===IMAGETYPE_PNG) {
                imagealphablending($target,false);
                $transp=imagecolorallocatealpha($target,0,0,0,127);
                imagefill($target,0,0,$transp);
                imagesavealpha($target,true);
                imagealphablending($target,true);
                imagecopyresampled(
                    $target,
                    $mask,
                    $destx,
                    $desty,
                    0,0,
                    $mwidth,
                    $mheight,
                    $mwidth,
                    $mheight
                );
            }
            else imagecopymerge(
                $target,
                $mask,
                $destx,
                $desty,
                0,0,
                $mwidth,
                $mheight,
                $this->transparency
            );
        }
        $cached=($return===true)?null:$this->cache(true);
        $raw=null;
        switch ($this->type) {
            case IMAGETYPE_PNG:
                $quality=(int)($this->quality/10);
                if ($return===true)
                    ob_start();
                imagepng($target,$cached,(($quality<10)?$quality:9));
                if ($return===true)
                    $raw=ob_get_clean();
                break;
            case IMAGETYPE_GIF:
                if ($return===true)
                    ob_start();
                imagegif($target,$cached);
                if ($return===true)
                    $raw=ob_get_clean();
                break;
            case IMAGETYPE_JPEG:
                if ($return===true)
                    ob_start();
                imagejpeg($target,$cached,$this->quality);
                if ($return===true)
                    $raw=ob_get_clean();
                break;
            default:
                abort("Unknown file type '%s'",[$this->type],E_ERROR);
                break;
        }
        return $raw;
    }


    private function size() {
        list($this->origwidth,$this->origheight,$this->type)=getimagesize($this->image);
        if ($this->ratio===false) {
            $this->reswidth=$this->maskwidth;
            $this->resheight=$this->maskheight;
        }
        elseif ($this->fit===false) {
            $xratio=(float)($this->maskwidth/$this->origwidth);
            $yratio=(float)($this->maskheight/$this->origheight);
            if (($xratio>0.0)&&($yratio>0.0))
                $ratio=min($xratio,$yratio);
            else {
                if (($xratio===0.0)&&($yratio===0.0))
                    $ratio=1;
                else $ratio=max($xratio,$yratio);
            }
            $this->reswidth=round($ratio*$this->origwidth);
            $this->resheight=round($ratio*$this->origheight);
        }
        else {
            $xratio=(float)($this->maskwidth/$this->origwidth);
            $yratio=(float)($this->maskheight/$this->origheight);
            if (($xratio===0.0)&&($yratio===0.0))
                $ratio=1;
            else $ratio=max($xratio,$yratio);
            $this->reswidth=round($ratio*$this->origwidth);
            $this->resheight=round($ratio*$this->origheight);
        }
    }


    function width($width) {
        $this->ratio=true;
        $this->fit=false;
        return $this->resize($width,null);
    }


    function height($height) {
        $this->ratio=true;
        $this->fit=false;
        return $this->resize(null,$height);
    }


    function __toString() {
        try {
            return $this->geturl();
        }
        catch(\Exception $e) {
            return static::$default;
        }
    }
}
