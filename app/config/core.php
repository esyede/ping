<?php
defined('BASE') or exit('Access Denied!');

//! ---------------------------------------------------------------------
//! System Environment
//! ---------------------------------------------------------------------
//! Bilah ini untuk menentukan level error yang tampil pada situs anda.
//! Saat pengembangan, silahkan di-set ke 2,
//! Saat situs di-online-kan, set ke 1
//! Level:
//! 1 = Produksi (Hanya Fatal error)
//! 2 = Pengembangan (tampilkan semua error + debugging)

$core['system_environment']=2;


//! ---------------------------------------------------------------------
//! Debug Level
//! ---------------------------------------------------------------------
//! Level debugging. Pesan error disimpan di file "tmp/log/error.log"
//! sedangkan file debug disimpan di file "tmp/log/debug.log"
//! Nilai:
//! 0 = Mati
//! 1 = Hanya error
//! 2 = Hanya debug dan error
//! 3 = Semua error, debug dan info

$core['debug_level']=3;


//! ---------------------------------------------------------------------
//! Trap Fatal Errors
//! ---------------------------------------------------------------------
//! Tangkap fatal error dan parse error. Matikan saja jika anda-
//! telah menggunakan debugger khusus semacam XDebug

$core['trap_fatal_error']=true;


//! ---------------------------------------------------------------------
//! Default Controller and Action
//! ---------------------------------------------------------------------
//! Ini adalah kontroler dan aksi default untuk halaman utama situs anda

$core['default_controller']='home';
$core['default_action']='index';


//! ---------------------------------------------------------------------
//! Query String
//! ---------------------------------------------------------------------
//! Ketika diaktifkan, anda bisa menggunakan query string seperti biasa-
//! sehingga url anda tidak lagi seperti situs.com/home/index
//! tetapi menjadi situs.com/?c=home&a=index
//! Bilah ini berguna ketika server anda tidak mendukung mod_rewrite

$core['enable_query_string']=false;
$core['controller_query_string']='c';
$core['action_query_string']='a';
