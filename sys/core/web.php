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


class Web {

    protected $cookie=[];
    protected $ua=false;
    protected $ip=false;
    protected $taglist=[];
    protected $attrlist=[];
    protected $tagmethod=0;
    protected $attrmethod=0;
    protected $escape=1;
    protected $agent=[
        'mobile'=>'android|blackberry|phone|ipod|palm|windows\s+ce',
        'desktop'=>'bsd|linux|os\s+[x9]|solaris|windows',
        'bot'=>'bot|crawl|slurp|spider'
    ];
    protected $invalid=[
        'tags'=>[
            'applet','body','bgsound','base','basefont','embed','frame','frameset',
            'head','html','id','iframe','ilayer','layer','link','meta','name','object',
            'script','style','title','xml'
        ],
        'attrib'=>['action','background','codebase','dynsrc','lowsrc']
    ];

    /**
    *   Konstruktor kelas
    */
    function __construct() {
        $this->cookie=[
            'host'=>rtrim($_SERVER['HTTP_HOST'],'/'),
            'path'=>'/',
            'expiry'=>time()+(60*60*24*365)
        ];
    }

    /**
    *   Tangkap variabel POST
    *   @param $var string
    *   @param $escape bool
    */
    function post($var,$escape=true) {
        if (isset($_POST[$var])) {
            if ($escape==false)
                return $_POST[$var];
            else return $this->escape($_POST[$var]);
        }
        return false;
    }

    /**
    *   Tangkap variabel GET
    *   @param $var string
    *   @param $escape bool
    */
    function get($var,$escape=true) {
        if (isset($_GET[$var])) {
            if ($escape==false)
                return $_GET[$var];
            else return $this->escape($_GET[$var]);
        }
        return false;
    }

    /**
    *   Tangkap variabel COOKIE
    *   @param $name string
    *   @param $escape bool
    */
    function cookie($name,$escape=false) {
        if (isset($_COOKIE[$name])) {
            if ($escape==false)
                return $_COOKIE[$name];
            else return $this->escape($_COOKIE[$name]);
        }
        return false;
    }

    /**
    *   Set cookie
    *   @param $key string
    *   @param $val mixed
    *   @param $expiry int
    */
    function setcookie($key,$val,$expiry=null) {
        if ($expiry===null)
            $expiry=$this->cookie['expiry'];
        setcookie($key,$val,$expiry,$this->cookie['path']);
    }

    /**
    *   Hash string
    *   @param $str string
    *   @param $salt string
    *   @param $cost int
    */
    function hash($str,$salt=null,$cost=10) {
        if ($cost<4||$cost>31)
            abort("Invalid cost parameter '%s'",[$cost]);
        $len=22;
        if ($salt) {
            if (!preg_match('/^[[:alnum:]\.\/]{'.$len.',}$/',$salt))
                abort("Salt must be 22 alphanumeric characters");
        }
        else {
            $line=16;
            $dt='';
            if (extension_loaded('mcrypt'))
                $dt=mcrypt_create_iv($line,MCRYPT_DEV_URANDOM);
            if (!$dt&&extension_loaded('openssl'))
                $dt=openssl_random_pseudo_bytes($line);
            if (!$dt)
                for ($i=0;$i<$line;$i++)
                    $dt.=chr(mt_rand(0,255));
            $salt=str_replace('+','.',base64_encode($dt));
        }
        $hash=crypt($str,sprintf('$2y$%02d$',$cost).substr($salt,0,$len));
        return strlen($hash)>13?$hash:false;
    }

    /**
    *   Cek apakah hash lemah atau tidak
    *   @param $str string
    *   @param $cost int
    */
    function weak($hash,$cost=10) {
        list ($len)=sscanf($hash,"$2y$%d$");
        return $len<$cost;
    }

    /**
    *   Bandingkan sebuah string dengan hashnya
    *   @param $str string
    *   @param $hash string
    */
    function verify($str,$hash) {
        $value=crypt($str,$hash);
        $len=strlen($value);
        if ($len!=strlen($hash)||$len<14)
            return false;
        $result=0;
        for ($i=0;$i<$len;$i++)
            $result|=(ord($value[$i])^ord($hash[$i]));
        return $result===0;
    }

    /**
    *   String adalah lebih dari panjang minimal?
    *   @param $var mixed
    *   @param $min mixed
    */
    function min($var,$min) {
        if (!is_numeric($var))
            return (strlen($var)>=$min);
        return ($var>=$min);
    }

    /**
    *   String adalah tidak lebih dari panjang maksimal?
    *   @param $var mixed
    *   @param $max mixed
    */
    function max($var,$max) {
        if (!is_numeric($var))
            return (strlen($var)<=$max);
        return ($var<=$max);
    }

    /**
    *   Apakah variabel pertama letaknya diantra dua buah variabel lainnya?
    *   @param $var mixed
    *   @param $min int
    *   @param $max int
    *   @param inclusive bool
    */
    function between($var,$min,$max,$inclusive=true) {
        if ($inclusive)
            return $var>=$min&&$var<=$max;
        return $var>$min&&$var<$max;
    }

    /**
    *   Bandingkan dua buah variabel
    *   @param $a mixed
    *   @param $operator string
    *   @param $b mixed
    */
    function compare($a,$operator,$b) {
        switch ($operator) {
            case '>':
                return $a>$b;
            case '<':
                return $a<$b;
            case '>=':
                return $a>=$b;
            case '<=':
                return $a<=$b;
            case '==':
                return $a==$b;
            case '===':
                return $a===$b;
            case '!=':
                return $a!=$b;
            case '!==':
                return $a!==$b;
            default:
                abort("Unidentified operator '%s'",[$operator]);
        }
    }

    /**
    *   Pencocokkan string dengan regular expression
    *   @param $str string
    *   @param $pattern string
    */
    function regex($str,$regex) {
        return (!preg_match('/'.$regex.'/',$str))?false:true;
    }

    /**
    *   Apakah variabel ini kosong?
    *   @param $str string
    */
    function isempty($var) {
        if (is_array($var)) {
            if (sizeof($var)<0||$var===null)
                return true;
            else return false;
        }
        else {
            if (!isset($var)||strlen($var)==0||$var===null)
                return true;
            else return false;
        }
    }

    /**
    *   Variabel adalah data numerik?
    *   @param $str string
    */
    function isnumeric($str) {
        return (is_numeric($str));
    }

    /**
    *   Variabel adalah desimal?
    *   @param $str string
    */
    function isfloat($str) {
        return (is_float($str));
    }

    /**
    *   Apakah string ini adalah alamat emaiL?
    *   @param $str string
    *   @param $mx bool
    */
    function isemail($str) {
        return is_string(filter_var($str,FILTER_VALIDATE_EMAIL));
    }

    /**
	*	Apakah string ini adalah url?
	*	@param $str string
	*/
	function isurl($str) {
		return is_string(filter_var($str,FILTER_VALIDATE_URL))
            &&((!preg_match('@^(mailto|ftp|http(s)?)://(.*)$@i',$str))?false:true);
	}

    /**
    *   Apakah string ini adalah tanggal?
    *   @param $str string
    *   @param $min int
    *   @param $max int
    */
    function isdate($str,$min=null,$max=null) {
        if (isset($str)) {
            $date=$str;
            if (!is_numeric($str))
                $date=strtotime($str);
            if ($date===false||$date==-1)
                return false;
            if ($min!==null&&(!is_numeric($min)?$min=strtotime($min):true)&&$date<$min)
                return false;
            if ($max!==null&&(!is_numeric($max)?$max=strtotime($max):true)&&$date>$max)
                return false;
            return true;
        }
        return false;
    }

    /**
    *   Apakah request ini adalah ajax?
    */
    function isajax() {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            &&strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest');
    }

    /**
    *   Mengambil data user-agent pengunjung
    */
    function ua() {
        if ($this->ua==false)
            $this->ua=(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:false);
        return $this->ua;
    }

    /**
    *   Mengambil data alamat IP pengunjung
    */
    function ip() {
        if ($this->ip===false) {
            if (isset($_SERVER['HTTP_CLIENT_IP'])
            &&$this->ipvalid($_SERVER['HTTP_CLIENT_IP']))
                $this->ip=$_SERVER['HTTP_CLIENT_IP'];
            elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            &&!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipx=explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($ipx as $ipv) {
                    if ($this->ipvalid($ipv)) {
                        $this->ip=$ipv;
                        break;
                    }
                }
            }
            elseif (isset($_SERVER['HTTP_X_FORWARDED'])
            &&$this->ipvalid($_SERVER['HTTP_X_FORWARDED']))
                $this->ip=$_SERVER['HTTP_X_FORWARDED'];
            elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])
            &&$this->ipvalid($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
                $this->ip=$_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
            elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])
            &&$this->ipvalid($_SERVER['HTTP_FORWARDED_FOR']))
                $this->ip=$_SERVER['HTTP_FORWARDED_FOR'];
            elseif (isset($_SERVER['HTTP_FORWARDED'])
            &&$this->ipvalid($_SERVER['HTTP_FORWARDED']))
                $this->ip=$_SERVER['HTTP_FORWARDED'];
            elseif (isset($_SERVER['HTTP_VIA'])
            &&$this->ipvalid($_SERVER['HTTP_VIAD']))
                $this->ip=$_SERVER['HTTP_VIA'];
            elseif (isset($_SERVER['REMOTE_ADDR'])
            &&!empty($_SERVER['REMOTE_ADDR']))
                $this->ip=$_SERVER['REMOTE_ADDR'];
            if ($this->ip===false)
                $this->ip='0.0.0.0';
            if ($this->ip=='::1')
                $this->ip='127.0.0.1';
        }
        return $this->ip;
    }

    /**
    *   Apakah IP ini valid?
    *   @param $ip string
    */
    function ipvalid($ip) {
        $ip=trim($ip);
        if (!empty($ip)&&ip2long($ip)!=-1) {
            $range=[
                ['0.0.0.0','2.255.255.255'],
                ['10.0.0.0','10.255.255.255'],
                ['127.0.0.0','127.255.255.255'],
                ['169.254.0.0','169.254.255.255'],
                ['172.16.0.0','172.31.255.255'],
                ['192.0.2.0','192.0.2.255'],
                ['192.168.0.0','192.168.255.255'],
                ['255.255.255.0','255.255.255.255']
            ];
            foreach ($range as $r) {
                $min=ip2long($r[0]);
                $max=ip2long($r[1]);
                if ((ip2long($ip)>=$min)&&(ip2long($ip)<=$max))
                    return false;
            }
            return true;
        }
        return false;
    }

    /**
    *   Apakah ini IPv4?
    *   @param $ip string
    */
    function ipv4($ip) {
        return (bool)filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);
    }

    /**
    *   Apakah ini IPv6?
    *   @param $ip string
    */
    function ipv6($ip) {
        return (bool)filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6);
    }

    /**
    *   Apakah IP ini private-ip?
    *   @param $ip string
    */
    function isprivate($ip) {
        return !(bool)filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4
            |FILTER_FLAG_IPV6|FILTER_FLAG_NO_PRIV_RANGE);
    }

    /**
    *   Apakah IP ini reserved-ip?
    *   @param $ip string
    */
    function isreserved($ip) {
        return !(bool)filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4
            |FILTER_FLAG_IPV6|FILTER_FLAG_NO_RES_RANGE);
    }

    /**
    *   Apakah IP ini publik?
    *   @param $ip string
    */
    function ispublic($ip) {
        return (bool)filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4
            |FILTER_FLAG_IPV6|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);
    }

    /**
    *   Apakah user-agent ini adalah perangkat desktop?
    *   @param $agent string
    */
    function isdesktop($agent=null) {
        if (!isset($agent))
            $agent=$this->ua();
        return (bool)preg_match('/('.$this->agent['desktop'].')/i',$agent)
            &&!$this->ismobile($agent);
    }

    /**
    *   Apakah user-agent ini adalah perangkat mobile?
    *   @param $agent string
    */
    function ismobile($agent=null) {
        if (!isset($agent))
            $agent=$this->ua();
        return (bool)preg_match('/('.$this->agent['mobile'].')/i',$agent);
    }

    /**
    *   Apakah user-agent ini adalah sebuah bot?
    *   @param $agent string
    */
    function isbot($agent=null) {
        if (!isset($agent))
            $agent=$this->ua();
        return (bool)preg_match('/('.$this->agent['bot'].')/i',$agent);
    }

    /**
    *   Info WHOIS domain/ip
    *   @param $addr string
    *   @param $server string
    */
    function whois($addr,$server='whois.internic.net') {
        $socket=@fsockopen($server,43,null,null);
        if (!$socket)
            return false;
        stream_set_blocking($socket,false);
        stream_set_timeout($socket,ini_get('default_socket_timeout'));
        fputs($socket,$addr."\r\n");
        $info=stream_get_meta_data($socket);
        $response='';
        while (!feof($socket)&&!$info['timed_out']) {
            $response.=fgets($socket,4096);
            $info=stream_get_meta_data($socket);
        }
        fclose($socket);
        return $info['timed_out']?false:trim($response);
    }

    /**
    *   Web content scrapper dengan curl
    *   @param $url string
    *   @param $to_array bool
    */
    function scrap($url,$to_array=false) {
        $url=str_replace(' ','%20',$url);
        $result=false;
        if (function_exists('curl_exec')) {
            $curl=@curl_init();
            @curl_setopt($curl,CURLOPT_URL,$url);
            @curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
            @curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,3);
            @curl_setopt($curl,CURLOPT_TIMEOUT,10);
            @curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
            $result=@curl_exec($curl);
            if (@curl_errno($curl))
                return false;
            if ($to_array==true)
                $result=explode("\n",trim($result));
            @curl_close($curl);
        }
        elseif (ini_get('allow_url_fopen'))
            $result=($to_array==true)?@file($url):@file_get_contents($url,false);
        return $result;
    }

    /**
    *   Fake string filler
    *   @param $count int
    *   @param $max int
    *   @param $std bool
    */
    function filler($count=1,$max=20,$std=true) {
        $out='';
        if ($std)
            $out='Lorem ipsum dolor sit amet, consectetur adipisicing elit, '.
            'sed do eiusmod tempor incididunt ut labore et dolore magna '.'aliqua.';
        $rand=explode(' ',
            'a ab ad accusamus adipisci alias aliquam amet animi aperiam '.
            'architecto asperiores aspernatur assumenda at atque aut beatae '.
            'blanditiis cillum commodi consequatur corporis corrupti culpa '.
            'cum cupiditate debitis delectus deleniti deserunt dicta '.
            'dignissimos distinctio dolor ducimus duis ea eaque earum eius '.
            'eligendi enim eos error esse est eum eveniet ex excepteur '.
            'exercitationem expedita explicabo facere facilis fugiat harum '.
            'hic id illum impedit in incidunt ipsa iste itaque iure iusto '.
            'laborum laudantium libero magnam maiores maxime minim minus '.
            'modi molestiae mollitia nam natus necessitatibus nemo neque '.
            'nesciunt nihil nisi nobis non nostrum nulla numquam occaecati '.
            'odio officia omnis optio pariatur perferendis perspiciatis '.
            'placeat porro possimus praesentium proident quae quia quibus '.
            'quo ratione recusandae reiciendis rem repellat reprehenderit '.
            'repudiandae rerum saepe sapiente sequi similique sint soluta '.
            'suscipit tempora tenetur totam ut ullam unde vel veniam vero '.
            'vitae voluptas');
        for ($i=0,$add=$count-(int)$std;$i<$add;$i++) {
            shuffle($rand);
            $out.=' '.ucfirst(implode(' ',array_slice($rand,0,mt_rand(3,$max)))).'.';
        }
        return $out;
    }

    /**
    *   Set aturan penyaringan string
    *   @param $taglist string
    *   @param $attrlist string
    *   @param $tagmethod string
    *   @param $attrmethod string
    *   @param $escape bool
    */
    function setrule($taglist=[],$attrlist=[],$tagmethod=false,$attrmethod=false,$escape=true) {
        $tagnum=count($taglist);
        $attrnum=count($attrlist);
        for ($i=0;$i<$tagnum;$i++)
            $taglist[$i]=strtolower($taglist[$i]);
        for ($i=0;$i<$attrnum;$i++)
            $attrlist[$i]=strtolower($attrlist[$i]);
        $this->taglist=$taglist;
        $this->attrlist=$attrlist;
        $this->tagmethod=$tagmethod;
        $this->attrmethod=$attrmethod;
        $this->escape=$escape;
    }

    /**
    *   Saring string dari tag-tag berbahaya
    *   @param $var string
    */
    function escape($var) {
        if (is_array($var)) {
            foreach ($var as $key=>$value)
                if (is_string($value))
                    $var[$key]=$this->del($this->decode($value));
            return $var;
        }
        elseif (is_string($var))
            return $this->del($this->decode($var));
        return $var;
    }

    /**
    *   Hapus tag dan atribut terlarang
    *   @param $var string
    */
    protected function del($var) {
        $i=0;
        while ($var!=$this->rmtag($var)) {
            $var=$this->rmtag($var);
            $i++;
        }
        return $var;
    }

    /**
    *   Hapus tag terlarang
    *   @param $var string
    */
    protected function rmtag($var) {
        $otag=null;
        $xtag=$var;
        $otag_start=strpos($var,'<');
        while ($otag_start!==false) {
            $otag.=substr($xtag,0,$otag_start);
            $xtag=substr($xtag,$otag_start);
            $from_otag=substr($xtag,1);
            $otag_finish=strpos($from_otag,'>');
            if ($otag_finish===false)
                break;
            $otag_loop=strpos($from_otag,'<');
            if (($otag_loop!==false)&&($otag_loop<$otag_finish)) {
                $otag.=substr($xtag,0,($otag_loop+1));
                $xtag=substr($xtag,($otag_loop+1));
                $otag_start=strpos($xtag,'<');
                continue;
            }
            $otag_loop=(strpos($from_otag,'<')+$otag_start+1);
            $currtag=substr($from_otag,0,$otag_finish);
            $tagLength=strlen($currtag);
            if (!$otag_finish) {
                $otag.=$xtag;
                $otag_start=strpos($xtag,'<');
            }
            $ltag=$currtag;
            $setattr=[];
            $currws=strpos($ltag,' ');
            if (substr($currtag,0,1)=='/') {
                $is_xtag=true;
                list ($tagname)=explode(' ',$currtag);
                $tagname=substr($tagname,1);
            }
            else {
                $is_xtag=false;
                list ($tagname)=explode(' ',$currtag);
            }
            if ((!preg_match("/^[a-z][a-z0-9]*$/i",$tagname))
            ||(!$tagname)
            ||((in_array(strtolower($tagname),$this->invalid['tags']))
            &&($this->escape))) {
                $xtag=substr($xtag,($tagLength+2));
                $otag_start=strpos($xtag,'<');
                continue;
            }
            while ($currws!==false) {
                $fromws=substr($ltag,($currws+1));
                $nextws=strpos($fromws,' ');
                $oquote=strpos($fromws,'"');
                $xquote=strpos(substr($fromws,($oquote+1)),'"')+$oquote+1;
                if (strpos($fromws,'=')!==false) {
                    if (($oquote!==false)&&(strpos(substr($fromws,($oquote+1)),'"')!==false))
                        $attrib=substr($fromws,0,($xquote+1));
                    else $attrib=substr($fromws,0,$nextws);
                }
                else $attrib=substr($fromws,0,$nextws);
                if (!$attrib)
                    $attrib=$fromws;
                $setattr[]=$attrib;
                $ltag=substr($fromws,strlen($attrib));
                $currws=strpos($ltag,' ');
            }
            $tagmatch=in_array(strtolower($tagname),$this->taglist);
            if ((!$tagmatch&&$this->tagmethod)||($tagmatch&&!$this->tagmethod)) {
                if (!$is_xtag) {
                    $setattr=$this->rmattr($setattr);
                    $otag.='<'.$tagname;
                    for ($i=0;$i<count($setattr);$i++) $otag.=' '.$setattr[$i];
                    if (strpos($from_otag,"</".$tagname)) $otag.='>';
                    else $otag.=' />';
                }
                else $otag.='</'.$tagname.'>';
            }
            $xtag=substr($xtag,($tagLength+2));
            $otag_start=strpos($xtag,'<');
        }
        $otag.=$xtag;
        return $otag;
    }

    /**
    *   Hapus atribut terlarang
    *   @param $var string
    */
    protected function rmattr($setattr) {
        $result=[];
        for ($i=0;$i<count($setattr);$i++) {
            if (!$setattr[$i])
                continue;
            $sub=explode('=',trim($setattr[$i]));
            list ($sub[0])=explode(' ',$sub[0]);
            if ((!preg_match("/^[a-z]*$/i",$sub[0]))
            ||(($this->escape)
            &&((in_array(strtolower($sub[0]),$this->invalid['attrib']))
            ||(substr($sub[0],0,2)=='on'))))
                continue;
            if ($sub[1]) {
                $sub[1]=str_replace('&#','',$sub[1]);
                $sub[1]=preg_replace('/\s+/','',$sub[1]);
                $sub[1]=str_replace('"','',$sub[1]);
                if ((substr($sub[1],0,1)=="'")
                &&(substr($sub[1],(strlen($sub[1])-1),1)=="'")) {
                    $sub[1]=substr($sub[1],1,(strlen($sub[1])-2));
                }
                $sub[1]=stripslashes($sub[1]);
            }
            if (((strpos(strtolower($sub[1]),'expression')!==false)
            &&(strtolower($sub[0])=='style'))
            ||(strpos(strtolower($sub[1]),'javascript:')!==false)
            ||(strpos(strtolower($sub[1]),'behaviour:')!==false)
            ||(strpos(strtolower($sub[1]),'vbscript:')!==false)
            ||(strpos(strtolower($sub[1]),'mocha:')!==false)
            ||(strpos(strtolower($sub[1]),'livescript:')!==false))
                continue;
            $attrmatch=in_array(strtolower($sub[0]),$this->attrlist);
            if ((!$attrmatch&&$this->attrmethod)||($attrmatch&&!$this->attrmethod)) {
                if ($sub[1])
                    $result[]=$sub[0].'="'.$sub[1].'"';
                elseif ($sub[1]=="0")
                    $result[]=$sub[0].'="0"';
                else $result[]=$sub[0].'="'.$sub[0].'"';
            }
        }
        return $result;
    }

    /**
    *   Decode karakter
    *   @param $var string
    */
    protected function decode($var) {
        $var=html_entity_decode($var,ENT_QUOTES,"UTF-8");
        $var=preg_replace_callback('/&#(\d+);/m',function ($match) {
            return chr(preg_replace("/[&#;]/","",$match[0]));
        },$var);
        $var=preg_replace_callback('/&#x([a-f0-9]+);/mi',function ($match) {
            return chr(preg_replace("/&#x;/","",$match[0]));
        },$var);
        return $var;
    }
}
