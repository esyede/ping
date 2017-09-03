<?php
/**
*    @package Ping Framework
*    @version 0.1-alpha
*    @author Suyadi <suyadi.1992@gmail.com>
*    @license MIT License
*    @see LICENSE.md file included in this package
*/


namespace Sys\Core;
defined('BASE') or exit('Access Denied!');


class Logger {
    protected $error=[];
    protected $core=[];
    protected $silent=false;
    protected $debug=[];
    protected $log=[];
    protected $codes=[
        100=>'Continue',
        101=>'Switching Protocols',
        102=>'Processing',
        200=>'OK',
        201=>'Created',
        202=>'Accepted',
        203=>'Non-Authoritative Information',
        204=>'No Content',
        205=>'Reset Content',
        206=>'Partial Content',
        207=>'Multi-Status',
        208=>'Already Reported',
        226=>'IM Used',
        300=>'Multiple Choices',
        301=>'Moved Permanently',
        302=>'Found',
        303=>'See Other',
        304=>'Not Modified',
        305=>'Use Proxy',
        306=>'(Unused)',
        307=>'Temporary Redirect',
        308=>'Permanent Redirect',
        400=>'Bad Request',
        401=>'Unauthorized',
        402=>'Payment Required',
        403=>'Forbidden',
        404=>'Not Found',
        405=>'Method Not Allowed',
        406=>'Not Acceptable',
        407=>'Proxy Authentication Required',
        408=>'Request Timeout',
        409=>'Conflict',
        410=>'Gone',
        411=>'Length Required',
        412=>'Precondition Failed',
        413=>'Payload Too Large',
        414=>'URI Too Long',
        415=>'Unsupported Media Type',
        416=>'Range Not Satisfiable',
        417=>'Expectation Failed',
        422=>'Unprocessable Entity',
        423=>'Locked',
        424=>'Failed Dependency',
        426=>'Upgrade Required',
        428=>'Precondition Required',
        429=>'Too Many Requests',
        431=>'Request Header Fields Too Large',
        500=>'Internal Server Error',
        501=>'Not Implemented',
        502=>'Bad Gateway',
        503=>'Service Unavailable',
        504=>'Gateway Timeout',
        505=>'HTTP Version Not Supported',
        506=>'Variant Also Negotiates',
        507=>'Insufficient Storage',
        508=>'Loop Detected',
        510=>'Not Extended',
        511=>'Network Authentication Required'
    ];

    /**
    *   Konstruktor kelas
    */
    function __construct() {
        $this->config=summon('config');
        $this->core=[
            'debug_level'=>$this->config->get('debug_level','core'),
            'env'=>$this->config->get('system_environment','core'),
            'routes'=>summon('router')->detail()
        ];
    }

    /**
    *   Trigger error page
    *   @param $type keyword
    *   @param $msg string
    *   @param $file string
    *   @param $line int
    *   @param $trace array
    */
    function err($type,$msg='',$file='',$line=0,$trace=null) {
        $this->error=[
            'level'=>$this->level($type),
            'message'=>$msg,
            'file'=>$file,
            'line'=>$line,
            'trace'=>$trace
        ];
        switch ($type) {
            case E_ERROR:
            case E_PARSE:
            case E_USER_ERROR:
                $level=2;
                break;
            case E_NOTICE:
            case E_WARNING:
            case E_DEPRECATED:
            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
                $level=1;
                break;
            default:
                $level=3;
                break;
        }
        if ($level==3||!$this->silent) {
            if ($this->core['debug_level']!=0)
                $this->annotate();
            if ($this->core['env']==2||$level>1)
                $this->draw();
        }
    }

    /**
    *   Menampilkan error page berdasarkan status code
    *   @param $code int
    *   @param $msg string
    *   @param $http string
    */
    function abort($code=null,$msg=null,$http='HTTP/1.0') {
        if (ob_get_level()!=0)
            ob_end_clean();
        $path=APP.'view'.DS.'error'.DS.(string)$code.'.html';
        if (is_int($code)) {
            if (file_exists($path)) {
                include ($path);
                die();
            }
            elseif (array_key_exists((int)$code,$this->codes)) {
                if (empty($msg))
                    $msg=$this->codes[$code];
                header('X-Powered-By: '.summon('loader')->sysinfo('package'));
                header("Status: ".(string)$code,false,(string)$code);
                header('Content-type: application/json',true,$code);
                echo json_encode([
                    'error'=>[
                        'code'=>$code,
                        'message'=>$this->codes[$code]
                    ]
                ]);
                die();
            }
            else abort("Not a valid http status code '%s'",[$code]);
        }
        elseif (is_string($code)) {
            if (file_exists($path)) {
                include ($path);
                die();
            }
        }
        else abort("Not a valid http status code '%s'",[$code]);
    }

    /**
    *   Log info error sistem ke file res/tmp/error.log
    *   @param $silent bool
    */
    protected function annotate($silent=false) {
        $msg="--------------------------------------------------------------------".PHP_EOL;
        $msg.="                 ERROR ".date('d-m-Y - H:i:s').PHP_EOL;
        $msg.="--------------------------------------------------------------------".PHP_EOL;
        $msg.=($silent==true)?"| Silent: YES":"| Silent: NO".PHP_EOL;
        $msg.="| Level: ".$this->error['level'].PHP_EOL;
        $msg.="| Message: ".$this->error['message'].PHP_EOL;
        $msg.="| File: ".$this->error['file'].PHP_EOL;
        $msg.="| Line: ".$this->error['line'].PHP_EOL;
        $msg.="| URL: ".$this->core['routes']['site_url'].'/'.$this->core['routes']['uri'].PHP_EOL;
        $msg.=PHP_EOL.PHP_EOL;
        $log=@fopen(RES.'logs'.DS.'error.log','a');
        @fwrite($log,$msg);
        @fclose($log);
    }

    /**
    *   Log pesan ke file res/logs/debug.php atau res/logs/log.php tergantung tipe log
    *   @param $type string
    *   @param $msg string
    */
    function log($msg,$type='info') {
        switch ($this->core['debug_level']) {
            case 0:
                return;
            case 1:
                if ($type!='error')
                    return;
                break;
            case 2:
                if ($type=='info')
                    return;
                break;
        }
        switch (strtolower($type)) {
            case 'debug':
                $this->debug[]=' - '.$msg;
                break;
            case 'info':
            case 'error':
                $this->log[]='['.date('d-m-Y - H:i:s').'] '.ucfirst($type).': '.$msg;
                break;
            default:
                return;
        }
    }

    /**
    *   Log untuk debugging error sistem
    */
    function debug() {
        if (!empty($this->debug)) {
            if ($log=fopen(RES.'logs'.DS.'debug.log','w')) {
                fwrite($log,"[Debug ".date('d-m-Y H:i:s')."]".PHP_EOL.implode(PHP_EOL,$this->debug).PHP_EOL);
                fclose($log);
            }
        }
        if (!empty($this->log)) {
            if ($log=fopen(RES.'logs'.DS.'log.log','a')) {
                for ($i=0;$i<sizeof($this->log);$i++)
                    fwrite($log,$this->log[$i].PHP_EOL);
                fclose($log);
            }
        }
    }

    /**
    *   Set level error
    *   @param $level keyword
    */
    function level($level) {
        switch ($level) {
            case E_ERROR:
                $msg='PHP Error';
                break;
            case E_PARSE:
                $msg='Parse Error';
                break;
            case E_USER_ERROR:
                $msg='PHP Error';
                break;
            case E_NOTICE:
                $msg='PHP Notice';
                break;
            case E_WARNING:
                $msg='PHP Warning';
                break;
            case E_DEPRECATED:
                $msg='Depreciated';
                break;
            case E_STRICT:
                $msg='PHP Strict';
                break;
            case E_USER_WARNING:
                $msg='PHP Warning';
                break;
            case E_USER_NOTICE:
                $msg='PHP Notice';
                break;
            default:
                $msg='PHP Fatal Error ['.$level.']';
                break;
        }
        return $msg;
    }

    /**
    *   Set silent mode
    *   @param $silent bool
    */
    function silent($silent=true) {
        $this->silent=$silent;
        return true;
    }

    /**
    *   Cetak halaman error html ke browser
    */
    protected function draw() {
        if (ob_get_level()!=0)
            ob_end_clean();
        ob_start();
        $file=APP.'view'.DS.'error'.DS.'system.html';
        if (!file_exists($file))
            $file=SYS.'error'.DS.'system.php';
        include ($file);
        $html=ob_get_contents();
        @ob_end_clean();
        if ($this->core['env']==2) {
            if (preg_match('~{{ backtrace }}(.*){{ /backtrace }}~iUs',$html,$match)) {
                $blocks='';
                unset($this->error['trace'][0]);
                $i=1;
                if (count($this->error['trace'])>0) {
                    foreach ($this->error['trace'] as $key=>$value) {
                        $block=str_replace('{{ # }}','#'.$key++,$match[1]);
                        foreach ($value as $k=>$v) {
                            if (is_object($v))
                                continue;
                            if (is_array($v))
                                $v=$this->vardump($v);
                            $block=str_replace('{{ '.$k.' }}',$v,$block);
                        }
                        $blocks.=$block;
                        $i++;
                    }
                }
                $html=str_replace($match[0],$blocks,$html);
            }
        }
        echo preg_replace('~{{ (.*) }}~','',str_replace(
            ['{{ level }}','{{ message }}','{{ file }}','{{ line }}'],
            [$this->error['level'],$this->error['message'],$this->error['file'],$this->error['line']],
        $html));
        die();
    }

    /**
    *   Variable-dumper dengan warna dan indentasi
    *   @param $var var
    *   @param $varname string
    *   @param $indent string
    */
    function vardump($var,$varname=null,$indent=null) {
        $c=['#F62459','#ECF0F1','#8E44AD','#FFB61E','#F9690E','#4B77BE','#0086b3','#BF55EC','#666','#DDD'];
        $html='<div style="font-family:monospace;color:'.$c[0].';background-color:'.$c[1].'">';
        $sp='<span style="color:';
        $cs='">';
        $csp='</span>';
        $line=$sp.$c[9].$cs.'|'.$csp.'&nbsp;';
        $type=ucfirst(gettype($var));
        switch ($type) {
            case 'Array':
                $html.=$indent.($varname?$varname.'=>':'').$sp.$c[2].$cs.'Array'.$csp.
                    '('.$sp.$c[2].$cs.count($var).$csp.')<br>'.$indent.'(<br>';
                    foreach (array_keys($var) as $name)
                        $html.=$this->vardump($var[$name],'['.$sp.$c[5].$cs.'\''.$name.'\''.$csp.']',$indent.$line);
                    $html.=$indent.')<br>';
                break;
            case 'String':
                $html.=$indent.$varname.' = '.$sp.$c[6].$cs.$type.'('.strlen($var).') '.$csp.$sp.$c[5].$cs.$var.$csp.'<br>';
                break;
            case 'Integer':
                $html.=$indent.$sp.$c[0].$cs.$varname.$csp.' = '.$sp.$c[6].$cs.$type.'('.strlen($var).') '.
                    $csp.$sp.$c[4].$cs.$var.$csp.'<br>';
                break;
            case 'Double':
                $html.=$indent.$sp.$c[0].$cs.$varname.$csp.' ='.$sp.$c[6].$cs.'Float('.strlen($var).') '.$csp.
                    $sp.$c[3].$cs.$var.$csp.'<br>';
                break;
            case 'Boolean':
                $html.=$indent.$sp.$c[0].$cs.$varname.$csp.' = '.$sp.$c[6].$cs.'Boolean('.strlen($var).') '.$csp.
                    $sp.$c[7].$cs.($var==1?'true':'false').$csp.'<br>';
                break;
            case 'NULL':
                $html.=$indent.$sp.$c[0].$cs.$varname.$csp.' ='.$sp.$c[6].$cs.$type.'('.strlen($var).') '.$csp.
                    $sp.$c[8].$cs.'NULL'.$csp.'<br>';
                break;
            case 'Object':
                $html.=$indent.$sp.$c[0].$cs.$varname.$csp.' = '.$sp.$c[6].$cs.'Object'.$csp.'<br>';
                break;
            case 'Resource':
                $html.=$indent.$sp.$c[0].$cs.$varname.$csp.' = '.$sp.$c[6].$cs.$type.$csp.$sp.$c[8].$cs.'Resource'.$csp.'<br>';
                break;
            default:
                $html.=$indent.$sp.$c[0].$cs.$varname.$csp.' = '.$sp.$c[6].$cs.$type.'('.@strlen($var).') '.$csp.$var.'<br>';
                break;
        }
        $html.='<div>';
        return $html;
    }
}
