<?php
defined('BASE') or exit('Access Denied!');

//! ---------------------------------------------------------------------
//! Konfigurasi Database
//! ---------------------------------------------------------------------
//! driver yang didukung:
//! mysql, mysqli, pgsql, sqlite, sqlite3, mongodb dan jong (text database)
$db=[
    'db_1'=>
        [
            'driver'=>'mysql',
            'host'=>'127.0.0.1',
            'port'=>'3306',
            'username'=>'root',
            'password'=>'',
            'database'=>'my_database1',
            'cache'=>null
        ],
//! ---------------------------------------------------------------------
//! Koneksi ke akun database-2, 3 dan seterusnya (jika dibutuhkan)
//! ---------------------------------------------------------------------
    // 'db_2'=>
    //     [
    //         'driver'=>'mysqli',
    //         'host'=>'127.0.0.1',
    //         'port'=>'3306',
    //         'username'=>'root',
    //         'password'=>'',
    //         'database'=>'my_database2',
    //         'cache'=>null
    //     ]

    ];
