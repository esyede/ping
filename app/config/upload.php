<?php
defined('BASE') or exit('Access Denied!');

//! ---------------------------------------------------------------------
//! Upload Configs
//! ---------------------------------------------------------------------
$upload['upload_path']='res/upload/';
$upload['check_mime_type']=true;
$upload['file_name_maxlenght']=100;
$upload['rename_uploaded_file']=false;
$upload['replace_existing_file']=false;
$upload['check_file_name']=true;
$upload['file_permission']=0644;
$upload['directory_permission']=0755;
$upload['create_directory_if_not_exist']=true;
$upload['accepted_mime_types']=[
    '.bmp'=>'image/bmp',
    '.gif'=>'image/gif',
    '.jpg'=>'image/jpeg',
    '.jpeg'=>'image/jpeg',
    '.pdf'=>'application/pdf',
    '.png'=>'image/png',
    '.zip'=>'application/zip'
];

//! ---------------------------------------------------------------------
//! Respomse Messages
//! ---------------------------------------------------------------------
$upload['response_messages']=[
    0=>'File: <b>%s</b> successfully uploaded!',
    2=>'The uploaded file exceeds the max. upload filesize directive in the server configuration.',
    3=>'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.',
    4=>'The uploaded file was only partially uploaded',
    5=>'No file was uploaded',
    6=>'Missing a temporary folder. ',
    7=>'Failed to write file to disk. ',
    8=>'A PHP extension stopped the file upload. ',
    9=>'Please select a file for upload.',
    10=>'Only files with the following extensions are allowed: %s',
    11=>'Sorry, the filename contains invalid characters. '.
    'Use only alphanumerical chars and separate parts of the name (if needed) with an underscore. '.
    'A valid filename ends with one dot followed by the extension.',
    12=>'The filename exceeds the maximum length of %s characters.',
    13=>'Sorry,the upload directory does not exist!',
    14=>'Uploading %s error! Sorry, a file with this name already exist.',
    15=>'The uploaded file is renamed to <b>%s</b>.',
    16=>'The file %s does not exist.',
    17=>'The file type (MIME type) is not valid.',
    18=>'The MIME type check is enabled, but is not supported.'
];
