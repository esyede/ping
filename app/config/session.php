<?php
defined('BASE') or exit('Access Denied!');

//! ---------------------------------------------------------------------
//! Session Configs
//! ---------------------------------------------------------------------
$session['use_database']=false;
$session['database_container']='db_1';
$session['table_name']='session_';
$session['cookie_name']='cookie_';
$session['id_column_name']='sess_id';
$session['token_column_name']='sess_token';
$session['ip_address_column_name']='ip_address';
$session['last_seen_column_name']='last_seen';
$session['session_data_column_name']='sess_data';
