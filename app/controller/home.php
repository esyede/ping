<?php
defined('BASE') or exit('Access Denied!');

class Home extends Sys\Core\Controller {

    function __construct() {
        // panggil parent controller
        parent::__construct();
        // muat library template
        $this->load->lib('template');
    }


    function _afterroute() {
        // render halaman html dengan data yang telah di-set di fungsi index
        $this->template->render($this->get('content.layout'),$this->get('content'));
    }


    function index() {
        // set data ke hive (untuk view)
        $this->set('content',[
            'layout'=>'home.html',
            'page_title'=>$this->load->sysinfo('package').' for PHP',
            'fw_slogan'=>'Micro framework MVC ringan, cepat dan minimalis untuk PHP.',
            'version'=>$this->load->sysinfo('version'),
            'elapsed'=>$this->load->sysinfo('elapsed'),
            'memory'=>$this->load->sysinfo('memory'),
            'docs_link'=>'http://pingframework.com/docs',
            'docs_title'=>'Panduan',
        ]);
    }
}
