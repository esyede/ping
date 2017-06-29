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


class Paginator {
    const NUMBER='(:num)';
    protected $total;
    protected $pagenum;
    protected $perpage;
    protected $current;
    protected $pattern;
    protected $max=10;
    protected $prevtext='Prev';
    protected $nexttext='Next';

    /**
    *   Konstruktor kelas
    */
    function __construct() {}

    /**
    *   Inisialisasi paginator
    *   @param $total int
    *   @param $perpage int
    *   @param $current int
    *   @param $pattern string
    */
    function init($total,$perpage,$current,$pattern='') {
        $this->total=$total;
        $this->perpage=$perpage;
        $this->current=$current;
        $this->pattern=$pattern;
        $this->updatenum();
    }

    /**
    *   Update nomor halaman
    */
    protected function updatenum() {
        $this->pagenum=($this->perpage==0?0:(int)ceil($this->total/$this->perpage));
    }

    /**
    *   Set maksimal halaman
    *   @param $max int
    */
    function max($max) {
        if ($max<3)
            abort("Max page can't be less than 3",E_USER_ERROR);
        $this->max=$max;
    }

    /**
    *   Get maksimal halaman
    */
    function getmax() {
        return $this->max;
    }

    /**
    *   Set halaman saat ini
    *   @param $current int
    */
    function current($current) {
        $this->current=$current;
    }

    /**
    *   Get halaman saat ini
    */
    function getcurrent() {
        return $this->current;
    }

    /**
    *   Set data perhalaman
    *   @param $perpage int
    */
    function perpage($perpage) {
        $this->perpage=$perpage;
        $this->updatenum();
    }

    /**
    *   Get data perhalaman
    */
    function getperpage() {
        return $this->perpage;
    }

    /**
    *   Set total data
    *   @param $total int
    */
    function total($total) {
        $this->total=$total;
        $this->updatenum();
    }

    /**
    *   Get total data
    */
    function gettotal() {
        return $this->total;
    }

    /**
    *   Get nomor halaman
    */
    function getnum() {
        return $this->pagenum;
    }

    /**
    *   Set pola url
    *   @param $pattern string
    */
    function pattern($pattern) {
        $this->pattern=$pattern;
    }

    /**
    *   Get pola url
    */
    function getpattern() {
        return $this->pattern;
    }

    /**
    *   Get url halaman
    *   @param $pagenum int
    */
    function pageurl($pagenum) {
        return str_replace(self::NUMBER,$pagenum,$this->pattern);
    }

    /**
    *   Get halaman selanjutnya
    */
    function next() {
        if ($this->current<$this->pagenum)
            return $this->current+1;
        return null;
    }

    /**
    *   Get halaman sebelumya
    */
    function prev() {
        if ($this->current>1)
            return $this->current-1;
        return null;
    }

    /**
    *   Get url halaman selanjutnya
    */
    function nexturl() {
        if (!$this->next())
            return null;
        return $this->pageurl($this->next());
    }

    /**
    *   Get url halaman sebelumya
    */
    function prevurl() {
        if (!$this->prev())
            return null;
        return $this->pageurl($this->prev());
    }

    /**
    *   Get array data paginator
    */
    function getpage() {
        $all=[];
        if ($this->pagenum<=1)
            return [];
        if ($this->pagenum<=$this->max)
            for ($i=1;$i<=$this->pagenum;$i++)
                $all[]=$this->build($i,$i==$this->current);
        else {
            $round=(int)floor(($this->max-3)/2);
            if ($this->current+$round>$this->pagenum)
                $start=$this->pagenum-$this->max+2;
            else $start=$this->current-$round;
            if ($start<2)
                $start=2;
            $finish=$start+$this->max-3;
            if ($finish>=$this->pagenum)
                $finish=$this->pagenum-1;
            $all[]=$this->build(1,$this->current==1);
            if ($start>2)
                $all[]=$this->ellipsis();
            for ($i=$start;$i<=$finish;$i++)
                $all[]=$this->build($i,$i==$this->current);
            if ($finish<$this->pagenum-1)
                $all[]=$this->ellipsis();
            $all[]=$this->build($this->pagenum,$this->current==$this->pagenum);
        }
        return $all;
    }

    /**
    *   Bangun paginator
    *   @param $pagenum int
    *   @param $current bool
    */
    protected function build($pagenum,$current=false) {
        return [
            'num'=>$pagenum,
            'url'=>$this->pageurl($pagenum),
            'current'=>$current
        ];
    }

    /**
    *   Buat halaman elipsis
    */
    protected function ellipsis() {
        return [
            'num'=>'...',
            'url'=>null,
            'current'=>false
        ];
    }

    /**
    *   Cetak paginator ke html
    */
    function draw() {
        if ($this->pagenum<=1)
            return '';
        $html='<ul class="pagination">';
        if ($this->prevurl())
            $html.='<li><a href="'.$this->prevurl().'">&laquo; '.$this->prevtext.'</a></li>';
        foreach ($this->getpage() as $page) {
            if ($page['url'])
                $html.='<li'.($page['current']?' class="active"':'').
                    '><a href="'.$page['url'].'">'.$page['num'].'</a></li>';
            else $html.='<li class="disabled"><span>'.$page['num'].'</span></li>';
        }
        if ($this->nexturl())
            $html.='<li><a href="'.$this->nexturl().'">'.$this->nexttext.' &raquo;</a></li>';
        $html.='</ul>';
        return $html;
    }

    /**
    *   Magic function wrapper
    */
    function __toString() {
        return $this->draw();
    }

    /**
    *   Ambil item pertama di halaman ini
    */
    function first() {
        $first=($this->current-1)*$this->perpage+1;
        if ($first>$this->total)
            return null;
        return $first;
    }

    /**
    *   Ambil item terakhir di halaman ini
    */
    function last() {
        $first=$this->first();
        if ($first===null)
            return null;
        $last=$first+$this->perpage-1;
        if ($last>$this->total)
            return $this->total;
        return $last;
    }

    /**
    *   Set teks 'Sebelumnya'
    *   @param $text string
    */
    function prevtext($text) {
        $this->prevtext=$text;
        return $this;
    }

    /**
    *   Set teks 'Selanjutnya'
    *   @param $text string
    */
    function nexttext($text) {
        $this->nexttext=$text;
        return $this;
    }
}
